<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gateways', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('driver');

            $table->longText('credentials')->nullable();
            $table->json('config')->nullable();

            $table->unsignedBigInteger('daily_limit')->nullable();
            $table->unsignedBigInteger('monthly_limit')->nullable();

            $table->boolean('is_enabled')->default(false);
            $table->boolean('supports_bulk')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gateways');
    }
};
