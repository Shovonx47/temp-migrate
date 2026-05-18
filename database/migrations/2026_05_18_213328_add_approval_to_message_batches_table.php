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
        Schema::table('message_batches', function (Blueprint $table): void {
            $table->string('approval')->nullable()->after('status');
            $table->foreignId('approved_by')->nullable()->after('approval')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('approval_note')->nullable()->after('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('message_batches', function (Blueprint $table): void {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['approval', 'approved_by', 'approved_at', 'approval_note']);
        });
    }
};
