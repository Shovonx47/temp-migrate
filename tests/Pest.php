<?php

use App\Models\Country;
use App\Models\Operator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

require_once __DIR__.'/Helpers/LivewireHelper.php';

/**
 * Seed the default Bangladesh country + operator catalog with prices. Shared by
 * any test that exercises the billing pipeline (message batches, campaigns).
 */
function seedDefaultPricing(float $masking = 0.5, float $nonMasking = 0.3): Country
{
    $bd = Country::firstOrCreate(
        ['code' => '880'],
        ['name' => 'Bangladesh', 'iso_alpha2' => 'BD', 'is_active' => true],
    );

    $operators = [
        ['name' => 'Grameenphone', 'codes' => [13, 17]],
        ['name' => 'Banglalink', 'codes' => [14, 19]],
        ['name' => 'Teletalk', 'codes' => [15]],
        ['name' => 'Airtel', 'codes' => [16]],
        ['name' => 'Robi', 'codes' => [18]],
    ];

    foreach ($operators as $row) {
        Operator::updateOrCreate(
            ['country_id' => $bd->id, 'name' => $row['name']],
            [
                'codes' => $row['codes'],
                'masking_price' => $masking,
                'non_masking_price' => $nonMasking,
                'is_active' => true,
            ],
        );
    }

    return $bd;
}
