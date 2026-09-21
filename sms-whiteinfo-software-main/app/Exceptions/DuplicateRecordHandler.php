<?php

namespace App\Exceptions;

use Filament\Notifications\Notification;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

final class DuplicateRecordHandler
{
    public const TITLE = 'Record already exists';

    public const MESSAGE = 'This record already exists in our system. Please review the existing entry instead of creating a duplicate.';

    public static function matches(Throwable $exception): bool
    {
        if ($exception instanceof UniqueConstraintViolationException) {
            return true;
        }

        if (! $exception instanceof QueryException) {
            return false;
        }

        $sqlState = (string) ($exception->errorInfo[0] ?? '');
        $message = strtolower($exception->getMessage());

        if (in_array($sqlState, ['23000', '23505'], true)) {
            return str_contains($message, 'duplicate')
                || str_contains($message, 'unique');
        }

        return str_contains($message, 'duplicate entry')
            || str_contains($message, 'unique constraint failed');
    }

    public static function report(Throwable $exception): void
    {
        Log::warning('Duplicate record rejected.', [
            'constraint' => $exception->getMessage(),
        ]);
    }

    public static function render(Request $request, Throwable $exception): JsonResponse|RedirectResponse
    {
        if (self::wantsApiResponse($request)) {
            return new JsonResponse([
                'message' => self::MESSAGE,
            ], 409);
        }

        self::notify();

        $redirect = redirect()->back()->withInput();

        if (! self::isLivewireRequest($request)) {
            $redirect->withErrors(['record' => self::MESSAGE]);
        }

        return $redirect;
    }

    public static function notify(): void
    {
        Notification::make()
            ->title(self::TITLE)
            ->body(self::MESSAGE)
            ->danger()
            ->persistent()
            ->send();
    }

    private static function isLivewireRequest(Request $request): bool
    {
        return $request->hasHeader('X-Livewire');
    }

    private static function wantsApiResponse(Request $request): bool
    {
        if (self::isLivewireRequest($request)) {
            return false;
        }

        return $request->is('api/*') || $request->expectsJson();
    }
}
