<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gateway_id')->constrained()->cascadeOnDelete();
            $table->foreignId('message_id')->nullable()->constrained()->nullOnDelete();
            $table->string('gateway_message_id')->nullable();
            $table->string('status'); // delivered, failed, rejected, pending, unknown
            $table->json('raw_payload');
            $table->timestamp('received_at');
            $table->timestamps();

            $table->index('gateway_message_id');
            $table->index(['gateway_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_reports');
    }
};
