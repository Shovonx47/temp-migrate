<?php

namespace Database\Seeders;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(['email' => 'superman@example.com'], [
            'name' => 'Superman',
            'phone' => '+8801700000001',
            'type' => UserType::Superman,
            'password' => bcrypt('superman'),
            'email_verified_at' => now(),
        ]);

        User::updateOrCreate(['email' => 'customer@example.com'], [
            'name' => 'Customer',
            'phone' => '+8801700000002',
            'type' => UserType::Customer,
            'password' => bcrypt('customer'),
            'email_verified_at' => now(),
        ]);
    }
}
