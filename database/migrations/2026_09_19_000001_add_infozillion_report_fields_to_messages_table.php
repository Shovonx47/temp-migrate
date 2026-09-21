<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table): void {
            $table->string('bill_msisdn', 32)->nullable()->after('source_ip');
            $table->string('sms_type', 32)->nullable()->after('bill_msisdn');
            $table->string('channel', 32)->nullable()->after('sms_type');
            $table->string('operator_name', 64)->nullable()->after('channel');
            $table->string('tariff_name', 128)->nullable()->after('operator_name');
            $table->uuid('request_id')->nullable()->index()->after('tariff_name');
            $table->string('server_txn_id', 128)->nullable()->index()->after('request_id');
            $table->string('server_response_code', 32)->nullable()->after('server_txn_id');
            $table->string('mno_txn_id', 128)->nullable()->after('server_response_code');
            $table->string('mno_response_code', 32)->nullable()->after('mno_txn_id');
            $table->text('mno_response_message')->nullable()->after('mno_response_code');
            $table->json('provider_response')->nullable()->after('mno_response_message');
            $table->string('delivery_status', 128)->nullable()->after('provider_response');
            $table->json('delivery_response')->nullable()->after('delivery_status');
            $table->index(['gateway_id', 'status', 'created_at'], 'messages_report_gateway_status_date_index');
            $table->index(['sender_id', 'created_at'], 'messages_report_sender_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table): void {
            $table->dropIndex('messages_report_gateway_status_date_index');
            $table->dropIndex('messages_report_sender_date_index');
            $table->dropIndex(['request_id']);
            $table->dropIndex(['server_txn_id']);
            $table->dropColumn([
                'bill_msisdn',
                'sms_type',
                'channel',
                'operator_name',
                'tariff_name',
                'request_id',
                'server_txn_id',
                'server_response_code',
                'mno_txn_id',
                'mno_response_code',
                'mno_response_message',
                'provider_response',
                'delivery_status',
                'delivery_response',
            ]);
        });
    }
};
