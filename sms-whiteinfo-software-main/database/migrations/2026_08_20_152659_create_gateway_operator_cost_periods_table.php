<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gateway_operator_cost_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gateway_id')->constrained()->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('manual_cost', 12, 4);
            $table->decimal('api_cost', 12, 4)->nullable();
            $table->decimal('cost_variance', 12, 4)->nullable();
            $table->unsignedInteger('dlr_delivered_count')->nullable();
            $table->unsignedInteger('dlr_api_delivered_count')->nullable();
            $table->integer('dlr_variance')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['gateway_id', 'period_start', 'period_end'], 'gateway_operator_cost_periods_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gateway_operator_cost_periods');
    }
};
