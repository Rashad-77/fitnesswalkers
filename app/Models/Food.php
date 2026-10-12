<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Food extends Model
{
    protected $table = 'foods';
    protected $guarded = [];
    protected $casts = ['price_updated_on' => 'date', 'price_locked' => 'boolean', 'is_dummy' => 'boolean'];

    public function allergenList(): array
    {
        return array_filter(array_map('trim', explode(',', (string) $this->allergens)));
    }

    public function costFor(float $grams): float
    {
        return round($this->price_per_kg * $grams / 1000, 2);
    }
}
