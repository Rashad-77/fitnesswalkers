<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Exercise extends Model
{
    protected $guarded = [];
    protected $casts = ['is_dummy' => 'boolean'];

    public function equipment(): BelongsTo { return $this->belongsTo(Equipment::class); }

    /** Exercises the gym can actually offer: no equipment needed, or equipment that is not out of order. */
    public function scopeAvailable($q)
    {
        return $q->where(fn ($w) => $w->whereNull('equipment_id')
            ->orWhereHas('equipment', fn ($e) => $e->where('out_of_order', false)));
    }

    public function isAvailable(): bool
    {
        return ! $this->equipment || ! $this->equipment->out_of_order;
    }
}
