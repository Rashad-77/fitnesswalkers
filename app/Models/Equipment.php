<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Equipment extends Model
{
    protected $table = 'equipment';
    protected $guarded = [];
    public function exercises() { return $this->hasMany(Exercise::class); }

    protected $casts = ['out_of_order' => 'boolean', 'is_dummy' => 'boolean'];
}
