<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('sp_id')
                ->nullable()
                ->after('type')
                ->constrained('users')
                ->nullOnDelete();

            $table->index('sp_id');
        });

        Schema::table('tariff_plans', function (Blueprint $table) {
            $table->foreignId('sp_id')
                ->nullable()
                ->after('created_by')
                ->constrained('users')
                ->nullOnDelete();

            $table->index('sp_id');
        });
    }

    public function down(): void
    {
        Schema::table('tariff_plans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sp_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sp_id');
        });
    }
};
