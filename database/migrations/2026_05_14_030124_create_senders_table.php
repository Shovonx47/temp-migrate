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
        Schema::create('senders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gateway_id')->nullable()->constrained('gateways')->nullOnDelete();
            $table->string('sender_id');
            $table->string('type');
            $table->boolean('is_enabled')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['gateway_id', 'sender_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('senders');
    }
};
