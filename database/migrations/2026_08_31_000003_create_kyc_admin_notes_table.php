<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Internal admin-only notes attached to a customer's KYC.
     * These are NEVER shown to the customer.
     */
    public function up(): void
    {
        Schema::create('kyc_admin_notes', function (Blueprint $table) {
            $table->id();

            // The customer this note is about
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // The admin who wrote this note
            $table->foreignId('admin_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Admin name snapshot at time of writing
            $table->string('admin_name')->nullable();

            // The note content
            $table->text('note');

            // Soft-delete so notes can be "removed" without data loss
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kyc_admin_notes');
    }
};
