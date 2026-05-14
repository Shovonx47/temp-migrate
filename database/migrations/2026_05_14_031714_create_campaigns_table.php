<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sender_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('body');
            $table->string('status')->default('draft'); // draft, scheduled, running, paused, completed, failed
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('total_recipients')->default(0);
            $table->unsignedBigInteger('sent_count')->default(0);
            $table->unsignedBigInteger('delivered_count')->default(0);
            $table->unsignedBigInteger('failed_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
