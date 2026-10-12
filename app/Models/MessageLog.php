<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageLog extends Model
{
    protected $guarded = [];
    protected $casts = ['is_dummy' => 'boolean'];

    public function member(): BelongsTo { return $this->belongsTo(Member::class); }

    /** WhatsApp accepted it. Nothing comes back after that (the system never receives anything). */
    public const DELIVERED = ['sent'];
}
