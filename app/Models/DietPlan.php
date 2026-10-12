<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DietPlan extends Model
{
    protected $guarded = [];
    protected $casts = ['inputs' => 'array', 'plan' => 'array', 'active' => 'boolean', 'is_dummy' => 'boolean'];

    public function member(): BelongsTo { return $this->belongsTo(Member::class); }
}
