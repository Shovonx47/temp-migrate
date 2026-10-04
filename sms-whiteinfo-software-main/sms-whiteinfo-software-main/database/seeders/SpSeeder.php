<?php

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\User;
use Illuminate\Database\Seeder;

class SpSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(['email' => 'sp@example.com'], [
            'name' => 'Service Provider',
            'phone' => '+8801700000003',
            'type' => UserType::Sp,
            'password' => bcrypt('sp'),
            'email_verified_at' => now(),
            'status' => UserStatus::Approved,
            'balance' => 10000,
            'api_access' => true,
            'sms_auto_approved' => true,
        ]);
    }
}
