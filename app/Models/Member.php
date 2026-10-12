<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Member extends Model
{
    protected $guarded = [];
    protected $casts = ['dob' => 'date', 'joined_on' => 'date', 'frozen' => 'boolean', 'frozen_at' => 'date', 'is_dummy' => 'boolean',
        'health_answers' => 'array', 'health_flag' => 'boolean', 'waiver_accepted_at' => 'datetime'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function memberships(): HasMany { return $this->hasMany(Membership::class)->orderByDesc('ends_on'); }
    public function payments(): HasMany { return $this->hasMany(Payment::class)->orderByDesc('paid_on')->orderByDesc('id'); }
    public function currentMembership(): HasOne { return $this->hasOne(Membership::class)->latestOfMany('ends_on'); }
    public function bodyLogs(): HasMany { return $this->hasMany(BodyLog::class)->orderBy('logged_on'); }
    public function goals(): HasMany { return $this->hasMany(Goal::class); }
    public function workoutLogs(): HasMany { return $this->hasMany(WorkoutLog::class); }
    public function planRequests(): HasMany { return $this->hasMany(PlanRequest::class)->latest(); }
    public function workoutPlans(): HasMany { return $this->hasMany(WorkoutPlan::class)->latest(); }
    public function renewalRequests(): HasMany { return $this->hasMany(RenewalRequest::class)->latest(); }
    public function dietPlans(): HasMany { return $this->hasMany(DietPlan::class)->latest(); }

    public function attendances(): HasMany { return $this->hasMany(Attendance::class)->orderByDesc('visited_on'); }

    /** Latest day the member came in, from check-ins or logged workouts (Y-m-d) or null. */
    public function lastVisit(): ?string
    {
        $a = $this->attendances()->max('visited_on');
        $w = $this->workoutLogs()->max('logged_on');
        $best = collect([$a, $w])->filter()->map(fn ($d) => substr((string) $d, 0, 10))->max();
        return $best ?: null;
    }

    public function visitsThisMonth(): int
    {
        return $this->attendances()->where('visited_on', '>=', now()->startOfMonth()->toDateString())->count();
    }

    public function checkedInToday(): bool
    {
        return $this->attendances()->whereDate('visited_on', today()->toDateString())->exists();
    }

    public function approvedPlan(): ?WorkoutPlan
    {
        return $this->workoutPlans()->where('status', 'approved')->with('items.exercise')->first();
    }

    /** active | expiring (7 days or fewer) | expired | frozen | none */
    public function status(): string
    {
        if ($this->frozen) return 'frozen';
        $m = $this->currentMembership;
        if (! $m) return 'none';
        $days = $this->daysLeft();
        if ($days < 0) return 'expired';
        if ($days <= 7) return 'expiring';
        return 'active';
    }

    public function daysLeft(): ?int
    {
        $m = $this->currentMembership;
        return $m ? (int) now()->startOfDay()->diffInDays($m->ends_on->startOfDay(), false) : null;
    }

    public function balanceDue(): float
    {
        return (float) $this->memberships->sum(fn ($m) => $m->balance());
    }

    public function age(): ?int
    {
        return $this->dob?->age;
    }

    public function whatsappUrl(string $message): ?string
    {
        $phone = preg_replace('/\D+/', '', (string) $this->user?->phone);
        if ($phone === '') return null;
        if (str_starts_with($phone, '0')) $phone = '94'.substr($phone, 1); // Sri Lanka
        return 'https://wa.me/'.$phone.'?text='.rawurlencode($message);
    }

    public static function nextNumber(): string
    {
        $n = (int) static::max('id') + 1;
        do {
            $no = 'FW-'.str_pad((string) $n++, 4, '0', STR_PAD_LEFT);
        } while (static::where('member_no', $no)->exists());
        return $no;
    }
}
