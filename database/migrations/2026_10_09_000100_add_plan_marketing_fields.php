<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $t) {
            $t->string('tagline')->nullable();          // one line: who this package is for
            $t->text('features')->nullable();           // one feature per line, shown on the public pricing page
            $t->boolean('featured')->default(false);    // "Most popular" highlight
        });
    }

    public function down(): void
    {
        Schema::table('plans', fn (Blueprint $t) => $t->dropColumn(['tagline', 'features', 'featured']));
    }
};
