<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->renameColumn('gateway_cost', 'primary_dipping_cost');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->decimal('primary_dipping_rate', 10, 6)->nullable()->after('primary_dipping_cost');
            $table->decimal('third_party_dipping_cost', 10, 4)->nullable()->after('primary_dipping_rate');
            $table->decimal('third_party_dipping_rate', 10, 6)->nullable()->after('third_party_dipping_cost');
            $table->boolean('was_third_party_dipped')->default(false)->after('third_party_dipping_rate');
            $table->foreignId('tariff_rate_id')->nullable()->after('was_third_party_dipped')->constrained('tariff_rates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tariff_rate_id');
            $table->dropColumn([
                'primary_dipping_rate',
                'third_party_dipping_cost',
                'third_party_dipping_rate',
                'was_third_party_dipped',
            ]);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->renameColumn('primary_dipping_cost', 'gateway_cost');
        });
    }
};
