<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $guarded = [];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public static function record(string $action, ?string $subject = null, ?string $details = null): void
    {
        static::create(['user_id' => auth()->id(), 'action' => $action, 'subject' => $subject, 'details' => $details]);
    }
}
