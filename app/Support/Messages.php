<?php

namespace App\Support;

use App\Models\{Member, Setting};

/**
 * The WhatsApp messages the owner sends. The wording lives here as defaults and can be changed in Settings > Messages.
 * Placeholders in {curly brackets} are filled in for each member.
 */
class Messages
{
    public const VARS = [
        'name' => 'Member first name', 'gym' => 'Gym name', 'package' => 'Package name', 'date' => 'Date the package ends',
        'days' => 'Days left', 'amount' => 'Amount still to pay (or paid, on a receipt)', 'number' => 'Receipt number', 'phone' => 'Gym phone number', 'message' => 'The text of your announcement',
    ];

    public const GROUPS = [
        'Membership ending' => ['expiring_7', 'expiring_2', 'expiring_0'],
        'After a membership ends' => ['expired_3', 'expired_30'],
        'Money and receipts' => ['owing', 'payment'],
        'Announcements' => ['announcement'],
        'Staying in touch' => ['quiet', 'birthday', 'plan_ready'],
        'Welcome and enquiries' => ['welcome', 'enquiry'],
    ];

    /** key => [title, when it is used, default text, placeholders that make sense for it] */
    public static function all(): array
    {
        return [
            'expiring_7' => ['A week before it ends', 'Sent when 3 to 7 days are left.', 'Hi {name}, your {package} membership at {gym} ends on {date}. Drop by the gym or call us on {phone} to renew. See you soon!', ['name', 'gym', 'package', 'date', 'days', 'phone']],
            'expiring_2' => ['Two days before it ends', 'Sent when 1 or 2 days are left.', 'Hi {name}, a quick reminder that your membership ends on {date}. Renew at the gym and keep your routine going.', ['name', 'gym', 'package', 'date', 'days']],
            'expiring_0' => ['The day it ends', 'Sent on the last day.', 'Hi {name}, your membership ends today. Renew any time to keep your plan and progress. Call us on {phone} if you have any questions.', ['name', 'gym', 'package', 'date', 'phone']],
            'expired_3' => ['Just after it ended', 'Sent in the first two weeks after the end date.', 'Hi {name}, your {package} membership at {gym} ended on {date}. Your plan and progress are saved. Drop by the gym or call us on {phone} to renew.', ['name', 'gym', 'package', 'date', 'phone']],
            'expired_30' => ['A few weeks after it ended', 'Sent more than two weeks after the end date.', 'Hi {name}, we miss you at {gym}. Come back any time and we will get you going again. Call us on {phone} or just drop in.', ['name', 'gym', 'date', 'phone']],
            'owing' => ['Money still to pay', 'Sent to members with a balance.', 'Hi {name}, a friendly reminder that {amount} is still to pay for your {package} membership. You can pay at the gym by cash or bank transfer. Thank you!', ['name', 'gym', 'package', 'amount']],
            'payment' => ['Receipt after a payment', 'Sent right after you record a payment (not for old payments entered later).', 'Thank you {name}! We received {amount} on {date}. Receipt {number}.', ['name', 'gym', 'amount', 'date', 'number']],
            'announcement' => ['Announcement (for example a closure)', 'Sent when you tick "Also send on WhatsApp" on a new announcement, to active members who agreed.', 'Hi {name}, a notice from {gym}: {message} Call us on {phone} if you have any questions.', ['name', 'gym', 'message', 'phone']],
            'quiet' => ['Not seen for a while', 'Sent when a member has not trained for two weeks.', 'Hi {name}, we have not seen you at the gym for a couple of weeks. Your plan is ready whenever you are. Call us on {phone} if you need anything.', ['name', 'gym', 'phone']],
            'birthday' => ['Birthday', 'Sent in the week of a birthday.', 'Happy birthday {name}! Hope you have a great day. See you at the gym.', ['name', 'gym']],
            'plan_ready' => ['Workout plan ready', 'Sent after you approve a plan.', 'Hi {name}, your new workout plan is ready in the app.', ['name', 'gym']],
            'welcome' => ['New member', 'Sent when someone joins.', 'Welcome to {gym}, {name}! Your member page is ready. Sign in to see your plan, check in at the gym door and track your progress.', ['name', 'gym', 'package']],
            'enquiry' => ['Following up an enquiry', 'Sent to people who asked about joining.', 'Hi {name}, thanks for asking about {gym}. Happy to answer any questions. Would you like to come and see the gym this week?', ['name', 'gym']],
        ];
    }

    public static function default(string $key): string
    {
        return self::all()[$key][2] ?? '';
    }

    public static function body(string $key): string
    {
        $v = trim((string) Setting::get('msg_'.$key));
        return $v !== '' ? $v : self::default($key);
    }

    public static function render(string $key, array $vars = []): string
    {
        return self::fill(self::body($key), $vars);
    }

    public static function fill(string $text, array $vars): string
    {
        $map = [];
        foreach ($vars as $k => $v) $map['{'.$k.'}'] = (string) $v;
        return strtr($text, $map);
    }

    /** Everything a template might ask for, worked out from the member. */
    public static function vars(Member $m, array $extra = []): array
    {
        $ms = $m->currentMembership;
        return $extra + [
            'name' => explode(' ', trim($m->user->name))[0],
            'gym' => Setting::get('gym_name', 'FitnessWalkers'),
            'package' => $ms?->plan?->name ?? 'membership',
            'date' => $ms?->ends_on?->format('d M Y') ?? '',
            'days' => max(0, (int) $m->daysLeft()),
            'message' => '',
            'amount' => rs($m->balanceDue()),
            'phone' => Setting::get('gym_phone', ''),
            'number' => '',
        ];
    }

    public static function forMember(string $key, Member $m, array $extra = []): string
    {
        return self::render($key, self::vars($m, $extra));
    }

    /** Which "ending" message fits the days left. */
    public static function expiringKey(int $days): string
    {
        return $days <= 0 ? 'expiring_0' : ($days <= 2 ? 'expiring_2' : 'expiring_7');
    }

    public static function expiredKey(int $daysAgo): string
    {
        return $daysAgo <= 14 ? 'expired_3' : 'expired_30';
    }

    /** Example values for the live preview in Settings. */
    public static function sample(): array
    {
        return ['name' => 'Kasun', 'gym' => Setting::get('gym_name', 'FitnessWalkers'), 'package' => 'Monthly', 'date' => now()->addDays(5)->format('d M Y'),
            'days' => 5, 'amount' => 'Rs. 2,500', 'number' => 'R-'.now()->year.'-00012', 'phone' => Setting::get('gym_phone', ''),
            'message' => 'The gym will be closed on Friday 10 Oct for the holiday and reopens on Saturday at 6 AM.'];
    }
}
