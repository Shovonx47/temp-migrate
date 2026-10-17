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
