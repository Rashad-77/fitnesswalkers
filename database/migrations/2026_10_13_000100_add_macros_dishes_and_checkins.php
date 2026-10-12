<?php

use App\Models\Food;
use App\Support\DishCatalogue;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The target-first meal planner: carbs and fat for every food, a library of dishes made from those foods,
 * and the member's weekly check-ins that adjust the plan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('foods', function (Blueprint $t) {
            $t->decimal('carbs_serving', 6, 1)->default(0);     // approximate grams per serving
            $t->decimal('fat_serving', 6, 1)->default(0);
        });
        foreach (Food::all() as $f) {
            $m = Food::estimateMacros($f->grp, $f->sub, $f->name, $f->kcal_serving, (float) $f->protein_serving);
            $f->forceFill(['carbs_serving' => $m['carbs'], 'fat_serving' => $m['fat']])->save();
        }

        Schema::create('dishes', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('local_name')->nullable();
            $t->string('kind')->default('meal');                // meal | snack | dessert
            $t->string('slots')->default('lunch,dinner');       // csv: breakfast, lunch, dinner, snack
            $t->string('tags')->nullable();                     // csv: quick, batch, weekend
            $t->unsignedSmallInteger('prep_minutes')->default(30);
            $t->text('method')->nullable();
            $t->string('needs')->nullable();                    // csv of what its foods need, besides plants: dairy, egg, meat (empty = vegan)
            $t->string('allergens')->nullable();                // csv, from its foods
            $t->string('avoid_tags')->nullable();               // csv: beef, pork (from its foods)
            $t->boolean('active')->default(true);
            $t->boolean('is_dummy')->default(false);
            $t->timestamps();
        });
        Schema::create('dish_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('dish_id')->constrained()->cascadeOnDelete();
            $t->foreignId('food_id')->constrained('foods')->cascadeOnDelete();
            $t->decimal('servings', 5, 2);                      // servings of that food in one normal portion of the dish
            $t->string('role')->default('dish');                // main (rice, roti...) | dish (curries, sambol...) | dessert
            $t->boolean('scales')->default(false);              // grows or shrinks when the member changes the portion
            $t->unsignedSmallInteger('sort')->default(0);
        });
        Schema::create('diet_checkins', function (Blueprint $t) {
            $t->id();
            $t->foreignId('member_id')->constrained()->cascadeOnDelete();
            $t->foreignId('diet_plan_id')->nullable()->constrained()->nullOnDelete();
            $t->date('week_of');
            $t->decimal('weight_kg', 5, 1);
            $t->string('followed')->default('most');            // most | some | little
            $t->string('hunger')->default('ok');                // low | ok | high
            $t->string('energy')->default('ok');                // low | ok | high
            $t->string('note', 300)->nullable();
            $t->unsignedSmallInteger('kcal_before')->nullable();
            $t->unsignedSmallInteger('kcal_after')->nullable();
            $t->string('action')->nullable();                   // kept | lowered | raised | hold
            $t->string('message', 400)->nullable();
            $t->boolean('is_dummy')->default(false);
            $t->timestamps();
        });

        DishCatalogue::sync();
    }

    public function down(): void
    {
        Schema::dropIfExists('diet_checkins');
        Schema::dropIfExists('dish_items');
        Schema::dropIfExists('dishes');
        Schema::table('foods', fn (Blueprint $t) => $t->dropColumn(['carbs_serving', 'fat_serving']));
    }
};
