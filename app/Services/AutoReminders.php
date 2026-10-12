<?php

namespace App\Services;

use App\Models\{Member, MessageLog};
use Illuminate\Support\Collection;

/**
 * Works out, every day, which member should get which message, then sends it.
 *
 * Every message is tied to an "anchor" (this membership, this week, this birthday...) and is sent at most once per
 * anchor, so running the job twice, or catching up after the server was off for a day, never double-messages anyone.
 */
class AutoReminders
{
    public const MAX_PER_RUN = 300;

    /**
     * @return array{send: array<int,array>, waiting: array<int,array>, no_consent: int}
     *   send     due and allowed right now
     *   waiting  due, but held back (switched off, or the wording is not approved yet)
     */
    public static function plan(): array
    {
        $mode = WhatsApp::mode();
        $members = Member::with(['user', 'currentMembership.plan', 'memberships.payments'])->get();
        $send = $waiting = []; $noConsent = 0;

        foreach ($members as $m) {
            if ($m->frozen || ! WhatsApp::number($m)) continue;
            foreach (self::dueFor($m) as [$key, $anchor]) {
                if (! $m->whatsapp_optin) { $noConsent++; continue; }
                if (self::handled($m, $key, $anchor, $mode)) continue;
                $row = ['member' => $m, 'key' => $key, 'anchor' => $anchor];
                if (! WhatsApp::autoOn($key)) { $waiting[] = $row + ['why' => 'That message is not switched on for automatic sending.']; continue; }
                if ($mode === 'live' && ! WhatsApp::approved($key)) { $waiting[] = $row + ['why' => 'WhatsApp has not approved this wording yet.']; continue; }
                $send[] = $row;
            }
        }
        return ['send' => $send, 'waiting' => $waiting, 'no_consent' => $noConsent];
    }

    /** @return array<int,array{0:string,1:string}> [message key, anchor] for everything this member is due today. */
    public static function dueFor(Member $m): array
    {
        $out = [];
        $ms = $m->currentMembership;
        $d = $m->daysLeft();

        if ($ms && $d !== null) {
            $key = match (true) {
                $d >= 3 && $d <= 7 => 'expiring_7',
                $d >= 1 && $d <= 2 => 'expiring_2',
                $d === 0 => 'expiring_0',
                $d <= -1 && $d >= -14 => 'expired_3',
                default => null,
            };
            if ($key) $out[] = [$key, 'ms'.$ms->id];
        }

        // money still owed on the membership: not on the first days, then at most once a week, and never more than three times
        if ($ms && $m->balanceDue() > 0 && $ms->starts_on->lte(today()->subDays(3))) {
            $recent = MessageLog::where('member_id', $m->id)->where('key', 'owing')->whereIn('status', ['sent', 'practice'])
                ->where('created_at', '>=', now()->subDays(60))->get();
            if ($recent->count() < 3 && ! $recent->contains(fn ($l) => $l->created_at->gt(now()->subDays(7)))) $out[] = ['owing', 'owing'.today()->format('oW')];
        }

        return $out;
    }

    /** Has this message already gone (or been rehearsed, in practice mode), or failed for good? */
    private static function handled(Member $m, string $key, string $anchor, string $mode): bool
    {
        $q = MessageLog::where(['member_id' => $m->id, 'key' => $key, 'anchor' => $anchor]);
        if ($mode === 'practice') return (clone $q)->where('status', 'practice')->exists();
        if ((clone $q)->whereIn('status', MessageLog::DELIVERED)->exists()) return true;
        return (clone $q)->where('status', 'failed')->where(fn ($w) => $w->where('attempts', '>=', 3))->exists()
            || (clone $q)->where('status', 'failed')->count() >= 3;
    }

    /* ------------------------------------------------------------------ not on the daily schedule: they follow an action */

