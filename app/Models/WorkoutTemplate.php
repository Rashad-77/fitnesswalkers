<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkoutTemplate extends Model
{
    protected $guarded = [];
    protected $casts = ['is_dummy' => 'boolean'];

    public function items(): HasMany { return $this->hasMany(WorkoutTemplateItem::class)->orderBy('day_no')->orderBy('id'); }
}
