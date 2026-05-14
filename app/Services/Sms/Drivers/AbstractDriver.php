<?php

namespace App\Services\Sms\Drivers;

use App\Contracts\SmsGateway;
use App\Enums\MessageStatus;
use App\Models\Gateway;
use App\Models\Message;
use App\Models\Sender;
use App\Services\Sms\SmsResult;

abstract class AbstractDriver implements SmsGateway
{
    final public function send(Message $message): bool
    {
        $message->loadMissing(['sender', 'gateway']);

        $sender = $message->sender;
        $gateway = $message->gateway;

        $message->update(['status' => MessageStatus::Pending]);

        try {
            $result = $this->sendMessage($message, $sender, $gateway);

            $message->update([
                'status' => $result->success ? MessageStatus::Sent : MessageStatus::Failed,
                'gateway_message_id' => $result->responseId,
                'failed_reason' => $result->errorMessage,
                'sent_at' => $result->success ? now() : null,
                'failed_at' => $result->success ? null : now(),
            ]);

            return $result->success;
        } catch (\Throwable $e) {
            $message->update([
                'status' => MessageStatus::Failed,
                'failed_reason' => $e->getMessage(),
                'failed_at' => now(),
            ]);

            report($e);

            return false;
        }
    }

    abstract protected function sendMessage(Message $message, Sender $sender, Gateway $gateway): SmsResult;
}
