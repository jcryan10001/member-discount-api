<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Simulated proof of eligibility, e.g. "NHS staff ID: ABC-12345".
            // A real system would store an uploaded document (see the README's "more time" notes).
            $table->text('proof_reference');
            // RequestStatus enum value: pending | approved | rejected.
            $table->string('status')->default('pending');
            // The admin who reviewed it; null until reviewed.
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_requests');
    }
};
