<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', fn (Blueprint $t) => $t->date('frozen_at')->nullable());
    }

    public function down(): void
    {
        Schema::table('members', fn (Blueprint $t) => $t->dropColumn('frozen_at'));
    }
};
