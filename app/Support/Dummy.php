<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/** Single source of truth for which tables carry placeholder (dummy) rows. */
class Dummy
{
    /** Deletion order (children before parents). */
    public const TABLES = [
        'attendances', 'enquiries', 'diet_plans', 'goals', 'body_logs', 'workout_logs', 'workout_plans', 'plan_requests',
        'renewal_requests', 'payments', 'memberships', 'members', 'workout_templates', 'exercises',
        'equipment', 'foods', 'announcements', 'expenses', 'plans', 'users', 'settings',
    ];

    public static function counts(): array
    {
        $out = [];
        foreach (self::TABLES as $t) {
            $out[$t] = DB::table($t)->where('is_dummy', true)->count();
        }
        return $out;
    }

    public static function total(): int
    {
        return array_sum(self::counts());
    }

    /** @return array<string,int> rows removed per table */
    public static function purge(bool $keepSettings = true): array
    {
        $removed = [];
        foreach (self::TABLES as $t) {
            if ($keepSettings && $t === 'settings') continue;
            try {
                $removed[$t] = DB::table($t)->where('is_dummy', true)->delete();
            } catch (\Throwable $e) {
                // Still referenced by a real row (e.g. a real payment on a dummy plan): leave it and report.
                $removed[$t] = 0;
            }
        }
        return $removed;
    }
}
