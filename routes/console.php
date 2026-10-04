<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('telescope:prune --hours=48')
    ->daily()
    ->runInBackground();

Schedule::command('disposable:update')
    ->daily()
    ->runInBackground();

Schedule::command('messages:poll-delivery')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// Auto-purge SMS messages and batches stuck in the queue for more than 8 minutes
Schedule::command('queue:purge-stale --minutes=8')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// Dispatch scheduled SMS batches and campaigns due for sending
Schedule::command('batches:send-scheduled')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('campaigns:send-scheduled')
    ->everyMinute()
    ->withoutOverlapping();

