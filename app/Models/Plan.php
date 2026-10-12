<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $guarded = [];
    protected $casts = ['price' => 'decimal:2', 'active' => 'boolean', 'is_dummy' => 'boolean'];
}
