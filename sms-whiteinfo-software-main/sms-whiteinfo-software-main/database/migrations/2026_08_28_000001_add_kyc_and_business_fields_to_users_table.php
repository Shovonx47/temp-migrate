<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nid_number')->nullable()->unique()->after('phone');
            $table->string('live_photo')->nullable()->after('back_nid');

            // Business Information & Trade License
            $table->string('business_name')->nullable()->after('live_photo');
            $table->text('business_address')->nullable()->after('business_name');
            $table->string('trade_license_number')->nullable()->after('business_address');
            $table->string('trade_license_file')->nullable()->after('trade_license_number');

            // KYC and Verification Status Fields
            $table->boolean('nid_verified')->default(false)->after('phone_verified_at');
            $table->boolean('live_photo_verified')->default(false)->after('nid_verified');
            $table->boolean('trade_license_submitted')->default(false)->after('live_photo_verified');
            $table->string('kyc_status')->default('under_review')->index()->after('trade_license_submitted');
            $table->text('kyc_rejected_reason')->nullable()->after('kyc_status');
            $table->timestamp('kyc_submitted_at')->nullable()->after('kyc_rejected_reason');
            $table->timestamp('kyc_verified_at')->nullable()->after('kyc_submitted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'nid_number',
                'live_photo',
                'business_name',
                'business_address',
                'trade_license_number',
                'trade_license_file',
                'nid_verified',
                'live_photo_verified',
                'trade_license_submitted',
                'kyc_status',
                'kyc_rejected_reason',
                'kyc_submitted_at',
                'kyc_verified_at',
            ]);
        });
    }
};
