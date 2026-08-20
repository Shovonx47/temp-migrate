<?php

use App\Enums\DippingRateMode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gateway_third_party_dipping_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gateway_id')->constrained()->cascadeOnDelete();
            $table->decimal('rate', 10, 6);
            $table->string('rate_mode')->default(DippingRateMode::PerSms->value);
            $table->date('effective_from')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['gateway_id', 'effective_from'], 'gateway_third_party_dipping_configs_lookup');
        });

        Schema::create('gateway_third_party_dipping_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gateway_id')->constrained()->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedInteger('dipped_sms_volume')->default(0);
            $table->decimal('manual_cost', 12, 4);
            $table->decimal('rate', 10, 6)->nullable();
            $table->string('rate_mode')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['gateway_id', 'period_start', 'period_end'], 'gateway_third_party_dipping_periods_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gateway_third_party_dipping_periods');
        Schema::dropIfExists('gateway_third_party_dipping_configs');
    }
};
