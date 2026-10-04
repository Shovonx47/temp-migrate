<?php

use App\Enums\SenderType;
use App\Models\Gateway;
use Illuminate\Database\Migrations\Migration;

class AddAnbernetGateway extends Migration
{
    public function up(): void
    {
        $gateway = Gateway::firstOrCreate(
            ['driver' => 'anbernet'],
            [
                'name' => 'Anbernet',
                'credentials' => [
                    'base_url' => env('ANBERNET_BASE_URL', 'https://rapi.anbernet.com:9978'),
                    'account' => env('ANBERNET_ACCOUNT', ''),
                    'password' => env('ANBERNET_PASSWORD', ''),
                    'token' => env('ANBERNET_TOKEN', ''),
                ],
                'config' => [],
                'requests_per_minute' => 60,
                'is_enabled' => false,
                'supports_bulk' => true,
            ],
        );

        $senders = [
            '8809600000000' => SenderType::Numeric,
            'ANBSENDER' => SenderType::AlphaNumeric,
            'ANBSHORT' => SenderType::Shortcode,
        ];

        foreach ($senders as $senderId => $type) {
            $gateway->senders()->firstOrCreate(
                ['sender_id' => $senderId],
                ['type' => $type, 'is_enabled' => true],
            );
        }
    }

    public function down(): void
    {
        Gateway::where('driver', 'anbernet')->delete();
    }
}
