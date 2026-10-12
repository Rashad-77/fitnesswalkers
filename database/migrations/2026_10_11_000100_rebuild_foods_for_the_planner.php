<?php

use App\Support\FoodCatalogue;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

/**
 * The meal planner now follows Sri Lanka's food-based dietary guidelines: it plans in servings of foods that guideline names.
 * That replaces the old per-100g and price columns. Prices are gone entirely. Old plans were built differently and are removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('diet_plans')->delete();
        Schema::dropIfExists('foods');
        Schema::create('foods', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('local_name')->nullable();
            $t->string('grp');                                  // cereal | veg | fruit | pulse | protein | egg | dairy | nuts | coconut | oil
            $t->string('sub')->nullable();                      // leaf, vita, other / high, medium, low / fish, poultry, red, dried ...
            $t->decimal('per_serving', 6, 2);                   // how much of the unit is one serving, e.g. 0.5
            $t->string('unit');                                 // cup, tbsp, g, hopper ...
            $t->string('unit_plural')->nullable();
            $t->decimal('serving_g', 6, 1)->nullable();
            $t->unsignedSmallInteger('kcal_serving');           // approximate
            $t->decimal('protein_serving', 4, 1);               // approximate
            $t->string('diet')->default('vegan');               // vegan | dairy | egg | meat
            $t->string('allergens')->nullable();                // csv: egg, dairy, fish, shellfish, nuts, gluten, soy, sesame
            $t->string('tags')->nullable();                     // csv: beef, pork
            $t->string('meal_tags')->default('main');           // csv: breakfast, main, snack
            $t->boolean('whole_grain')->default(false);
            $t->boolean('limited')->default(false);             // eat less of it (white bread, white rice...)
            $t->boolean('active')->default(true);               // the owner can switch off anything not sold locally
            $t->string('note')->nullable();
            $t->boolean('is_dummy')->default(false);
            $t->timestamps();
            $t->index('grp');
        });
        FoodCatalogue::sync();
    }

    public function down(): void
    {
        Schema::dropIfExists('foods');
    }
};
