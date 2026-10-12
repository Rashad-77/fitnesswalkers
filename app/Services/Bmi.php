<?php

namespace App\Services;

class Bmi
{
    public static function calc(float $weightKg, float $heightCm): float
    {
        $m = $heightCm / 100;
        return $m > 0 ? round($weightKg / ($m * $m), 1) : 0.0;
    }

    /** Rough guide only. BMI can't tell muscle from fat. */
    public static function category(float $bmi): string
    {
        return match (true) {
            $bmi < 18.5 => 'Underweight range',
            $bmi < 23 => 'Healthy range',     // Asian cut-offs (WHO Asia-Pacific)
            $bmi < 27.5 => 'Overweight range',
            default => 'Obese range',
        };
    }
}
