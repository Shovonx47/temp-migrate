<?php

namespace Database\Seeders;

use App\Enums\DepositStatus;
use App\Enums\MessageStatus;
use App\Enums\SenderType;
use App\Enums\UserType;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Deposit;
use App\Models\Gateway;
use App\Models\Group;
use App\Models\Message;
use App\Models\Sender;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DemoSeeder extends Seeder
{
    private Collection $customers;

    private Collection $gateways;

    private Collection $senders;

    private Collection $groups;

    private Collection $templates;

    public function run(): void
    {
        DB::beginTransaction();

        try {
            $this->seedUsers();
            $this->seedGateways();
            $this->seedSenders();
            $this->seedGroups();
            $this->seedContacts();
            $this->seedTemplates();
            $this->seedCampaigns();
            $this->seedMessages();
            $this->seedDeposits();
            $this->seedTransactions();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function seedUsers(): void
    {
        $users = [
            ['name' => 'Rahim Uddin',      'email' => 'rahim@sendora.test',    'phone' => '+8801711000001'],
            ['name' => 'Karim Traders',    'email' => 'karim@sendora.test',    'phone' => '+8801711000002'],
            ['name' => 'Desh Mart',        'email' => 'deshmart@sendora.test', 'phone' => '+8801711000003'],
            ['name' => 'Nabil Enterprise', 'email' => 'nabil@sendora.test',    'phone' => '+8801711000004'],
            ['name' => 'Rafiq & Sons',     'email' => 'rafiq@sendora.test',    'phone' => '+8801711000005'],
            ['name' => 'City Connect',     'email' => 'city@sendora.test',     'phone' => '+8801711000006'],
            ['name' => 'Hasan Logistics',  'email' => 'hasan@sendora.test',    'phone' => '+8801711000007'],
            ['name' => 'Rupali Store',     'email' => 'rupali@sendora.test',   'phone' => '+8801711000008'],
            ['name' => 'Bhuiyan Holdings', 'email' => 'bhuiyan@sendora.test',  'phone' => '+8801711000009'],
            ['name' => 'Alam & Brothers',  'email' => 'alam@sendora.test',     'phone' => '+8801711000010'],
        ];

        $userData = collect($users)->map(fn (array $user) => [
            ...$user,
            'type' => UserType::Customer->value,
            'approval' => fake()->randomElement(['approved', 'pending', 'rejected']),
            'balance' => fake()->numberBetween(100, 5000),
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ])->toArray();

        DB::table('users')->insert($userData);

        $this->customers = User::all();
    }

    private function seedGateways(): void
    {
        $gateways = [
            ['name' => 'SSLCommerz SMS', 'driver' => 'sslcommerz', 'is_enabled' => true,  'supports_bulk' => false],
            ['name' => 'Twilio',         'driver' => 'twilio',     'is_enabled' => true,  'supports_bulk' => true],
            ['name' => 'Vonage',         'driver' => 'vonage',     'is_enabled' => true,  'supports_bulk' => true],
            ['name' => 'Infobip',        'driver' => 'infobip',    'is_enabled' => false, 'supports_bulk' => true],
            ['name' => 'Kannel',         'driver' => 'kannel',     'is_enabled' => false, 'supports_bulk' => false],
        ];

        $gatewayData = collect($gateways)->map(fn (array $gateway) => [
            ...$gateway,
            'created_at' => now(),
            'updated_at' => now(),
        ])->toArray();

        DB::table('gateways')->insert($gatewayData);

        $this->gateways = Gateway::all();
    }

    private function seedSenders(): void
    {
        $sendersData = [];

        $senderSpecs = [
            'sslcommerz' => [
                ['sender_id' => 'SENDORA', 'type' => SenderType::AlphaNumeric, 'is_enabled' => true],
                ['sender_id' => '8809613000001', 'type' => SenderType::Numeric, 'is_enabled' => false],
            ],
            'twilio' => [
                ['sender_id' => '+14155238886', 'type' => SenderType::Numeric, 'is_enabled' => true],
                ['sender_id' => 'TXTSVC', 'type' => SenderType::AlphaNumeric, 'is_enabled' => true],
            ],
            'vonage' => [
                ['sender_id' => 'VonageAPI', 'type' => SenderType::AlphaNumeric, 'is_enabled' => true],
            ],
            'infobip' => [
                ['sender_id' => 'InfoSMS', 'type' => SenderType::AlphaNumeric, 'is_enabled' => false],
                ['sender_id' => '2020', 'type' => SenderType::Shortcode, 'is_enabled' => false],
            ],
            'kannel' => [
                ['sender_id' => '8801700000999', 'type' => SenderType::Numeric, 'is_enabled' => false],
            ],
        ];

        foreach ($this->gateways as $gateway) {
            $specs = $senderSpecs[$gateway->driver] ?? [];
            foreach ($specs as $spec) {
                $sendersData[] = [
                    'gateway_id' => $gateway->id,
                    'sender_id' => $spec['sender_id'],
                    'type' => $spec['type']->value,
                    'is_enabled' => $spec['is_enabled'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('senders')->insert($sendersData);

        $this->senders = Sender::all();

        // Assign senders to users
        $enabledSenders = $this->senders->where('is_enabled', true)->toArray();

        foreach ($this->customers as $customer) {
            $assigned = collect($enabledSenders)
                ->random(fake()->numberBetween(1, 3))
                ->pluck('id')
                ->toArray();

            $pivotData = collect($assigned)->map(fn (int $senderId) => [
                'sender_id' => $senderId,
                'user_id' => $customer->id,
            ])->toArray();

            DB::table('sender_user')->insert($pivotData);
        }
    }

    private function seedGroups(): void
    {
        $groupNames = [
            ['name' => 'VIP Customers',     'description' => 'High value returning customers'],
            ['name' => 'New Registrations', 'description' => 'Users registered in last 30 days'],
            ['name' => 'Dhaka Zone',        'description' => 'Customers in Dhaka metropolitan area'],
            ['name' => 'Chittagong Zone',   'description' => 'Customers in Chittagong division'],
            ['name' => 'Inactive Users',    'description' => 'No activity in last 6 months'],
            ['name' => 'Promo Subscribers', 'description' => 'Opted in for promotional messages'],
            ['name' => 'Alerts Only',       'description' => 'Transactional and alert recipients'],
            ['name' => 'Test Group',        'description' => 'Internal testing contacts'],
        ];

        $groupsData = [];

        foreach ($this->customers as $customer) {
            $assigned = fake()->randomElements($groupNames, fake()->numberBetween(2, 5));
            foreach ($assigned as $data) {
                $groupsData[] = [
                    'user_id' => $customer->id,
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('groups')->insert($groupsData);

        $this->groups = Group::all();
    }

    private function seedContacts(): void
    {
        $contactsData = [];

        foreach ($this->customers as $customer) {
            fake()->unique(reset: true);

            $count = fake()->numberBetween(20, 80);

            for ($i = 0; $i < $count; $i++) {
                $contactsData[] = [
                    'user_id' => $customer->id,
                    'name' => fake()->name(),
                    'phone' => '+8801'
                        .fake()->randomElement([3, 4, 5, 6, 7, 8, 9])
                        .fake()->unique()->numerify('########'),
                    'is_blocked' => false,
                    'meta' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('contacts')->insert($contactsData);

        // Assign contacts to groups
        $contacts = Contact::all();
        $groupContactPivot = [];

        foreach ($this->customers as $customer) {
            $customerContacts = $contacts->where('user_id', $customer->id);
            $customerGroups = $this->groups->where('user_id', $customer->id);

            foreach ($customerGroups as $group) {
                $groupContacts = $customerContacts->random(fake()->numberBetween(5, min(20, $customerContacts->count())));
                foreach ($groupContacts as $contact) {
                    $groupContactPivot[] = [
                        'contact_id' => $contact->id,
                        'group_id' => $group->id,
                    ];
                }
            }
        }

        DB::table('contact_group')->insert($groupContactPivot);
    }

    private function seedTemplates(): void
    {
        $templateData = [
            ['name' => 'OTP Verification',     'body' => 'Your OTP code is: {otp}. Valid for 5 minutes. Do not share this with anyone.'],
            ['name' => 'Welcome Message',      'body' => 'Welcome to {company}! We are excited to have you on board.'],
            ['name' => 'Payment Confirmation', 'body' => 'Dear {name}, your payment of BDT {amount} has been received. Txn ID: {txn_id}'],
            ['name' => 'Order Shipped',        'body' => 'Your order #{order_id} has been shipped via {courier}. Track: {tracking_url}'],
            ['name' => 'Appointment Reminder', 'body' => 'Reminder: Your appointment with {doctor} is on {date} at {time}. Reply YES to confirm.'],
            ['name' => 'Flash Sale Alert',     'body' => 'FLASH SALE! {discount}% off on all items. Use code: {promo_code}. Valid till {end_date}.'],
            ['name' => 'Balance Low Warning',  'body' => 'Your SMS balance is low: BDT {balance}. Please recharge to avoid service interruption.'],
            ['name' => 'Eid Greetings',        'body' => 'Eid Mubarak from {company}! May this Eid bring joy and prosperity to you and your family.'],
        ];

        $templatesData = [];

        foreach ($this->customers as $customer) {
            $assigned = fake()->randomElements($templateData, fake()->numberBetween(3, 6));
            foreach ($assigned as $data) {
                $templatesData[] = [
                    'user_id' => $customer->id,
                    'name' => $data['name'],
                    'body' => $data['body'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('templates')->insert($templatesData);

        $this->templates = Template::all();
    }

    private function seedCampaigns(): void
    {
        $campaignsData = [];

        foreach ($this->customers as $customer) {
            $customerTemplates = $this->templates->where('user_id', $customer->id);
            $customerSenders = $this->senders->whereIn('id',
                DB::table('sender_user')
                    ->where('user_id', $customer->id)
                    ->pluck('sender_id')
                    ->toArray()
            );

            if ($customerTemplates->isEmpty() || $customerSenders->isEmpty()) {
                continue;
            }

            // Draft campaigns
            for ($i = 0; $i < fake()->numberBetween(1, 3); $i++) {
                $campaignsData[] = [
                    'user_id' => $customer->id,
                    'template_id' => $customerTemplates->random()->id,
                    'sender_id' => $customerSenders->random()->id,
                    'name' => fake()->sentence(3),
                    'body' => fake()->paragraph(),
                    'status' => 'draft',
                    'scheduled_at' => null,
                    'started_at' => null,
                    'completed_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Scheduled campaigns
            for ($i = 0; $i < fake()->numberBetween(1, 2); $i++) {
                $campaignsData[] = [
                    'user_id' => $customer->id,
                    'template_id' => $customerTemplates->random()->id,
                    'sender_id' => $customerSenders->random()->id,
                    'name' => fake()->sentence(3),
                    'body' => fake()->paragraph(),
                    'status' => 'scheduled',
                    'scheduled_at' => now()->addDays(fake()->numberBetween(1, 7)),
                    'started_at' => null,
                    'completed_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Running campaign
            $campaignsData[] = [
                'user_id' => $customer->id,
                'template_id' => $customerTemplates->random()->id,
                'sender_id' => $customerSenders->random()->id,
                'name' => fake()->sentence(3),
                'body' => fake()->paragraph(),
                'status' => 'running',
                'scheduled_at' => null,
                'started_at' => now()->subHours(fake()->numberBetween(1, 5)),
                'completed_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Completed campaigns
            for ($i = 0; $i < fake()->numberBetween(2, 5); $i++) {
                $startedAt = now()->subDays(fake()->numberBetween(1, 30));
                $campaignsData[] = [
                    'user_id' => $customer->id,
                    'template_id' => $customerTemplates->random()->id,
                    'sender_id' => $customerSenders->random()->id,
                    'name' => fake()->sentence(3),
                    'body' => fake()->paragraph(),
                    'status' => 'completed',
                    'scheduled_at' => null,
                    'started_at' => $startedAt,
                    'completed_at' => $startedAt->addHours(fake()->numberBetween(1, 12)),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Failed campaign (some customers)
            if (fake()->boolean(30)) {
                $startedAt = now()->subHours(fake()->numberBetween(2, 6));
                $campaignsData[] = [
                    'user_id' => $customer->id,
                    'template_id' => $customerTemplates->random()->id,
                    'sender_id' => $customerSenders->random()->id,
                    'name' => fake()->sentence(3),
                    'body' => fake()->paragraph(),
                    'status' => 'failed',
                    'scheduled_at' => null,
                    'started_at' => $startedAt,
                    'completed_at' => $startedAt->addHours(1),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        collect($campaignsData)->chunk(500)->each(fn (Collection $chunk) => DB::table('campaigns')->insert($chunk->toArray()));
    }

    private function seedMessages(): void
    {
        $campaigns = Campaign::all();
        $messagesData = [];

        foreach ($campaigns as $campaign) {
            $count = match ($campaign->status) {
                'draft', 'running' => fake()->numberBetween(50, 200),
                'completed' => fake()->numberBetween(100, 500),
                'failed' => fake()->numberBetween(10, 100),
                default => 0,
            };

            for ($i = 0; $i < $count; $i++) {
                $messagesData[] = [
                    'user_id' => $campaign->user_id,
                    'campaign_id' => $campaign->id,
                    'sender_id' => $campaign->sender_id,
                    'phone' => '+8801'
                        .fake()->randomElement([3, 4, 5, 6, 7, 8, 9])
                        .fake()->numerify('########'),
                    'body' => fake()->paragraph(),
                    'status' => match ($campaign->status) {
                        'running' => fake()->randomElement([MessageStatus::Sent->value, MessageStatus::Delivered->value, MessageStatus::Pending->value]),
                        'completed' => fake()->randomElement([MessageStatus::Delivered->value, MessageStatus::Sent->value, MessageStatus::Failed->value]),
                        'failed' => MessageStatus::Failed->value,
                        default => MessageStatus::Pending->value,
                    },
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Batch insert
        collect($messagesData)->chunk(500)->each(fn (Collection $chunk) => DB::table('messages')->insert($chunk->toArray()));

        // Standalone messages
        $standaloneData = [];
        foreach ($this->customers->random(5) as $customer) {
            $sender = $this->senders
                ->whereIn('id', DB::table('sender_user')->where('user_id', $customer->id)->pluck('sender_id'))
                ->first();

            if (! $sender) {
                continue;
            }

            for ($i = 0; $i < fake()->numberBetween(5, 30); $i++) {
                $standaloneData[] = [
                    'user_id' => $customer->id,
                    'campaign_id' => null,
                    'sender_id' => $sender->id,
                    'to' => '+8801'
                        .fake()->randomElement([3, 4, 5, 6, 7, 8, 9])
                        .fake()->numerify('########'),
                    'body' => fake()->paragraph(),
                    'status' => fake()->randomElement(array_map(fn (MessageStatus $status) => $status->value, MessageStatus::cases())),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (! empty($standaloneData)) {
            collect($standaloneData)->chunk(500)->each(fn (Collection $chunk) => DB::table('messages')->insert($chunk->toArray()));
        }
    }

    private function seedDeposits(): void
    {
        $methods = ['sslcommerz', 'stripe', 'manual'];
        $depositsData = [];

        foreach ($this->customers as $customer) {
            // Completed deposits
            for ($i = 0; $i < fake()->numberBetween(2, 6); $i++) {
                $depositsData[] = [
                    'user_id' => $customer->id,
                    'amount' => fake()->randomFloat(2, 100, 10000),
                    'payment_method' => fake()->randomElement($methods),
                    'status' => DepositStatus::Completed->value,
                    'transaction_id' => fake()->uuid(),
                    'created_at' => now()->subDays(fake()->numberBetween(0, 30)),
                    'updated_at' => now()->subDays(fake()->numberBetween(0, 30)),
                ];
            }

            // Pending deposit
            if (fake()->boolean(40)) {
                $depositsData[] = [
                    'user_id' => $customer->id,
                    'amount' => fake()->randomFloat(2, 100, 10000),
                    'payment_method' => fake()->randomElement($methods),
                    'status' => DepositStatus::Pending->value,
                    'transaction_id' => fake()->uuid(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Failed deposit
            if (fake()->boolean(20)) {
                $depositsData[] = [
                    'user_id' => $customer->id,
                    'amount' => fake()->randomFloat(2, 100, 10000),
                    'payment_method' => fake()->randomElement($methods),
                    'status' => DepositStatus::Failed->value,
                    'transaction_id' => fake()->uuid(),
                    'created_at' => now()->subDays(fake()->numberBetween(1, 15)),
                    'updated_at' => now()->subDays(fake()->numberBetween(0, 15)),
                ];
            }
        }

        collect($depositsData)->chunk(500)->each(fn (Collection $chunk) => DB::table('deposits')->insert($chunk->toArray()));
    }

    private function seedTransactions(): void
    {
        $deposits = Deposit::where('status', DepositStatus::Completed->value)->get();
        $transactionsData = [];

        // Deposit transactions
        foreach ($deposits as $deposit) {
            $balanceBefore = fake()->randomFloat(2, 0, 5000);
            $transactionsData[] = [
                'user_id' => $deposit->user_id,
                'transactionable_type' => 'deposit',
                'transactionable_id' => $deposit->id,
                'amount' => $deposit->amount,
                'direction' => 'credit',
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore + $deposit->amount,
                'description' => null,
                'created_at' => $deposit->created_at,
                'updated_at' => $deposit->created_at,
            ];
        }

        // SMS deduction transactions
        foreach ($this->customers as $customer) {
            $messages = Message::query()
                ->where('user_id', $customer->id)
                ->inRandomOrder()
                ->limit(fake()->numberBetween(5, 20))
                ->get();

            foreach ($messages as $message) {
                $amount = fake()->randomFloat(2, 0.25, 5);
                $balanceBefore = fake()->randomFloat(2, 50, 5000);

                $transactionsData[] = [
                    'user_id' => $customer->id,
                    'transactionable_type' => 'message',
                    'transactionable_id' => $message->id,
                    'amount' => $amount,
                    'direction' => 'debit',
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceBefore - $amount,
                    'description' => null,
                    'created_at' => now()->subDays(fake()->numberBetween(0, 30)),
                    'updated_at' => now()->subDays(fake()->numberBetween(0, 30)),
                ];
            }
        }

        collect($transactionsData)->chunk(500)->each(fn (Collection $chunk) => DB::table('transactions')->insert($chunk->toArray()));
    }
}
