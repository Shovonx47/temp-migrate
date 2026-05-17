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
        Schema::table('gateways', function (Blueprint $table): void {
            // Drop old SMS-count-based limits.
            if (Schema::hasColumn('gateways', 'daily_limit')) {
                $table->dropColumn('daily_limit');
            }

            if (Schema::hasColumn('gateways', 'monthly_limit')) {
                $table->dropColumn('monthly_limit');
            }

            // Drop orphaned column from failed rollback (if it exists).
            if (Schema::hasColumn('gateways', 'individual_rate_limit')) {
                $table->dropColumn('individual_rate_limit');
            }

            // API request rate: maximum gateway API calls per minute.
            $table->unsignedInteger('requests_per_minute')->default(60)->after('supports_bulk');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gateways', function (Blueprint $table): void {
            $table->dropColumn('requests_per_minute');

            $table->unsignedBigInteger('daily_limit')->nullable();
            $table->unsignedBigInteger('monthly_limit')->nullable();
        });
    }
};
