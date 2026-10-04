<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('gateways', function (Blueprint $table) {
            $table->renameColumn('requests_per_minute', 'requests_per_second');
        });

        Schema::table('gateways', function (Blueprint $table) {
            $table->unsignedInteger('requests_per_second')->default(10)->change();
        });

        DB::table('gateways')->get()->each(function ($gateway) {
            DB::table('gateways')
                ->where('id', $gateway->id)
                ->update([
                    'requests_per_second' => max(1, (int) round(((int) ($gateway->requests_per_second ?? 60)) / 60)),
                ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('gateways')->get()->each(function ($gateway) {
            DB::table('gateways')
                ->where('id', $gateway->id)
                ->update([
                    'requests_per_second' => ((int) ($gateway->requests_per_second ?? 1)) * 60,
                ]);
        });

        Schema::table('gateways', function (Blueprint $table) {
            $table->renameColumn('requests_per_second', 'requests_per_minute');
        });

        Schema::table('gateways', function (Blueprint $table) {
            $table->unsignedInteger('requests_per_minute')->default(60)->change();
        });
    }
};
