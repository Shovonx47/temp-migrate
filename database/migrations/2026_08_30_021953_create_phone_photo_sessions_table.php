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
        Schema::create('phone_photo_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('session_token', 128)->unique();
            $table->longText('photo_data')->nullable();
            $table->string('status', 32)->default('pending'); // pending, completed, expired
            $table->dateTime('expires_at');
            $table->timestamps();

            $table->index('session_token');
            $table->index(['status', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phone_photo_sessions');
    }
};
