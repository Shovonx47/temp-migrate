<?php

namespace App\Filament\Customer\Resources\Messaging\Messages\Pages;

use App\Enums\BatchStatus;
use App\Enums\SmsApproval;
use App\Filament\Customer\Resources\Messaging\Batches\BatchResource;
use App\Filament\Customer\Resources\Messaging\Messages\MessageResource;
use App\Jobs\ProcessBatchJob;
use App\Models\Batch;
use App\Models\Message;
use App\Models\Sender;
use App\Models\User;
use App\Support\MessageMetrics;
use App\Support\OperatorRateResolver;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;
use Illuminate\Validation\Rules\Exists;
use Throwable;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class CreateQuickMessage extends CreateRecord
{
    protected static string $resource = MessageResource::class;

    protected static bool $canCreateAnother = false;

    public function getTitle(): string
    {
        return 'Quick Message';
    }

    public function getBreadcrumb(): string
    {
        return 'Quick Message';
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSendNowFormAction(),
            $this->getScheduleFormAction(),
        ];
    }

    protected function getSendNowFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Send Now')
            ->icon(Heroicon::PaperAirplane)
            ->color('primary');
    }

    protected function getCreateFormAction(): Action
    {
        return $this->getSendNowFormAction();
    }

    protected function getScheduleFormAction(): Action
    {
        return Action::make('scheduleMessage')
            ->label('Schedule Message')
            ->icon(Heroicon::Clock)
            ->color('gray')
            ->modalHeading('Schedule Quick Message')
            ->modalDescription('Choose the exact day and timeline when your SMS message should be automatically sent.')
            ->modalIcon(Heroicon::Clock)
            ->modalIconColor('primary')
            ->modalWidth('lg')
            ->modalAlignment(Alignment::Center)
            ->modalSubmitActionLabel('Confirm & Schedule')
            ->modalCancelActionLabel('Cancel')
            ->schema([
                View::make('filament.schemas.components.schedule-banner')
                    ->columnSpanFull(),
                DatePicker::make('scheduled_date')
                    ->label('Select Day')
                    ->prefixIcon(Heroicon::CalendarDays)
                    ->native(false)
                    ->minDate(now()->toDateString())
                    ->default(now()->toDateString())
                    ->required()
                    ->closeOnDateSelection()
                    ->columnSpanFull(),
                Grid::make(3)
                    ->schema([
                        Select::make('scheduled_hour')
                            ->label('Hour')
                            ->prefixIcon(Heroicon::Clock)
                            ->options([
                                '01' => '01',
                                '02' => '02',
                                '03' => '03',
                                '04' => '04',
                                '05' => '05',
                                '06' => '06',
                                '07' => '07',
                                '08' => '08',
                                '09' => '09',
                                '10' => '10',
                                '11' => '11',
                                '12' => '12',
                            ])
                            ->default(now()->addMinutes(10)->format('h'))
                            ->required(),
                        Select::make('scheduled_minute')
                            ->label('Minute')
                            ->options(array_combine(
                                array_map(fn (int $m): string => sprintf('%02d', $m), range(0, 59)),
                                array_map(fn (int $m): string => sprintf('%02d', $m), range(0, 59)),
                            ))
                            ->default(now()->addMinutes(10)->format('i'))
                            ->optionsLimit(60)
                            ->required(),
                        ToggleButtons::make('scheduled_period')
                            ->label('AM / PM')
                            ->options([
                                'AM' => 'AM',
                                'PM' => 'PM',
                            ])
                            ->colors([
                                'AM' => 'info',
                                'PM' => 'warning',
                            ])
                            ->default(now()->addMinutes(10)->format('A'))
                            ->inline()
                            ->required(),
                    ]),
            ])
            ->action(function (array $data): void {
                $formData = $this->form->getState();

                $rawDate = $data['scheduled_date'] instanceof \DateTimeInterface
                    ? $data['scheduled_date']->format('Y-m-d')
                    : Carbon::parse((string) $data['scheduled_date'])->format('Y-m-d');

                $hour = str_pad((string) ($data['scheduled_hour'] ?? '12'), 2, '0', STR_PAD_LEFT);
                $minute = str_pad((string) ($data['scheduled_minute'] ?? '00'), 2, '0', STR_PAD_LEFT);
                $period = strtoupper((string) ($data['scheduled_period'] ?? 'AM'));

                $scheduledAt = Carbon::createFromFormat('Y-m-d h:i A', "{$rawDate} {$hour}:{$minute} {$period}", config('app.timezone'));

                if ($scheduledAt->lt(now()->subMinute())) {
                    Notification::make()
                        ->title('Invalid Schedule Time')
                        ->body('The selected schedule time has already passed. Please select a time in the future.')
                        ->icon(Heroicon::ExclamationTriangle)
                        ->danger()
                        ->send();

                    $this->halt();
                }

                $body = (string) ($formData['body'] ?? '');
                $phones = array_values(array_filter(array_map('trim', $formData['phones'] ?? [])));
                $recipients = array_map(
                    fn (string $phone): array => ['phone' => $phone, 'values' => []],
                    $phones,
                );

                $this->assertAffordable($body, $recipients, (int) ($formData['sender_id'] ?? 0));

                /** @var User $user */
                $user = Auth::user();
                $requiresApproval = $user->requiresSmsApproval();
                $clientIp = request()->ip();

                $batch = DB::transaction(fn (): Batch => Batch::create([
                    'user_id' => $user->id,
                    'sender_id' => $formData['sender_id'] ?? null,
                    'body' => $body,
                    'source_type' => 'manual',
                    'source_ip' => $clientIp,
                    'source_config' => [
                        'recipients' => $recipients,
                        'ip' => $clientIp,
                    ],
                    'status' => BatchStatus::Scheduled,
                    'scheduled_at' => $scheduledAt,
                    'approval' => $requiresApproval ? SmsApproval::Pending : null,
                ]));

                Notification::make()
                    ->title('Message scheduled successfully')
                    ->body("Your message has been scheduled for {$scheduledAt->format('d M Y \a\t h:i A')}.")
                    ->icon(Heroicon::CheckCircle)
                    ->success()
                    ->send();

                $this->redirect(BatchResource::getUrl('view', ['record' => $batch]));
            });
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make()
                    ->columns()
                    ->schema([
                        Select::make('sender_id')
                            ->label('Sender ID')
                            ->prefixIcon(Heroicon::Identification)
                            ->options(fn () => Auth::user()
                                ->senders()
                                ->select(['senders.sender_id', 'senders.id'])
                                ->pluck('sender_id', 'id')
                                ->all()
                            )
                            ->default(function () {
                                $senders = Auth::user()?->senders()->pluck('senders.id');

                                return ($senders && $senders->count() === 1) ? $senders->first() : null;
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->exists(
                                table: 'sender_user',
                                column: 'sender_id',
                                modifyRuleUsing: fn (Exists $rule) => $rule->where('user_id', Auth::id())
                            ),
                        PhoneInput::make('phones')
                            ->tags()
                            ->suggestions(fn () => $this->getRecentRecipientNumbers())
                            ->label('Phone Numbers')
                            ->placeholder('Input Phone Numbers')
                            ->required()
                            ->columnSpanFull(),
                        Textarea::make('body')
                            ->label('Message')
                            ->placeholder('Type your message')
                            ->rows(5)
                            ->required()
                            ->live(onBlur: true)
                            ->extraInputAttributes(['data-sms-body' => 'true'])
                            ->columnSpanFull(),
                        View::make('filament.schemas.components.sms-stats')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws Halt
     * @throws Throwable
     */
    protected function handleRecordCreation(array $data): Model
    {
        $body = (string) ($data['body'] ?? '');
        $phones = array_values(array_filter(array_map('trim', $data['phones'] ?? [])));
        $recipients = array_map(
            fn (string $phone): array => ['phone' => $phone, 'values' => []],
            $phones,
        );

        $this->assertAffordable($body, $recipients, (int) ($data['sender_id'] ?? 0));

        /** @var User $user */
        $user = Auth::user();
        $requiresApproval = $user->requiresSmsApproval();
        $clientIp = request()->ip();

        $batch = DB::transaction(fn (): Batch => Batch::create([
            'user_id' => $user->id,
            'sender_id' => $data['sender_id'] ?? null,
            'body' => $body,
            'source_type' => 'manual',
            'source_ip' => $clientIp,
            'source_config' => [
                'recipients' => $recipients,
                'ip' => $clientIp,
            ],
            'status' => $requiresApproval ? BatchStatus::Pending : BatchStatus::Processing,
            'approval' => $requiresApproval ? SmsApproval::Pending : null,
        ]));

        if (! $requiresApproval) {
            ProcessBatchJob::dispatchSync(
                $batch,
                'manual',
                $batch->source_config,
                $body,
                [],
                syncSending: true,
            );
        }

        $freshBatch = $batch->fresh();
        $isSuccess = $freshBatch && $freshBatch->status === BatchStatus::Completed;

        Notification::make()
            ->title($requiresApproval ? 'Awaiting approval' : ($isSuccess ? 'Message sent successfully' : 'Messages are being processed'))
            ->body($requiresApproval
                ? 'Your message batch is pending admin approval before sending.'
                : ($isSuccess
                    ? 'Your quick message has been sent successfully.'
                    : 'We will notify you when the recipients are ready to send.'
                )
            )
            ->icon($requiresApproval ? Heroicon::Inbox : Heroicon::CheckCircle)
            ->status($requiresApproval ? 'info' : ($isSuccess ? 'success' : 'info'))
            ->send();

        return $batch;
    }

    protected function getCreatedNotification(): ?Notification
    {
        return null;
    }

    protected function getRedirectUrl(): string
    {
        return BatchResource::getUrl('view', ['record' => $this->record]);
    }

    /**
     * @param  array<int, array{phone: string, values: array<string, string>}>  $recipients
     *
     * @throws Halt
     */
    private function assertAffordable(string $body, array $recipients, int $senderId): void
    {
        $user = Auth::user();
        $recipientCount = count($recipients);

        if ($recipientCount === 0) {
            return;
        }

        $sender = $senderId > 0 ? Sender::find($senderId) : null;

        if (! $sender || $sender->type === null) {
            return;
        }

        $resolver = OperatorRateResolver::forUser($user);
        $isMasking = $sender->type->isMasking();
        $rates = $resolver->ratesForUser($isMasking);

        if ($rates === []) {
            $typeName = $isMasking ? 'Masking' : 'Non-masking';
            $planName = $resolver->getPlanName();
            $planMsg = $planName ? " in your assigned tariff plan [{$planName}]" : ' in your tariff plan';

            Notification::make()
                ->title("{$typeName} rate not configured")
                ->body("Please set your {$typeName} rate{$planMsg} before sending messages.")
                ->icon(Heroicon::ExclamationTriangle)
                ->danger()
                ->send();

            $this->halt();
        }

        $maxRate = max($rates);

        $parts = MessageMetrics::for($body)->parts;
        $estimated = round($maxRate * $parts * $recipientCount, 4);

        if ((float) $user->balance >= $estimated) {
            return;
        }

        $shortfall = number_format(max($estimated - (float) $user->balance, 0), 2);

        Notification::make()
            ->title('Insufficient balance')
            ->body('This batch needs up to '.Number::currency($estimated, 'BDT').'. Top up '.Number::currency($shortfall, 'BDT').' to send.')
            ->icon(Heroicon::Banknotes)
            ->danger()
            ->send();

        $this->halt();
    }

    /**
     * Returns a deduplicated list of recent phone numbers to use as suggestions
     * in the Phone Numbers tags field. Numbers are pulled from:
     *   1. The 100 most-recently sent outgoing messages (messages table).
     *   2. The 20 most-recent manual batches' recipient lists.
     *
     * @return array<string>
     */
    public function getRecentRecipientNumbers(): array
    {
        $user = Auth::user();
        if (! $user) {
            return [];
        }

        // Pull from recent outgoing messages.
        $messageNumbers = Message::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->whereNotNull('to')
            ->where('to', '!=', '')
            ->latest('id')
            ->limit(100)
            ->pluck('to')
            ->map(fn ($to) => (string) $to)
            ->all();

        // Pull from recent manual batches source_config.
        $batchRecipients = [];
        $recentBatches = Batch::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('source_type', 'manual')
            ->latest('id')
            ->limit(20)
            ->get(['source_config']);

        foreach ($recentBatches as $batch) {
            $recipients = $batch->source_config['recipients'] ?? [];
            foreach ($recipients as $recipient) {
                if (! empty($recipient['phone'])) {
                    $batchRecipients[] = (string) $recipient['phone'];
                }
            }
        }

        $combined = array_merge($messageNumbers, $batchRecipients);
        $unique = array_values(array_unique(array_filter(array_map('trim', $combined))));

        return array_slice($unique, 0, 10);
    }
}
