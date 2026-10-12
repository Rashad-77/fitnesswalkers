<?php

namespace App\Http\Controllers\Admin;

/** Cleans the exercise rows submitted by the workout plan / template editor. */
class PlanItems
{
    /** @return array<int,array{day_no:int,exercise_id:int,sets:int,reps:string}> */
    public static function clean(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            if (empty($row['exercise_id']) || ! \App\Models\Exercise::whereKey($row['exercise_id'])->exists()) continue;
            $out[] = [
                'day_no' => max(1, min(7, (int) ($row['day_no'] ?? 1))),
                'exercise_id' => (int) $row['exercise_id'],
                'sets' => max(1, min(10, (int) ($row['sets'] ?? 3))),
                'reps' => mb_substr(trim((string) ($row['reps'] ?? '10')) ?: '10', 0, 20),
            ];
        }
        usort($out, fn ($a, $b) => $a['day_no'] <=> $b['day_no']);
        return $out;
    }
}
