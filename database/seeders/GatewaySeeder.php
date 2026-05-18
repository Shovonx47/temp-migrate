<?php

namespace Database\Seeders;

use App\Enums\SenderType;
use App\Models\Gateway;
use Illuminate\Database\Seeder;

class GatewaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $gateways = [
            [
                'name' => 'ReveSMS',
                'driver' => 'revesms',
                'credentials' => [
                    'base_url' => 'http://localhost:3002',
                    'api_key' => 'PUT_YOUR_API_KEY',
                    'secret_key' => 'PUT_YOUR_SECRET_KEY',
                ],
                'config' => [],
                'is_enabled' => true,
                'supports_bulk' => true,
                'senders' => [
                    '8809600000000' => SenderType::Numeric,
                    'ReveSender' => SenderType::AlphaNumeric,
                    'RSHORT' => SenderType::Shortcode,
                ],
            ],
            [
                'name' => 'QSMS',
                'driver' => 'qsms',
                'credentials' => [
                    'base_url' => 'http://localhost:3001',
                    'api_key' => 'PUT_YOUR_API_KEY',
                    'client_id' => 'PUT_YOUR_CLIENT',
                ],
                'config' => [],
                'is_enabled' => true,
                'supports_bulk' => true,
                'senders' => [
                    '8809600000033' => SenderType::Numeric,
                    'QSENDER' => SenderType::AlphaNumeric,
                    'QSHORT' => SenderType::Shortcode,
                ],
            ],
        ];

        foreach ($gateways as $data) {
            $senders = $data['senders'] ?? [];
            unset($data['senders']);

            $gateway = Gateway::create($data);

            $gateway->senders()->createMany(
                collect($senders)->map(fn ($type, $sender_id) => [
                    'sender_id' => $sender_id,
                    'type' => $type,
                    'is_enabled' => true,
                ])->values()->all()
            );
        }
    }
}