    /** A thank-you with the receipt number, right after a payment is recorded. Never lets a problem here break the payment. */
    public static function receipt(\App\Models\Payment $p): ?MessageLog
    {
        try {
            $mode = WhatsApp::mode();
            if ($mode === 'off' || ! WhatsApp::autoOn('payment')) return null;
            if ($p->paid_on->lt(today()->subDay())) return null;                       // an old payment entered later gets no message
            $m = $p->member()->with('user')->first();
            if (! $m || ! $m->whatsapp_optin || ! WhatsApp::number($m)) return null;
            if ($mode === 'live' && ! WhatsApp::approved('payment')) return null;
            $anchor = 'pay'.$p->id;
            if (self::handled($m, 'payment', $anchor, $mode)) return null;
            return WhatsApp::deliver($m, 'payment', $anchor, ['amount' => rs($p->amount), 'date' => $p->paid_on->format('d M Y'), 'number' => $p->receipt_no]);
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    /** Active members who agreed to messages and have a usable number: who an announcement would reach. */
    public static function announcementRecipients(): Collection
    {
        return Member::with(['user', 'currentMembership'])->where('whatsapp_optin', true)->get()
            ->filter(fn ($m) => in_array($m->status(), ['active', 'expiring'], true) && WhatsApp::number($m))->values();
    }

    /** Text of an announcement as one clean line, which is what a WhatsApp template can carry. */
    public static function announcementText(string $title, string $body): string
    {
        $t = trim(preg_replace('/\s+/', ' ', rtrim(trim($title), '.:').'. '.$body));
        return mb_strlen($t) > 480 ? rtrim(mb_substr($t, 0, 477)).'...' : $t;
    }

    /**
     * Sends one announcement to every active member who agreed. Each member gets it once per announcement.
     * @return array{sent:int, failed:int, why:?string}
     */
    public static function announce(string $text, string $anchor): array
    {
        $mode = WhatsApp::mode();
        if ($mode === 'off') return ['sent' => 0, 'failed' => 0, 'why' => 'Automatic sending is switched off.'];
        if (! WhatsApp::autoOn('announcement')) return ['sent' => 0, 'failed' => 0, 'why' => 'Announcements are not switched on for automatic sending.'];
        if ($mode === 'live' && ! WhatsApp::approved('announcement')) return ['sent' => 0, 'failed' => 0, 'why' => 'WhatsApp has not approved the announcement message yet.'];
        if (PHP_SAPI !== 'cli') @set_time_limit(180);       // a web request needs room; the scheduled command has no limit
        $sent = $failed = 0;
        foreach (self::announcementRecipients()->take(self::MAX_PER_RUN) as $m) {
            if (self::handled($m, 'announcement', $anchor, $mode)) continue;
            $log = WhatsApp::deliver($m, 'announcement', $anchor, ['message' => $text]);
            $log->status === 'failed' ? $failed++ : $sent++;
            if ($mode === 'live') usleep(150000);
        }
        return ['sent' => $sent, 'failed' => $failed, 'why' => null];
    }

    /** Sends everything due on the daily schedule. Returns the numbers for the summary line. */
    public static function run(): array
    {
        $mode = WhatsApp::mode();
        if ($mode === 'off') return ['mode' => $mode, 'sent' => 0, 'failed' => 0, 'waiting' => 0];
        \App\Models\Setting::put('wa_last_run', now()->toDateTimeString(), false);     // shown on the settings screen so a silent cron job is noticed

        $plan = self::plan();
        $sent = $failed = 0;
        foreach (array_slice($plan['send'], 0, self::MAX_PER_RUN) as $row) {
            $log = WhatsApp::deliver($row['member'], $row['key'], $row['anchor']);
            $log->status === 'failed' ? $failed++ : $sent++;
            if ($mode === 'live') usleep(150000);          // gentle pace, well inside WhatsApp's limits
        }
        return ['mode' => $mode, 'sent' => $sent, 'failed' => $failed, 'waiting' => count($plan['waiting'])];
    }
}
