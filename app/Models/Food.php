<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One food in the Sri Lankan catalogue the meal planner draws from. Calories and protein are per household serving (estimates). */
class Food extends Model
{
    protected $table = 'foods';
    protected $guarded = [];
    protected $casts = ['active' => 'boolean', 'whole_grain' => 'boolean', 'limited' => 'boolean', 'is_dummy' => 'boolean', 'per_serving' => 'float'];

    public const ALLERGENS = ['egg', 'dairy', 'fish', 'shellfish', 'nuts', 'gluten', 'soy', 'sesame'];
    public const DIETS = ['vegan' => 'Plant only', 'dairy' => 'Contains milk', 'egg' => 'Contains egg', 'meat' => 'Fish, chicken or meat'];
    public const MEAL_TAGS = ['breakfast' => 'Breakfast', 'main' => 'Lunch and dinner', 'snack' => 'Snacks'];

    /** Food group names as shown to people. */
    public const SHORT = ['cereal' => 'Rice and cereals', 'veg' => 'Vegetables and leaves', 'fruit' => 'Fruit', 'pulse' => 'Pulses', 'protein' => 'Fish or lean meat',
        'egg' => 'Egg', 'dairy' => 'Milk or curd', 'nuts' => 'Nuts and seeds', 'coconut' => 'Coconut', 'oil' => 'Oil'];

    public function allergenList(): array { return $this->csv($this->allergens); }
    public function tagList(): array { return $this->csv($this->tags); }
    public function mealTagList(): array { return $this->csv($this->meal_tags) ?: ['main']; }

    private function csv(?string $v): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $v))));
    }

    public function kcalFor(int|float $servings): int { return (int) round($this->kcal_serving * $servings); }
    public function proteinFor(int|float $servings): float { return round($this->protein_serving * $servings, 1); }
    public function qty(int|float $servings): string { return self::measure($this->per_serving, $this->unit, $this->unit_plural, $servings); }

    /** "1 1/2 cups", "3 tbsp", "30 g": the household amount for a number of servings. */
    public static function measure(float $per, string $unit, ?string $plural, int|float $servings): string
    {
        $total = round($per * $servings * 12) / 12;                      // to the nearest twelfth, so thirds and quarters come out clean
        $whole = (int) floor($total + 1e-9);
        $frac = $total - $whole;
        $sym = null;
        foreach ([[0, ''], [1 / 4, '¼'], [1 / 3, '⅓'], [1 / 2, '½'], [2 / 3, '⅔'], [3 / 4, '¾']] as [$v, $s]) if (abs($frac - $v) < 0.02) { $sym = $s; break; }
        if ($sym === null) return rtrim(rtrim(number_format($total, 1), '0'), '.').' '.($total > 1 ? ($plural ?: $unit) : $unit);
        $num = $whole > 0 ? $whole.($sym ? ' '.$sym : '') : ($sym ?: '0');
        return $num.' '.($total > 1 ? ($plural ?: $unit) : $unit);
    }
}
