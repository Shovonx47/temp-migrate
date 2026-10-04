<?php

namespace App\Services\Logging;

use App\Enums\SmsLogLevel;
use App\Models\SmsLog;

/**
 * Centralised SMS logging service.
 *
 * Call from jobs, drivers, or any service that processes SMS.
 * All entries are persisted to the sms_logs table.
 */
class SmsLogger
{
    public static function info(string $event, array $context = []): SmsLog
    {
        return self::write(SmsLogLevel::Info, $event, $context);
    }

    public static function debug(string $event, array $context = []): SmsLog
    {
        return self::write(SmsLogLevel::Debug, $event, $context);
    }

    public static function warning(string $event, array $context = []): SmsLog
    {
        return self::write(SmsLogLevel::Warning, $event, $context);
    }

    public static function error(string $event, array $context = []): SmsLog
    {
        return self::write(SmsLogLevel::Error, $event, $context);
    }

    /**
     * @param  array{
     *     message_id?: int|null,
     *     user_id?: int|null,
     *     gateway_id?: int|null,
     *     phone?: string|null,
     *     body?: string|null,
     *     gateway_name?: string|null,
     *     operator_name?: string|null,
     *     gateway_message_id?: string|null,
     *     status?: string|null,
     *     request_payload?: string|null,
     *     response_payload?: string|null,
     *     error_message?: string|null,
     *     parts?: int,
     *     cost?: float,
     *     duration_ms?: int|null,
     *     job_id?: string|null,
     *     queue?: string|null,
     * } $context
     */
    private static function write(SmsLogLevel $level, string $event, array $context): SmsLog
    {
        return SmsLog::create([
            'level' => $level->value,
            'event' => $event,
            'message_id' => $context['message_id'] ?? null,
            'user_id' => $context['user_id'] ?? null,
            'gateway_id' => $context['gateway_id'] ?? null,
            'phone' => $context['phone'] ?? null,
            'body' => $context['body'] ?? null,
            'gateway_name' => $context['gateway_name'] ?? null,
            'operator_name' => $context['operator_name'] ?? null,
            'gateway_message_id' => $context['gateway_message_id'] ?? null,
            'status' => $context['status'] ?? null,
            'request_payload' => isset($context['request_payload'])
                ? (is_string($context['request_payload']) ? $context['request_payload'] : json_encode($context['request_payload']))
                : null,
            'response_payload' => isset($context['response_payload'])
                ? (is_string($context['response_payload']) ? $context['response_payload'] : json_encode($context['response_payload']))
                : null,
            'error_message' => $context['error_message'] ?? null,
            'parts' => $context['parts'] ?? 1,
            'cost' => $context['cost'] ?? 0,
            'duration_ms' => $context['duration_ms'] ?? null,
            'job_id' => $context['job_id'] ?? null,
            'queue' => $context['queue'] ?? null,
        ]);
    }
}
