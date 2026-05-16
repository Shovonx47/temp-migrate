<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->string('source_type'); // manual, upload, group
            $table->json('source_config'); // mapping, group_ids, file_path, etc.
            $table->string('status')->default('processing'); // processing, completed, failed
            $table->unsignedInteger('total_recipients')->default(0);
            $table->unsignedInteger('valid_count')->default(0);
            $table->unsignedInteger('invalid_count')->default(0);

            $table->unsignedBigInteger('last_processed_cursor')->nullable();


            $table->string('failed_report_path')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_batches');
    }
};
