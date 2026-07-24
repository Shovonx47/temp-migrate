<?php

use App\Support\MnoCarrier;

test('fromBillMsisdn resolves GP from 88017 prefix', function () {
    expect(MnoCarrier::fromBillMsisdn('8801712345678'))->toBe('GP');
});

test('fromBillMsisdn resolves GP from 88013 prefix', function () {
    expect(MnoCarrier::fromBillMsisdn('8801312345678'))->toBe('GP');
});

test('fromBillMsisdn resolves BL from 88014 prefix', function () {
    expect(MnoCarrier::fromBillMsisdn('8801412345678'))->toBe('BL');
});

test('fromBillMsisdn resolves BL from 88019 prefix', function () {
    expect(MnoCarrier::fromBillMsisdn('8801912345678'))->toBe('BL');
});

test('fromBillMsisdn resolves TT from 88015 prefix', function () {
    expect(MnoCarrier::fromBillMsisdn('8801512345678'))->toBe('TT');
});

test('fromBillMsisdn resolves RB from 88016 prefix', function () {
    expect(MnoCarrier::fromBillMsisdn('8801612345678'))->toBe('RB');
});

test('fromBillMsisdn resolves RB from 88018 prefix', function () {
    expect(MnoCarrier::fromBillMsisdn('8801812345678'))->toBe('RB');
});

test('fromBillMsisdn returns null for unknown prefix', function () {
    expect(MnoCarrier::fromBillMsisdn('8801212345678'))->toBeNull();
});

test('fromBillMsisdn handles plus prefix', function () {
    expect(MnoCarrier::fromBillMsisdn('+8801712345678'))->toBe('GP');
});

test('matchesCarrier returns true for GP phone with GP MNO', function () {
    expect(MnoCarrier::matchesCarrier('+8801712345678', 'GP'))->toBeTrue();
});

test('matchesCarrier returns true for GP phone (013) with GP MNO', function () {
    expect(MnoCarrier::matchesCarrier('+8801312345678', 'GP'))->toBeTrue();
});

test('matchesCarrier returns false for BL phone with GP MNO', function () {
    expect(MnoCarrier::matchesCarrier('+8801412345678', 'GP'))->toBeFalse();
});

test('matchesCarrier returns true for BL phone with BL MNO', function () {
    expect(MnoCarrier::matchesCarrier('+8801412345678', 'BL'))->toBeTrue();
    expect(MnoCarrier::matchesCarrier('+8801912345678', 'BL'))->toBeTrue();
});

test('matchesCarrier returns true for TT phone with TT MNO', function () {
    expect(MnoCarrier::matchesCarrier('+8801512345678', 'TT'))->toBeTrue();
});

test('matchesCarrier returns true for RB phone with RB MNO', function () {
    expect(MnoCarrier::matchesCarrier('+8801612345678', 'RB'))->toBeTrue();
    expect(MnoCarrier::matchesCarrier('+8801812345678', 'RB'))->toBeTrue();
});

test('matchesCarrier returns false for non-Bangladesh phone', function () {
    expect(MnoCarrier::matchesCarrier('+919876543210', 'GP'))->toBeFalse();
});

test('matchesCarrier returns false for invalid MNO code', function () {
    expect(MnoCarrier::matchesCarrier('+8801712345678', 'XX'))->toBeFalse();
});
