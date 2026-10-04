<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Every KYC-related change made by any admin is recorded here.
     * This table is append-only and must never be modified/deleted.
     */
    public function up(): void
    {
        Schema::create('kyc_audit_logs', function (Blueprint $table) {
            $table->id();

            // The customer whose KYC was changed
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // The admin who made the change (null if system/auto)
            $table->foreignId('admin_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Human-readable admin name snapshot (in case admin is deleted later)
            $table->string('admin_name')->nullable();

            // Action type: updated, approved, rejected, resubmission_requested, document_replaced, document_removed
            $table->string('action');

            // Which field was changed (e.g. "nid_number", "nid_front", "kyc_status")
            $table->string('field_name')->nullable();

            // Serialized old value (for documents: just the filename, not the file)
            $table->text('old_value')->nullable();

            // Serialized new value
            $table->text('new_value')->nullable();

            // Extra context/reason (e.g. rejection reason text, resubmission note)
            $table->text('note')->nullable();

            // IP address of the admin who made the change
            $table->string('ip_address', 45)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kyc_audit_logs');
    }
};
