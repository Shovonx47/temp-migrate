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
                    'url' => 'http://localhost:8585',
                    'key' => 'da63720e7ced4657',
                    'secret' => '2258a8b6',
                ],
                'config' => [],
                'is_enabled' => true,
                'supports_bulk' => true,
                'senders' => [
                    '8809600000000' => SenderType::Numeric,
                    'TestSender' => SenderType::AlphaNumeric,
                    'DBBL' => SenderType::Shortcode,
                ],
            ],
            [
                'name' => 'Log (Testing)',
                'driver' => 'log',
                'credentials' => [
                    'filename' => 'sms.log',
                ],
                'config' => [],
                'is_enabled' => true,
                'supports_bulk' => true,
                'senders' => [
                    '8809600000033' => SenderType::Numeric,
                    'LOGGER' => SenderType::AlphaNumeric,
                    'LOG' => SenderType::Shortcode,
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
