<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Members only get automatic messages after they have said yes.
        Schema::table('members', function (Blueprint $t) {
            $t->boolean('whatsapp_optin')->default(false);
            $t->timestamp('whatsapp_optin_at')->nullable();
        });

        // One row per message the system tried to send. The anchor stops the same reminder going out twice.
        Schema::create('message_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $t->string('key');                       // which message: expiring_7, owing...
            $t->string('anchor');                    // what it was about: the membership, the week, the birthday year...
            $t->string('to')->nullable();
            $t->string('template')->nullable();      // name of the approved WhatsApp template used
            $t->text('text')->nullable();            // what the member saw (for the owner's records)
            $t->string('status')->default('queued'); // practice | sent | delivered | read | failed
            $t->string('wamid')->nullable()->index(); // WhatsApp's id for the message, used to match delivery receipts
            $t->text('error')->nullable();
            $t->unsignedTinyInteger('attempts')->default(1);
            $t->boolean('is_dummy')->default(false);
            $t->timestamps();
            $t->index(['member_id', 'key', 'anchor']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_logs');
        Schema::table('members', fn (Blueprint $t) => $t->dropColumn(['whatsapp_optin', 'whatsapp_optin_at']));
    }
};
