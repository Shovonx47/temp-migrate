<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Covers the poller's fallback query: WHERE status='sent' AND sent_at <= ?
            // Without this, the UPDATE takes gap locks across a broad range under
            // REPEATABLE READ, blocking unrelated FK-referencing inserts for seconds.
            $table->index(['status', 'sent_at'], 'messages_status_sent_at_index');

            // Covers the poller's dispatch query: WHERE status='sent' AND next_poll_at <= ?
            $table->index(['status', 'next_poll_at'], 'messages_status_next_poll_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('messages_status_sent_at_index');
            $table->dropIndex('messages_status_next_poll_at_index');
        });
    }
};
