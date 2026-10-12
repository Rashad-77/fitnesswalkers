<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Plan extends Model
{
    protected $guarded = [];
    protected $casts = ['price' => 'decimal:2', 'active' => 'boolean', 'featured' => 'boolean', 'is_dummy' => 'boolean'];

    /** The features written for this package, one per line. */
    public function featureList(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $this->features))));
    }

    /** "quarterly": used in the link from the pricing page to the enquiry form. */
    public function slug(): string { return Str::slug($this->name); }

    /** Price for a month (30 days) of this package. */
    public function perMonth(): float { return round((float) $this->price / max(1, $this->duration_days) * 30); }

    /**
     * What you save compared with paying for the shortest package again and again, or null when there is no saving.
     * For example 3 x Rs. 5,000 - Rs. 13,500 = Rs. 1,500.
     */
    public function savingVsMonthly(): ?int
    {
        $base = static::where('active', true)->orderBy('duration_days')->first();
        if (! $base || $base->id === $this->id) return null;
        $s = (float) $base->price / max(1, $base->duration_days) * $this->duration_days - (float) $this->price;
        if ($s < 10) return null;
        return (int) ($s >= 1000 ? round($s / 100) * 100 : round($s / 10) * 10);
    }
}
