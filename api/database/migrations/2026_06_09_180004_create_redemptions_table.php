<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $table->string('code_issued');
            $table->timestamp('redeemed_at');
            $table->timestamps();

            // One redemption per member per offer, enforced at the DB level.
            // This is the safety net behind the in-code "already redeemed" check
            // in RedemptionService: even if two requests race past that check,
            // the unique index makes the second insert fail instead of granting
            // a duplicate code.
            $table->unique(['user_id', 'offer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redemptions');
    }
};
