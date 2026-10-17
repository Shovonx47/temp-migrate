<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gateway_operator_rates', function (Blueprint $table): void {
            $table->decimal('dipping_charge', 10, 6)->nullable()->after('non_masking_price');
        });

    }

    public function down(): void
    {
        Schema::table('gateway_operator_rates', function (Blueprint $table): void {
            $table->dropColumn('dipping_charge');
        });
    }
};
