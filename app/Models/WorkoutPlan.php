<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkoutPlan extends Model
{
    protected $guarded = [];
    protected $casts = ['approved_at' => 'datetime', 'is_dummy' => 'boolean'];

    public function member(): BelongsTo { return $this->belongsTo(Member::class); }
    public function items(): HasMany { return $this->hasMany(WorkoutPlanItem::class)->orderBy('day_no')->orderBy('id'); }
}
