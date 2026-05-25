<?php

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\Sender;
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

        $user = User::updateOrCreate(['email' => 'customer@example.com'], [
            'name' => 'Customer',
            'phone' => '+8801700000002',
            'type' => UserType::Customer,
            'password' => bcrypt('customer'),
            'email_verified_at' => now(),
            'balance' => 50,
            'status' => UserStatus::Approved,
            'front_nid' => 'path/to/front_nid.jpg',
            'back_nid' => 'path/to/back_nid.jpg',
            'api_access' => true,
            'sms_auto_approved' => true,
        ]);

        $senders = Sender::inRandomOrder()
            ->limit(3)
            ->pluck('id');

        $user->senders()->sync($senders);
    }
}
