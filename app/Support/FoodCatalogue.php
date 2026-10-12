<?php

namespace App\Support;

use App\Models\Food;

/** Loads the built-in Sri Lankan food catalogue (database/seeders/data/foods.php) into the foods table. */
class FoodCatalogue
{
    public static function rows(): array
    {
        return require database_path('seeders/data/foods.php');
    }

    /** Adds any built-in food that is missing (matched by name). Never changes or removes foods the owner has edited. */
    public static function sync(): int
    {
        $have = Food::pluck('name')->map(fn ($n) => mb_strtolower($n))->flip();
        $added = 0;
        foreach (self::rows() as $r) {
            if ($have->has(mb_strtolower($r['name']))) continue;
            Food::create($r + ['active' => true, 'is_dummy' => false]);
            $added++;
        }
        return $added;
    }
}
