<?php

use App\Models\Gateway;
use Illuminate\Database\Migrations\Migration;

class AddInfozillionGateway extends Migration
{
    public function up(): void
    {
        Gateway::firstOrCreate(
            ['driver' => 'infozillion'],
            [
                'name' => 'InfoZillion (MNPSP)',
                'credentials' => [
                    'base_url' => env('INFOZILLION_BASE_URL', 'https://api.mnpspbd.com'),
                    'api_type' => env('INFOZILLION_API_TYPE', 'mno'),
                    'username' => env('INFOZILLION_USERNAME', ''),
                    'password' => env('INFOZILLION_PASSWORD', ''),
                    'bill_msisdn' => env('INFOZILLION_BILL_MSISDN', ''),
                    'api_key' => env('INFOZILLION_API_KEY', ''),
                    'username_secondary' => env('INFOZILLION_USERNAME_SECONDARY', ''),
                    'password_secondary' => env('INFOZILLION_PASSWORD_SECONDARY', ''),
                    'bill_msisdn_secondary' => env('INFOZILLION_BILL_MSISDN_SECONDARY', ''),
                    'transaction_type' => env('INFOZILLION_TRANSACTION_TYPE', 'T'),
                    'message_type' => env('INFOZILLION_MESSAGE_TYPE', '1'),
                    'is_long_sms' => false,
                    'campaign_id' => env('INFOZILLION_CAMPAIGN_ID', ''),
                ],
                'config' => [],
                'requests_per_minute' => 60,
                'is_enabled' => false,
                'supports_bulk' => true,
            ],
        );
    }

    public function down(): void
    {
        Gateway::where('driver', 'infozillion')->delete();
    }
}
