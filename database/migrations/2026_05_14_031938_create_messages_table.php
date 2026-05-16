<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('message_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sender_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('gateway_id')->nullable()->constrained()->nullOnDelete(); // snapshot

            $table->string('to'); // recipient phone number
            $table->text('body');
            $table->string('status')->default('pending'); // pending, sent, delivered, failed
            $table->string('gateway_message_id')->nullable(); // gateway's response id


            $table->decimal('cost', 10, 4)->default(0);
            $table->decimal('rate', 8, 4)->default(0);
            $table->unsignedTinyInteger('parts')->default(1);



            $table->text('failed_reason')->nullable();

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'sent_at']);
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
