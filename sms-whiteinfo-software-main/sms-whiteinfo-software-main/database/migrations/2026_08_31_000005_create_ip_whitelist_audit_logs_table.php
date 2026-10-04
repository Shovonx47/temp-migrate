<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Audit log for all IP Whitelist lifecycle events:
     * add, edit, approve, reject, disable, enable, delete.
     */
    public function up(): void
    {
        Schema::create('ip_whitelist_audit_logs', function (Blueprint $table) {
            $table->id();

            // The IP whitelist request being acted on (nullable in case of deletion)
            $table->foreignId('ip_whitelist_request_id')
                ->nullable()
                ->constrained('ip_whitelist_requests')
                ->nullOnDelete();

            // The customer account whose whitelist this affects
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // The actor who performed the action (admin or customer)
            $table->foreignId('actor_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('actor_name')->nullable();
            $table->string('actor_type')->default('customer'); // admin | customer | system

            // Action: created, updated, approved, rejected, disabled, enabled, deleted
            $table->string('action');

            // The IP address or CIDR range affected
            $table->string('ip_address');

            // Before / after state
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            // Optional note or rejection/revocation reason
            $table->text('note')->nullable();

            // IP address of the person who initiated the request
            $table->string('source_ip', 45)->nullable();

            $table->timestamps();

            $table->index(['user_id', 'action']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ip_whitelist_audit_logs');
    }
};
