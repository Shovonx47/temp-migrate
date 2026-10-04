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
        Schema::create('signup_register_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('endpoint_url');
            $table->string('http_method')->default('POST');
            $table->text('bearer_token')->nullable();
            $table->string('sender_id');
            $table->string('body_template')->default('Your SMS WhiteInfo verification code is: {otp}. Valid for {minutes} minutes.');
            $table->string('phone_format')->default('local_bd');
            $table->unsignedInteger('timeout_seconds')->default(15);
            $table->unsignedInteger('priority')->default(1)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signup_register_gateways');
    }
};
