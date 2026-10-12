<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DishItem extends Model
{
    protected $guarded = [];
    protected $casts = ['servings' => 'float', 'scales' => 'boolean'];
    public $timestamps = false;

    public function dish(): BelongsTo { return $this->belongsTo(Dish::class); }
    public function food(): BelongsTo { return $this->belongsTo(Food::class); }
}
