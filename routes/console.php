<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('telescope:prune --hours=48')->daily();

// Schedule::command('campaigns:send-scheduled')
//    ->everyMinute()
//    ->withoutOverlapping()
//    ->runInBackground();

Schedule::command('messages:poll-delivery')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
