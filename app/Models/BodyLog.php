<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BodyLog extends Model
{
    protected $guarded = [];
    protected $casts = ['logged_on' => 'date', 'is_dummy' => 'boolean'];
}
