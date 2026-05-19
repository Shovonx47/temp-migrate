<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserOperatorRatesTable extends Migration
{
    public function up(): void
    {
        Schema::create('user_operator_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('operator_id')->constrained()->cascadeOnDelete();
            $table->decimal('masking_price', 8, 4)->nullable();
            $table->decimal('non_masking_price', 8, 4)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'operator_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_operator_rates');
    }
}
