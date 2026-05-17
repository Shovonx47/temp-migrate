<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->foreignId('group_id')
                ->after('sender_id')
                ->nullable()
                ->constrained('groups')
                ->nullOnDelete();

            $table->unsignedInteger('total_recipients')->default(0)->after('completed_at');
            $table->unsignedInteger('total_sent')->default(0)->after('total_recipients');
            $table->unsignedInteger('total_failed')->default(0)->after('total_sent');

            $table->unsignedBigInteger('last_processed_recipient_id')->nullable()->after('total_failed');

            $table->index(['user_id', 'status']);
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['scheduled_at']);

            $table->dropConstrainedForeignId('group_id');
            $table->dropColumn([
                'total_recipients',
                'total_sent',
                'total_failed',
                'last_processed_recipient_id',
            ]);
        });
    }
};
