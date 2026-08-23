<?php

namespace App\Filament\Customer\Resources\Messaging\Messages\Pages;

use App\Enums\BatchStatus;
use App\Enums\SmsApproval;
use App\Filament\Customer\Resources\Messaging\Batches\BatchResource;
use App\Filament\Customer\Resources\Messaging\Messages\MessageResource;
use App\Jobs\ProcessBatchJob;
use App\Models\Batch;
use App\Models\Sender;
use App\Models\User;
use App\Support\MessageMetrics;
use App\Support\OperatorRateResolver;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
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
                            ->label('Phone Numbers')
                            ->placeholder('Type a number, press Enter')
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

        $batch = DB::transaction(fn (): Batch => Batch::create([
            'user_id' => $user->id,
            'sender_id' => $data['sender_id'] ?? null,
            'body' => $body,
            'source_type' => 'manual',
            'source_config' => ['recipients' => $recipients],
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
}
