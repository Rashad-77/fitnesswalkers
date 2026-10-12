<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Membership extends Model
{
    protected $guarded = [];
    protected $casts = ['starts_on' => 'date', 'ends_on' => 'date', 'price' => 'decimal:2', 'is_dummy' => 'boolean'];

    public function member(): BelongsTo { return $this->belongsTo(Member::class); }
    public function plan(): BelongsTo { return $this->belongsTo(Plan::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }

    public function paid(): float { return (float) $this->payments()->sum('amount'); }
    public function balance(): float { return max(0, (float) $this->price - $this->paid()); }
}
