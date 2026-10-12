<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $guarded = [];
    protected $casts = ['spent_on' => 'date', 'amount' => 'decimal:2', 'is_dummy' => 'boolean'];
}
