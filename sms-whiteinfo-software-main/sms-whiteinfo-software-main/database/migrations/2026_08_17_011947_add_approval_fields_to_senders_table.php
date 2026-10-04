<?php

use App\Enums\SenderApprovalStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('senders', function (Blueprint $table) {
            $table->string('approval_status')
                ->default(SenderApprovalStatus::Approved->value)
                ->after('is_enabled');
            $table->foreignId('requested_by')
                ->nullable()
                ->after('approval_status')
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('reviewed_by')
                ->nullable()
                ->after('requested_by')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('reviewed_at')
                ->nullable()
                ->after('reviewed_by');
            $table->string('rejection_reason')
                ->nullable()
                ->after('reviewed_at');

            $table->index('approval_status');
            $table->index('requested_by');
        });
    }

    public function down(): void
    {
        Schema::table('senders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('requested_by');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn([
                'approval_status',
                'reviewed_at',
                'rejection_reason',
            ]);
        });
    }
};
