<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Goal extends Model
{
    protected $guarded = [];
    protected $casts = ['target_date' => 'date', 'done' => 'boolean', 'is_dummy' => 'boolean'];
}
