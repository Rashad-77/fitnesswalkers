<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutLog extends Model
{
    protected $guarded = [];
    protected $casts = ['logged_on' => 'date', 'weight_kg' => 'decimal:1', 'is_dummy' => 'boolean'];

    public function exercise(): BelongsTo { return $this->belongsTo(Exercise::class); }
    public function member(): BelongsTo { return $this->belongsTo(Member::class); }
}
