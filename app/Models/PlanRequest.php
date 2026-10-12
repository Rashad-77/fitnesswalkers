<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PlanRequest extends Model
{
    protected $guarded = [];
    protected $casts = ['is_dummy' => 'boolean'];

    public function member(): BelongsTo { return $this->belongsTo(Member::class); }
    public function plan(): HasOne { return $this->hasOne(WorkoutPlan::class); }
}
