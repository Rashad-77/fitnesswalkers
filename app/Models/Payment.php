<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $guarded = [];
    protected $casts = ['paid_on' => 'date', 'amount' => 'decimal:2', 'is_dummy' => 'boolean'];

    public function member(): BelongsTo { return $this->belongsTo(Member::class); }
    public function membership(): BelongsTo { return $this->belongsTo(Membership::class); }

    public static function nextReceiptNo(): string
    {
        $n = (int) static::max('id') + 1;
        do {
            $no = 'R-'.now()->format('Y').'-'.str_pad((string) $n++, 5, '0', STR_PAD_LEFT);
        } while (static::where('receipt_no', $no)->exists());
        return $no;
    }
}
