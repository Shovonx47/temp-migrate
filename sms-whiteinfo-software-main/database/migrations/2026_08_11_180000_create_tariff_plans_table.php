<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tariff_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('standard'); // TariffPlanType enum
            $table->string('status')->default('draft');  // TariffPlanStatus enum
            $table->string('currency', 10)->default('BDT');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tariff_plans');
    }
};
