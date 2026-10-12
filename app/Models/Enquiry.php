<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enquiry extends Model
{
    public const STATUSES = ['new' => 'New', 'contacted' => 'Contacted', 'joined' => 'Joined', 'lost' => 'Not joining'];
    public const SOURCES = ['website' => 'Website', 'walk-in' => 'Walk-in', 'phone' => 'Phone / WhatsApp', 'other' => 'Other'];

    protected $guarded = [];
    protected $casts = ['is_dummy' => 'boolean'];

    public function member(): BelongsTo { return $this->belongsTo(Member::class); }

    public function whatsappUrl(): ?string
    {
        $phone = preg_replace('/\D+/', '', (string) $this->phone);
        if ($phone === '') return null;
        if (str_starts_with($phone, '0')) $phone = '94'.substr($phone, 1);
        $gym = Setting::get('gym_name', 'FitnessWalkers');
        return 'https://wa.me/'.$phone.'?text='.rawurlencode("Hi {$this->name}, thanks for your interest in {$gym}! How can we help you get started?");
    }
}
