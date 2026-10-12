<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DietCheckin extends Model
{
    protected $guarded = [];
    protected $casts = ['week_of' => 'date', 'weight_kg' => 'float', 'is_dummy' => 'boolean'];

    public function member(): BelongsTo { return $this->belongsTo(Member::class); }
}
