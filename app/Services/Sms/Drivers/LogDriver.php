<?php

namespace App\Services\Sms\Drivers;

use App\Contracts\SmsGateway;
use App\Contracts\SupportsBulkSms;
use App\Services\Sms\Data\BulkSmsRequest;
use App\Services\Sms\Data\BulkSmsResponse;
use App\Services\Sms\Data\SmsRequest;
use App\Services\Sms\Data\SmsResponse;
use Illuminate\Support\Facades\Log;

final class LogDriver implements SmsGateway, SupportsBulkSms
{
    public function __construct(private readonly array $config = [])
    {
    }

    public function send(SmsRequest $request): SmsResponse
    {
        $channel = (string)($this->config['filename'] ?? 'sms');

        if (function_exists('ray')) {
            ray([
                'from' => $request->from,
                'body' => $request->body,
            ])->label("SMS to {$request->to}")->orange();
        } else {
            Log::channel('stack')->info("SMS [{$channel}] to {$request->to}", [
                'from' => $request->from,
                'body' => $request->body,
            ]);
        }

        return new SmsResponse(
            successful: true,
            gatewayMessageId: 'log-' . uniqid(),
        );
    }

    public function sendBulk(BulkSmsRequest $request): BulkSmsResponse
    {
        $channel = (string)($this->config['filename'] ?? 'sms');

        if (function_exists('ray')) {
            ray([
                'from' => $request->from,
                'body' => $request->body,
                'recipients' => $request->recipients,
            ])->label("SMS bulk to " . count($request->recipients) . " recipients")->orange();
        } else {
            Log::channel('stack')->info("SMS [{$channel}] bulk to " . count($request->recipients) . ' recipients', [
                'from' => $request->from,
                'body' => $request->body,
                'recipients' => $request->recipients,
            ]);
        }

        return new BulkSmsResponse(
            successful: true,
            perRecipientIds: array_map(static fn() => 'log-' . uniqid(), $request->recipients),
        );
    }
}
