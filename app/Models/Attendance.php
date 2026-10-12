<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $guarded = [];
    protected $casts = ['visited_on' => 'date:Y-m-d', 'is_dummy' => 'boolean'];

    public function member(): BelongsTo { return $this->belongsTo(Member::class); }

    /** Records one visit per member per day. Returns true when a new visit was recorded. */
    public static function record(Member $member, string $source): bool
    {
        $day = today()->toDateString();
        if (static::where('member_id', $member->id)->whereDate('visited_on', $day)->exists()) return false;
        try {
            static::create(['member_id' => $member->id, 'visited_on' => $day, 'source' => $source]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            return false; // two scans at the same moment
        }
        return true;
    }
}
