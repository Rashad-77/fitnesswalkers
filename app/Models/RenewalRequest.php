<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RenewalRequest extends Model
{
    protected $guarded = [];
    protected $casts = ['is_dummy' => 'boolean'];

    public function member() { return $this->belongsTo(Member::class); }
    public function plan() { return $this->belongsTo(Plan::class); }
}
