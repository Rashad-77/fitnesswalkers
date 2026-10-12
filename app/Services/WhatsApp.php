<?php

namespace App\Services;

use App\Models\{Member, MessageLog, Reminder, Setting};
use App\Support\Messages;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\{Crypt, Http};
use Illuminate\Support\Str;

/**
 * Automatic WhatsApp messages through Meta's official WhatsApp Business Platform (Cloud API).
 *
 * Modes (Settings > Automatic sending):
 *   off       nothing is sent
 *   practice  the daily job runs and records what it WOULD send, but contacts nobody
 *   live      messages really go out
 *
 * This is SEND-ONLY. Nothing is ever received from WhatsApp: no webhook, no delivery receipts, no replies. "sent" means
 * WhatsApp accepted the message. The messages therefore ask members to call or visit, never to reply, and each template
 * carries a footer saying how to stop them (tell the gym), because we cannot see a STOP reply.
 *
 * WhatsApp only lets a business start a conversation with an approved template, so every message the owner writes in
 * Settings > Messages is turned into a template. Its name contains a short fingerprint of the wording, which means
 * changing the words always means a new template, and a message is only sent when that exact wording is approved.
 */
class WhatsApp
{
    /** Small print under every message. Members cannot reply, so it says how to opt out. */
    public const FOOTER = 'To stop these messages, tell us at the gym.';

    /**
     * The only messages that can go out by themselves: membership reminders, receipts and announcements.
     * Everything else (check-ins, birthdays, welcomes) stays a manual, one-tap message from the owner's own WhatsApp.
     * The value is the category Meta will judge it as; Meta has the final say, and marketing costs more per message.
     */
    public const AUTO = [
        'expiring_7' => 'UTILITY', 'expiring_2' => 'UTILITY', 'expiring_0' => 'UTILITY', 'expired_3' => 'UTILITY',
        'owing' => 'UTILITY', 'payment' => 'UTILITY', 'announcement' => 'UTILITY',
    ];


    /** Which row of the manual Reminders page a message belongs to, so both stay in step. */
    public const KIND = ['expiring_7' => 'expiring', 'expiring_2' => 'expiring', 'expiring_0' => 'expiring', 'expired_3' => 'expired', 'owing' => 'owing'];

    /* ------------------------------------------------------------------ settings */

    public static function mode(): string
    {
        $m = Setting::get('wa_mode');
        return in_array($m, ['practice', 'live'], true) ? $m : 'off';
    }

    public static function phoneId(): string { return trim((string) Setting::get('wa_phone_id')); }
    public static function wabaId(): string { return trim((string) Setting::get('wa_waba_id')); }
    public static function language(): string { return trim((string) Setting::get('wa_lang')) ?: 'en'; }
    public static function hour(): int { return max(0, min(23, (int) (Setting::get('wa_hour') ?? 9))); }
    public static function version(): string { return config('services.whatsapp.version', 'v21.0'); }

    public static function secret(string $key): string
    {
        $raw = Setting::get($key);
        if (! $raw) return '';
        try { return Crypt::decryptString($raw); } catch (\Throwable) { return ''; }
    }

    public static function putSecret(string $key, ?string $value): void
    {
        if ($value === null || trim($value) === '') return;           // leaving the box empty keeps what is saved
        Setting::put($key, Crypt::encryptString(trim($value)), false);
    }

    public static function token(): string { return self::secret('wa_token'); }

    public static function connected(): bool { return self::phoneId() !== '' && self::token() !== ''; }

    public static function autoOn(string $key): bool { return Setting::get('wa_auto_'.$key) === '1'; }

    /* ------------------------------------------------------------------ people */

    /** Digits only, with Sri Lanka's country code added to numbers that start with 0. Null when there is no usable number. */
    public static function number(Member $m): ?string
    {
        $n = preg_replace('/\D+/', '', (string) $m->user?->phone);
        if ($n === '') return null;
        if (str_starts_with($n, '0')) $n = '94'.substr($n, 1);
        return strlen($n) >= 9 && strlen($n) <= 15 ? $n : null;
    }

    /* ------------------------------------------------------------------ templates */

