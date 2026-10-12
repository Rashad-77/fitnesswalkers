<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->text('value')->nullable();
            $t->boolean('is_dummy')->default(false);
            $t->timestamps();
        });

        Schema::create('plans', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->unsignedInteger('duration_days');
            $t->decimal('price', 10, 2);
            $t->boolean('active')->default(true);
            $t->boolean('is_dummy')->default(false);
            $t->timestamps();
        });

        Schema::create('members', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('member_no')->unique();
            $t->date('dob')->nullable();
            $t->string('sex')->nullable(); // male | female
            $t->decimal('height_cm', 5, 1)->nullable();
            $t->string('address')->nullable();
            $t->string('emergency_contact')->nullable();
            $t->date('joined_on');
            $t->boolean('frozen')->default(false);
            $t->text('notes')->nullable();
            $t->boolean('is_dummy')->default(false);
            $t->timestamps();
        });

        Schema::create('memberships', function (Blueprint $t) {
            $t->id();
            $t->foreignId('member_id')->constrained()->cascadeOnDelete();
            $t->foreignId('plan_id')->constrained();
            $t->date('starts_on');
            $t->date('ends_on');
            $t->decimal('price', 10, 2);
            $t->boolean('is_dummy')->default(false);
            $t->timestamps();
        });

        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->string('receipt_no')->unique();
            $t->foreignId('member_id')->constrained()->cascadeOnDelete();
            $t->foreignId('membership_id')->nullable()->constrained()->nullOnDelete();
            $t->decimal('amount', 10, 2);
            $t->string('method'); // cash | bank | card
            $t->date('paid_on');
            $t->string('note')->nullable();
            $t->boolean('is_dummy')->default(false);
            $t->timestamps();
        });

        Schema::create('expenses', function (Blueprint $t) {
            $t->id();
            $t->string('category');
            $t->decimal('amount', 10, 2);
            $t->date('spent_on');
            $t->string('description')->nullable();
            $t->boolean('is_dummy')->default(false);
            $t->timestamps();
        });

        Schema::create('announcements', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->text('body');
            $t->dateTime('published_at');
            $t->boolean('is_dummy')->default(false);
            $t->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('action');
            $t->string('subject')->nullable();
            $t->text('details')->nullable();
            $t->timestamps();
        });

        Schema::create('renewal_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('member_id')->constrained()->cascadeOnDelete();
            $t->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $t->string('status')->default('pending'); // pending | done
            $t->boolean('is_dummy')->default(false);
            $t->timestamps();
        });

        Schema::create('equipment', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('category'); // machine | free weights | bodyweight | cardio
            $t->boolean('out_of_order')->default(false);
            $t->boolean('is_dummy')->default(false);
            $t->timestamps();
        });

        Schema::create('exercises', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('muscle_group');
            $t->foreignId('equipment_id')->nullable()->constrained('equipment')->nullOnDelete();
            $t->text('steps');
            $t->text('mistakes')->nullable();
            $t->string('clip_url')->nullable(); // short looping MP4/WebM
            $t->boolean('is_dummy')->default(false);
            $t->timestamps();
        });

        Schema::create('workout_templates', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('goal'); // fat_loss | muscle_gain | general
            $t->string('level'); // beginner | intermediate
            $t->unsignedTinyInteger('days_per_week');
            $t->boolean('is_dummy')->default(false);
            $t->timestamps();
        });

        Schema::create('workout_template_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('workout_template_id')->constrained()->cascadeOnDelete();
            $t->unsignedTinyInteger('day_no');
            $t->foreignId('exercise_id')->constrained()->cascadeOnDelete();
            $t->unsignedTinyInteger('sets')->default(3);
            $t->string('reps')->default('10');
        });

        Schema::create('plan_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('member_id')->constrained()->cascadeOnDelete();
            $t->string('goal');
            $t->string('level');
            $t->unsignedTinyInteger('days_per_week');
            $t->text('notes')->nullable();
            $t->string('status')->default('pending'); // pending | approved | rejected
            $t->text('admin_note')->nullable();
            $t->boolean('is_dummy')->default(false);
            $t->timestamps();
        });

        Schema::create('workout_plans', function (Blueprint $t) {
            $t->id();
            $t->foreignId('member_id')->constrained()->cascadeOnDelete();
            $t->foreignId('plan_request_id')->nullable()->constrained()->nullOnDelete();
            $t->string('name');
            $t->string('status')->default('draft'); // draft | approved
            $t->dateTime('approved_at')->nullable();
            $t->boolean('is_dummy')->default(false);
            $t->timestamps();
        });

        Schema::create('workout_plan_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('workout_plan_id')->constrained()->cascadeOnDelete();
            $t->unsignedTinyInteger('day_no');
            $t->foreignId('exercise_id')->constrained()->cascadeOnDelete();
            $t->unsignedTinyInteger('sets')->default(3);
            $t->string('reps')->default('10');
        });

        Schema::create('workout_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('member_id')->constrained()->cascadeOnDelete();
            $t->foreignId('exercise_id')->constrained()->cascadeOnDelete();
            $t->date('logged_on');
            $t->unsignedTinyInteger('set_no')->default(1);
            $t->unsignedSmallInteger('reps');
            $t->decimal('weight_kg', 6, 1)->default(0);
            $t->boolean('is_dummy')->default(false);
            $t->timestamps();
        });

        Schema::create('body_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('member_id')->constrained()->cascadeOnDelete();
            $t->date('logged_on');
            $t->decimal('weight_kg', 5, 1);
            $t->decimal('height_cm', 5, 1)->nullable();
            $t->decimal('bmi', 4, 1)->nullable();
            $t->decimal('waist_cm', 5, 1)->nullable();
            $t->decimal('chest_cm', 5, 1)->nullable();
            $t->decimal('arm_cm', 5, 1)->nullable();
            $t->boolean('is_dummy')->default(false);
            $t->timestamps();
        });

        Schema::create('goals', function (Blueprint $t) {
            $t->id();
            $t->foreignId('member_id')->constrained()->cascadeOnDelete();
            $t->string('title');
            $t->decimal('target_value', 7, 1)->nullable();
            $t->string('unit')->nullable();
            $t->date('target_date')->nullable();
            $t->boolean('done')->default(false);
            $t->boolean('is_dummy')->default(false);
            $t->timestamps();
        });

        Schema::create('foods', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('category'); // grain | protein | veg | fruit | dairy | fat | legume
            $t->string('diet_type'); // vegan | vegetarian | non_veg
            $t->string('allergens')->nullable(); // csv: egg,dairy,fish,nuts,gluten,soy
            $t->decimal('kcal_100g', 6, 1);
            $t->decimal('protein_100g', 5, 1);
            $t->decimal('carbs_100g', 5, 1);
            $t->decimal('fat_100g', 5, 1);
            $t->decimal('price_per_kg', 8, 2); // LKR
            $t->date('price_updated_on')->nullable();
            $t->boolean('price_locked')->default(false); // owner override: auto-update skips it
            $t->string('meal_tags')->default('main'); // csv of: breakfast, main, snack
            $t->boolean('is_dummy')->default(false);
            $t->timestamps();
        });

        Schema::create('diet_plans', function (Blueprint $t) {
            $t->id();
            $t->foreignId('member_id')->constrained()->cascadeOnDelete();
            $t->string('source'); // app | owner
            $t->string('title');
            $t->json('inputs')->nullable();
            $t->json('plan')->nullable(); // days -> meals -> items
            $t->text('owner_text')->nullable(); // owner-written plan
            $t->boolean('active')->default(true);
            $t->boolean('is_dummy')->default(false);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['diet_plans','foods','goals','body_logs','workout_logs','workout_plan_items','workout_plans','plan_requests','workout_template_items','workout_templates','exercises','equipment','renewal_requests','audit_logs','announcements','expenses','payments','memberships','members','plans','settings'] as $tbl) {
            Schema::dropIfExists($tbl);
        }
    }
};
