<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Operator;
use Illuminate\Database\Seeder;

class OperatorSeeder extends Seeder
{
    public function run(): void
    {
        $bd = Country::where('code', '880')->first();

        if (! $bd) {
            return;
        }

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
                    'masking_price' => 0.45,
                    'non_masking_price' => 0.40,
                    'is_active' => true,
                ],
            );
        }
    }
}
