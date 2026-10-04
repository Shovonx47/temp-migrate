<?php

namespace App\Notifications\Horizon;

use Laravel\Horizon\Horizon;
use Laravel\Horizon\Notifications\LongWaitDetected as BaseLongWaitDetected;
use NotificationChannels\Telegram\Enums\ParseMode;
use NotificationChannels\Telegram\TelegramMessage;

class LongWaitDetected extends BaseLongWaitDetected
{
    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return array_filter([
            Horizon::$slackWebhookUrl ? 'slack' : null,
            Horizon::$smsNumber ? 'nexmo' : null,
            Horizon::$email ? 'mail' : null,
            config('services.telegram-bot-api.enabled') ? 'telegram' : null,
        ]);
    }

    /**
     * Get the Telegram representation of the notification.
     */
    public function toTelegram(mixed $notifiable): TelegramMessage
    {
        return TelegramMessage::create()
            ->to(config('services.telegram-bot-api.chat_id'))
            ->parseMode(ParseMode::HTML)
            ->content(sprintf(
                "<b>%s: Long Queue Wait Detected</b>\n\nThe <code>%s</code> queue on the <code>%s</code> connection has a wait time of <b>%d seconds</b>.",
                config('horizon.name'),
                $this->longWaitQueue,
                $this->longWaitConnection,
                $this->seconds,
            ));
    }
}
