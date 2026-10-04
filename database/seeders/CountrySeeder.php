<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        Country::updateOrCreate(
            ['code' => '880'],
            ['name' => 'Bangladesh', 'iso_alpha2' => 'BD', 'is_active' => true],
        );
    }
}
