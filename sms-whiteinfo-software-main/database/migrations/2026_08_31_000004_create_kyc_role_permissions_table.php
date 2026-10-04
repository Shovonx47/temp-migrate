<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stores KYC-specific permission grants for Superman-type admin users.
     *
     * Super Admin (identified by having ALL permissions or by user_id reference)
     * can grant any subset of the defined KYC permissions to other admin accounts.
     *
     * Permissions are stored as a JSON array of permission keys, e.g.:
     * ["kyc.view", "kyc.approve", "kyc.audit"]
     */
    public function up(): void
    {
        Schema::create('kyc_role_permissions', function (Blueprint $table) {
            $table->id();

            // The admin whose permissions are being defined
            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete();

            // JSON array of granted permission keys
            $table->json('permissions')->default('[]');

            // Who granted these permissions
            $table->foreignId('granted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_role_permissions');
    }
};
