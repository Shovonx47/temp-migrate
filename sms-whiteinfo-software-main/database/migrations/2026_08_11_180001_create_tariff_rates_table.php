<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tariff_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tariff_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('operator_id')->constrained()->cascadeOnDelete();
            $table->decimal('masking_price', 8, 4)->nullable();
            $table->decimal('non_masking_price', 8, 4)->nullable();
            $table->date('effective_from')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tariff_plan_id', 'operator_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tariff_rates');
    }
};
