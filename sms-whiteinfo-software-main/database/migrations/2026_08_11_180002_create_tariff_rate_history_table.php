<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tariff_rate_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tariff_plan_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('tariff_rate_id')->nullable(); // nullable: rate row may be deleted
            $table->foreignId('operator_id')->constrained()->cascadeOnDelete();
            $table->decimal('old_masking_price', 8, 4)->nullable();
            $table->decimal('new_masking_price', 8, 4)->nullable();
            $table->decimal('old_non_masking_price', 8, 4)->nullable();
            $table->decimal('new_non_masking_price', 8, 4)->nullable();
            $table->string('event')->default('updated'); // 'created'|'updated'|'deleted'
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('changed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tariff_rate_history');
    }
};
