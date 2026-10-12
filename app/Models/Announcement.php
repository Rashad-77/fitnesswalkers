<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $guarded = [];
    protected $casts = ['published_at' => 'datetime', 'is_dummy' => 'boolean'];
}
