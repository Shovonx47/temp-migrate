<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds a JSON column to track which specific KYC fields the admin
     * has requested the customer to resubmit.
     * e.g. ["nid_number", "nid_front", "nid_back", "live_photo", "trade_license"]
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('kyc_resubmit_fields')
                ->nullable()
                ->after('kyc_rejected_reason')
                ->comment('Array of field keys admin has requested to be resubmitted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('kyc_resubmit_fields');
        });
    }
};
