<?php

use App\Enums\DippingRateMode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('gateway_dipping_charge_histories');
        Schema::dropIfExists('gateway_dipping_charges');

        Schema::create('gateway_dipping_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gateway_id')->constrained()->cascadeOnDelete();
            $table->foreignId('operator_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('masking_rate', 10, 6)->nullable();
            $table->decimal('non_masking_rate', 10, 6)->nullable();
            $table->string('rate_mode')->default(DippingRateMode::PerSms->value);
            $table->date('effective_from')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['gateway_id', 'operator_id', 'effective_from'], 'gateway_dipping_charges_lookup_index');
        });

        Schema::create('gateway_dipping_charge_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gateway_dipping_charge_id')
                ->constrained('gateway_dipping_charges', indexName: 'gdch_charge_id_fk')
                ->cascadeOnDelete();
            $table->foreignId('gateway_id')->constrained()->cascadeOnDelete();
            $table->foreignId('operator_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('old_masking_rate', 10, 6)->nullable();
            $table->decimal('new_masking_rate', 10, 6)->nullable();
            $table->decimal('old_non_masking_rate', 10, 6)->nullable();
            $table->decimal('new_non_masking_rate', 10, 6)->nullable();
            $table->string('old_rate_mode')->nullable();
            $table->string('new_rate_mode')->nullable();
            $table->date('old_effective_from')->nullable();
            $table->date('new_effective_from')->nullable();
            $table->string('event');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('changed_at');
        });

        if (Schema::hasTable('gateway_operator_rates')) {
            foreach (DB::table('gateway_operator_rates')->orderBy('id')->lazy() as $rate) {
                DB::table('gateway_dipping_charges')->insert([
                    'gateway_id' => $rate->gateway_id,
                    'operator_id' => $rate->operator_id,
                    'masking_rate' => $rate->masking_price,
                    'non_masking_rate' => $rate->non_masking_price,
                    'rate_mode' => DippingRateMode::PerSms->value,
                    'effective_from' => null,
                    'created_at' => $rate->created_at,
                    'updated_at' => $rate->updated_at,
                ]);
            }
        }

        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('dipping_charge_id')
                ->nullable()
                ->after('tariff_rate_id')
                ->constrained('gateway_dipping_charges')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dipping_charge_id');
        });

        Schema::dropIfExists('gateway_dipping_charge_histories');
        Schema::dropIfExists('gateway_dipping_charges');
    }
};
