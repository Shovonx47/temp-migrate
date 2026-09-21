<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->string('source_ip', 45)->nullable()->after('source_type');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->string('source_ip', 45)->nullable()->after('source');
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('source_ip', 45)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->dropColumn('source_ip');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('source_ip');
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('source_ip');
        });
    }
};