    /**
     * Turns "Hi {name}, ends on {date}" into WhatsApp's "Hi {{1}}, ends on {{2}}" and records which value goes where.
     * @return array{body:string, order:string[]}
     */
    public static function convert(string $text): array
    {
        $order = [];
        $body = preg_replace_callback('/\{(\w+)\}/', function ($m) use (&$order) {
            if (! isset(Messages::VARS[$m[1]])) return $m[0];
            $i = array_search($m[1], $order, true);
            if ($i === false) { $order[] = $m[1]; $i = count($order) - 1; }
            return '{{'.($i + 1).'}}';
        }, $text);
        return ['body' => $body, 'order' => $order];
    }

    public static function templateName(string $key): string
    {
        return 'fw_'.$key.'_'.substr(md5(self::convert(Messages::body($key))['body'].'|'.self::FOOTER.'|'.self::language()), 0, 6);
    }

    /** @return array<string,array{status:string,key:string,error?:string}> templates we know about, by name */
    public static function known(): array
    {
        return json_decode((string) Setting::get('wa_templates'), true) ?: [];
    }

    private static function remember(string $name, array $info): void
    {
        $all = self::known();
        $all[$name] = $info + ($all[$name] ?? []);
        Setting::put('wa_templates', json_encode($all), false);
    }

    /** none | pending | approved | rejected, for the message's CURRENT wording. */
    public static function status(string $key): string
    {
        return strtolower(self::known()[self::templateName($key)]['status'] ?? 'none');
    }

    public static function approved(string $key): bool { return self::status($key) === 'approved'; }

    public static function markApproved(string $key): void
    {
        self::remember(self::templateName($key), ['status' => 'approved', 'key' => $key]);
    }

    /* ------------------------------------------------------------------ talking to Meta */

    private static function api(): PendingRequest
    {
        return Http::withToken(self::token())->acceptJson()->timeout(20)->baseUrl('https://graph.facebook.com/'.self::version());
    }

    private static function errorOf($res): string
    {
        return (string) ($res->json('error.error_user_msg') ?: $res->json('error.message') ?: ('HTTP '.$res->status()));
    }

