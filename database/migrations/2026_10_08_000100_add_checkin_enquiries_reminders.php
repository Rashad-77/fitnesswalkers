<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $t) {
            $t->id();
            $t->foreignId('member_id')->constrained()->cascadeOnDelete();
            $t->date('visited_on');
            $t->string('source')->default('owner'); // owner | member
            $t->boolean('is_dummy')->default(false);
            $t->timestamps();
            $t->unique(['member_id', 'visited_on']);
        });

        Schema::create('enquiries', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('phone');
            $t->string('email')->nullable();
            $t->string('interest')->nullable();
            $t->text('message')->nullable();
            $t->string('source')->default('website'); // website | walk-in | phone | other
            $t->string('status')->default('new');      // new | contacted | joined | lost
            $t->text('notes')->nullable();
            $t->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $t->boolean('is_dummy')->default(false);
            $t->timestamps();
        });

        Schema::create('reminders', function (Blueprint $t) {
            $t->id();
            $t->foreignId('member_id')->constrained()->cascadeOnDelete();
            $t->string('kind'); // expiring | expired | owing | birthday | quiet
            $t->date('sent_on');
            $t->timestamps();
            $t->index(['member_id', 'kind', 'sent_on']);
        });

        Schema::table('members', function (Blueprint $t) {
            $t->json('health_answers')->nullable();
            $t->boolean('health_flag')->default(false);
            $t->text('health_notes')->nullable();
            $t->timestamp('waiver_accepted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('members', fn (Blueprint $t) => $t->dropColumn(['health_answers', 'health_flag', 'health_notes', 'waiver_accepted_at']));
        Schema::dropIfExists('reminders');
        Schema::dropIfExists('enquiries');
        Schema::dropIfExists('attendances');
    }
};
