<?php

use App\Services\Sms\MessageMetrics;

test('empty body is treated as one GSM-7 part with zero chars', function () {
    $m = MessageMetrics::for('');

    expect($m->encoding)->toBe(MessageMetrics::GSM7)
        ->and($m->chars)->toBe(0)
        ->and($m->parts)->toBe(1);
});

test('short GSM-7 body fits in a single part', function () {
    $m = MessageMetrics::for('Hello world');

    expect($m->encoding)->toBe(MessageMetrics::GSM7)
        ->and($m->chars)->toBe(11)
        ->and($m->parts)->toBe(1);
});

test('GSM-7 body exactly at single-part limit is still one part', function () {
    $m = MessageMetrics::for(str_repeat('a', 160));

    expect($m->parts)->toBe(1)
        ->and($m->chars)->toBe(160);
});

test('GSM-7 body over single-part limit splits into multi-part at 153 chars each', function () {
    $m = MessageMetrics::for(str_repeat('a', 161));

    expect($m->encoding)->toBe(MessageMetrics::GSM7)
        ->and($m->parts)->toBe(2);
});

test('GSM-7 extension characters count as two', function () {
    $m = MessageMetrics::for('a{b}c');

    expect($m->encoding)->toBe(MessageMetrics::GSM7)
        ->and($m->chars)->toBe(7);
});

test('Unicode body switches to UCS-2 with 70/67 limits', function () {
    $body = str_repeat('আ', 70);
    $m = MessageMetrics::for($body);

    expect($m->encoding)->toBe(MessageMetrics::UCS2)
        ->and($m->chars)->toBe(70)
        ->and($m->parts)->toBe(1);
});

test('Unicode body over single-part limit splits at 67 chars per part', function () {
    $body = str_repeat('আ', 71);
    $m = MessageMetrics::for($body);

    expect($m->encoding)->toBe(MessageMetrics::UCS2)
        ->and($m->parts)->toBe(2);
});
