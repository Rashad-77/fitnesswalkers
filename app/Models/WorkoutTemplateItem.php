<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutTemplateItem extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    public function exercise(): BelongsTo { return $this->belongsTo(Exercise::class); }
}
