<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('gateway_id')->nullable()->constrained()->nullOnDelete();

            $table->string('level', 16)->default('info'); // info, warning, error, debug
            $table->string('event', 64); // e.g. dispatch, sent, failed, delivered, dlr_received
            $table->string('phone', 32)->nullable();
            $table->text('body')->nullable();
            $table->string('gateway_name', 64)->nullable();
            $table->string('operator_name', 64)->nullable();
            $table->string('gateway_message_id', 128)->nullable();
            $table->string('status', 32)->nullable(); // pending, sending, sent, delivered, failed
            $table->text('request_payload')->nullable();
            $table->text('response_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedTinyInteger('parts')->default(1);
            $table->decimal('cost', 10, 4)->default(0);
            $table->unsignedInteger('duration_ms')->nullable(); // request round-trip ms
            $table->string('job_id', 128)->nullable();
            $table->string('queue', 64)->nullable();

            $table->timestamps();

            $table->index(['level', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['gateway_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};
