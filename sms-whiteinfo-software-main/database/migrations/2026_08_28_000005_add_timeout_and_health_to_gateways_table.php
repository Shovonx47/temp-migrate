<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gateways', function (Blueprint $table) {
            $table->unsignedSmallInteger('timeout_seconds')->default(10)->after('requests_per_second');
            $table->string('health_status', 30)->default('healthy')->after('is_enabled');
            $table->timestamp('last_health_check_at')->nullable()->after('health_status');
            $table->unsignedInteger('last_health_latency_ms')->nullable()->after('last_health_check_at');
            $table->text('last_health_response')->nullable()->after('last_health_latency_ms');
        });
    }

    public function down(): void
    {
        Schema::table('gateways', function (Blueprint $table) {
            $table->dropColumn([
                'timeout_seconds',
                'health_status',
                'last_health_check_at',
                'last_health_latency_ms',
                'last_health_response',
            ]);
        });
    }
};
