<?php

namespace App\Support;

use App\Models\{Dish, DishItem, Food};

/** Loads the built-in dish library (database/seeders/data/dishes.php) into the dishes tables. */
class DishCatalogue
{
    public static function rows(): array
    {
        return require database_path('seeders/data/dishes.php');
    }

    /** Adds any built-in dish that is missing (matched by name). Never changes or removes dishes the owner has edited. */
    public static function sync(): int
    {
        $foods = Food::all()->keyBy(fn ($f) => mb_strtolower($f->name));
        $have = Dish::pluck('name')->map(fn ($n) => mb_strtolower($n))->flip();
        $added = 0;
        foreach (self::rows() as $r) {
            if ($have->has(mb_strtolower($r['name']))) continue;
            $items = [];
            foreach ($r['items'] as $i => [$name, $servings, $code]) {
                $food = $foods[mb_strtolower($name)] ?? null;
                if (! $food) continue;                                        // a food the owner deleted: the dish simply goes without it
                $items[] = ['food_id' => $food->id, 'servings' => $servings, 'sort' => $i,
                    'role' => $code === 'm' ? 'main' : ($code === 'd' ? 'dessert' : 'dish'), 'scales' => in_array($code, ['m', 's', 'd'], true)];
            }
            if (! $items) continue;
            $dish = Dish::create(['name' => $r['name'], 'local_name' => $r['local'], 'kind' => $r['kind'], 'slots' => $r['slots'], 'tags' => $r['tags'] ?: null,
                'prep_minutes' => $r['mins'], 'method' => $r['method'], 'active' => true, 'is_dummy' => false]);
            $dish->items()->createMany($items);
            $dish->refreshFromItems();
            $added++;
        }
        return $added;
    }
}