    /** Asks Meta who we are connected as. */
    public static function check(): array
    {
        if (! self::connected()) return ['ok' => false, 'error' => 'Add the phone number ID and the access token first.'];
        try {
            $res = self::api()->get('/'.self::phoneId(), ['fields' => 'display_phone_number,verified_name,quality_rating,code_verification_status']);
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'Could not reach WhatsApp: '.$e->getMessage()];
        }
        if (! $res->successful()) return ['ok' => false, 'error' => self::errorOf($res)];
        return ['ok' => true, 'number' => $res->json('display_phone_number'), 'name' => $res->json('verified_name'), 'quality' => $res->json('quality_rating')];
    }

    /** Sends one approved template. Never throws: a failure is returned so the log can record it. */
    public static function send(string $to, string $template, array $params): array
    {
        $payload = ['messaging_product' => 'whatsapp', 'to' => $to, 'type' => 'template',
            'template' => ['name' => $template, 'language' => ['code' => self::language()]]];
        if ($params) {
            $payload['template']['components'] = [['type' => 'body', 'parameters' => array_map(fn ($p) => ['type' => 'text', 'text' => $p], array_values($params))]];
        }
        try {
            $res = self::api()->post('/'.self::phoneId().'/messages', $payload);
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'Could not reach WhatsApp: '.$e->getMessage()];
        }
        if (! $res->successful()) return ['ok' => false, 'error' => self::errorOf($res), 'code' => $res->json('error.code')];
        return ['ok' => true, 'wamid' => $res->json('messages.0.id')];
    }

    /** Sends the CURRENT wording of a message to Meta for approval. */
    public static function submit(string $key): array
    {
        if (self::wabaId() === '' || self::token() === '') return ['ok' => false, 'error' => 'Add the WhatsApp Business Account ID and the access token first.'];
        $conv = self::convert(Messages::body($key));
        $name = self::templateName($key);
        $sample = Messages::sample();
        $body = ['type' => 'BODY', 'text' => $conv['body']];
        if ($conv['order']) $body['example'] = ['body_text' => [array_map(fn ($v) => (string) $sample[$v], $conv['order'])]];
        try {
            $res = self::api()->post('/'.self::wabaId().'/message_templates', [
                'name' => $name, 'language' => self::language(), 'category' => self::AUTO[$key] ?? 'UTILITY', 'components' => [$body, ['type' => 'FOOTER', 'text' => self::FOOTER]],
            ]);
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'Could not reach WhatsApp: '.$e->getMessage()];
        }
        if (! $res->successful()) {
            self::remember($name, ['status' => 'rejected', 'key' => $key, 'error' => self::errorOf($res)]);
            return ['ok' => false, 'error' => self::errorOf($res)];
        }
        self::remember($name, ['status' => strtolower((string) ($res->json('status') ?: 'pending')), 'key' => $key]);
        return ['ok' => true, 'status' => $res->json('status')];
    }

    /** Pulls the latest approved / pending / rejected state of our templates from Meta. */
    public static function syncStatuses(): array
    {
        if (self::wabaId() === '' || self::token() === '') return ['ok' => false, 'error' => 'Add the WhatsApp Business Account ID and the access token first.'];
        try {
            $res = self::api()->get('/'.self::wabaId().'/message_templates', ['fields' => 'name,status,rejected_reason', 'limit' => 200]);
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'Could not reach WhatsApp: '.$e->getMessage()];
        }
        if (! $res->successful()) return ['ok' => false, 'error' => self::errorOf($res)];
        $n = 0;
        foreach ((array) $res->json('data') as $t) {
            if (! str_starts_with((string) ($t['name'] ?? ''), 'fw_')) continue;
            $key = collect(array_keys(self::AUTO))->first(fn ($k) => str_starts_with($t['name'], 'fw_'.$k.'_'));
            if (! $key) continue;
            $err = $t['rejected_reason'] ?? null;
            self::remember($t['name'], ['status' => strtolower((string) $t['status']), 'key' => $key] + ($err && $err !== 'NONE' ? ['error' => $err] : []));
            $n++;
        }
        return ['ok' => true, 'count' => $n];
    }

    /* ------------------------------------------------------------------ sending a message to a member */

    /** WhatsApp wants plain one-line text for each fill-in value. */
    private static function clean($v): string
    {
        return trim((string) preg_replace('/\s+/', ' ', (string) $v)) ?: '-';
    }

    /**
     * Sends (or, in practice mode, pretends to send) one message and writes it to the log.
     * The caller has already decided this message is due; consent and approval are checked again here as a safety net.
     */
    public static function deliver(Member $m, string $key, string $anchor, array $extraVars = []): MessageLog
    {
        $mode = self::mode();
        $vars = Messages::vars($m, $extraVars);
        $conv = self::convert(Messages::body($key));
        $to = self::number($m);
        $attempts = MessageLog::where(['member_id' => $m->id, 'key' => $key, 'anchor' => $anchor, 'status' => 'failed'])->count() + 1;
        $log = MessageLog::create([
            'member_id' => $m->id, 'key' => $key, 'anchor' => $anchor, 'to' => $to, 'template' => self::templateName($key),
            'text' => Messages::fill(Messages::body($key), $vars), 'status' => 'queued', 'attempts' => $attempts,
        ]);

        if (! $m->whatsapp_optin) return tap($log)->update(['status' => 'failed', 'error' => 'This member has not agreed to WhatsApp messages.', 'attempts' => 3]);
        if (! $to) return tap($log)->update(['status' => 'failed', 'error' => 'No usable phone number.', 'attempts' => 3]);
        if ($mode === 'practice') return tap($log)->update(['status' => 'practice']);
        if ($mode !== 'live') return tap($log)->update(['status' => 'failed', 'error' => 'Automatic sending is switched off.', 'attempts' => 3]);
        if (! self::approved($key)) return tap($log)->update(['status' => 'failed', 'error' => 'This wording is not approved by WhatsApp yet.', 'attempts' => 3]);

        $res = self::send($to, self::templateName($key), array_map(fn ($v) => self::clean($vars[$v] ?? ''), $conv['order']));
        if (! $res['ok']) {
            // a wrong number or a blocked recipient will not get better by retrying
            $final = in_array($res['code'] ?? null, [131026, 131049, 131047, 470], true);
            return tap($log)->update(['status' => 'failed', 'error' => $res['error'], 'attempts' => $final ? 3 : $attempts]);
        }
        $log->update(['status' => 'sent', 'wamid' => $res['wamid']]);
        if (isset(self::KIND[$key])) Reminder::create(['member_id' => $m->id, 'kind' => self::KIND[$key], 'sent_on' => today()]);   // the manual list now shows "Sent"
        return $log;
    }
}
