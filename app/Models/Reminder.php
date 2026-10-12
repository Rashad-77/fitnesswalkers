<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reminder extends Model
{
    protected $guarded = [];
    protected $casts = ['sent_on' => 'date'];

    public static function lastSent(int $memberId, string $kind): ?\Carbon\Carbon
    {
        return static::where('member_id', $memberId)->where('kind', $kind)->max('sent_on')
            ? \Carbon\Carbon::parse(static::where('member_id', $memberId)->where('kind', $kind)->max('sent_on')) : null;
    }
}
