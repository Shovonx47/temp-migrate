<?php

use App\Exceptions\DuplicateRecordHandler;
use App\Http\Middleware\EnsureApiAccess;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->preventRequestForgery(except: [
            'webhooks/*',
        ]);

        $middleware->alias([
            'api.access' => EnsureApiAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->reportable(function (UniqueConstraintViolationException $e): false {
            DuplicateRecordHandler::report($e);

            return false;
        });

        $exceptions->renderable(function (UniqueConstraintViolationException $e, Request $request) {
            return DuplicateRecordHandler::render($request, $e);
        });

        $exceptions->renderable(function (QueryException $e, Request $request) {
            if ($e instanceof UniqueConstraintViolationException || ! DuplicateRecordHandler::matches($e)) {
                return null;
            }

            DuplicateRecordHandler::report($e);

            return DuplicateRecordHandler::render($request, $e);
        });
    })->create();
