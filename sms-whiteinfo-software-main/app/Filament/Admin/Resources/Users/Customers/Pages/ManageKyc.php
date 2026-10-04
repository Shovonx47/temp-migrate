<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\Customers\Pages;

use App\Enums\KycPermission;
use App\Enums\KycStatus;
use App\Filament\Admin\Resources\Users\Customers\CustomerResource;
use App\Models\KycAuditLog;
use App\Models\User;
use App\Services\KycGate;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class ManageKyc extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = CustomerResource::class;

    protected string $view = 'filament.admin.pages.kyc.manage-kyc';

    protected static ?string $title = 'Manage KYC';

    public static function canAccess(array $parameters = []): bool
    {
        return KycGate::allows(KycPermission::Edit)
            || KycGate::allows(KycPermission::DocumentReplace);
    }

    public ?array $nidData         = [];
    public ?array $livePhotoData   = [];
    public ?array $tradeLicData    = [];
    public ?array $verifyData      = [];

    /** @var User */
    public $record;

    public function mount(int|string $record): void
    {
        $this->record = User::findOrFail($record);

        // Pre-fill the forms with current values
        $this->nidForm->fill([
            'nid_number' => $this->record->nid_number,
            'front_nid'  => $this->record->front_nid ? [$this->record->front_nid] : [],
            'back_nid'   => $this->record->back_nid ? [$this->record->back_nid] : [],
        ]);

        $this->livePhotoForm->fill([
            'live_photo' => $this->record->live_photo ? [$this->record->live_photo] : [],
        ]);

        $this->tradeLicForm->fill([
            'trade_license_number' => $this->record->trade_license_number,
            'trade_license_file'   => $this->record->trade_license_file ? [$this->record->trade_license_file] : [],
        ]);

        $this->verifyForm->fill([
            'kyc_status'        => $this->record->kyc_status?->value,
            'nid_verified'      => $this->record->nid_verified,
            'live_photo_verified' => $this->record->live_photo_verified,
        ]);
    }

    // ─── NID Form ──────────────────────────────────────────────────────────────

    protected function nidForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('nidData')
            ->components([
                Section::make('NID / Identity Documents')
                    ->description('Edit the NID number or replace NID photos. Only changed fields will be saved.')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        TextInput::make('nid_number')
                            ->label('NID Number')
                            ->placeholder('e.g. 1234567890')
                            ->maxLength(30),

                        FileUpload::make('front_nid')
                            ->label('NID Front Photo')
                            ->image()
                            ->maxSize(4096)
                            ->directory('ids')
                            ->disk('private')
                            ->visibility('private')
                            ->nullable(),

                        FileUpload::make('back_nid')
                            ->label('NID Back Photo')
                            ->image()
                            ->maxSize(4096)
                            ->directory('ids')
                            ->disk('private')
                            ->visibility('private')
                            ->nullable(),
                    ])
                    ->columns(2)
                    ->footerActions([
                        Action::make('saveNid')
                            ->label('Save NID Changes')
                            ->icon('heroicon-o-check')
                            ->color('primary')
                            ->action('saveNidSection'),
                    ]),
            ]);
    }

    // ─── Live Photo Form ────────────────────────────────────────────────────────

    protected function livePhotoForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('livePhotoData')
            ->components([
                Section::make('Live Selfie Photo')
                    ->description('Replace the customer\'s live selfie. Only the new photo will be saved.')
                    ->icon('heroicon-o-camera')
                    ->schema([
                        FileUpload::make('live_photo')
                            ->label('Live Photo')
                            ->image()
                            ->maxSize(4096)
                            ->directory('live_photos')
                            ->disk('private')
                            ->visibility('private')
                            ->nullable(),
                    ])
                    ->footerActions([
                        Action::make('saveLivePhoto')
                            ->label('Save Live Photo')
                            ->icon('heroicon-o-check')
                            ->color('primary')
                            ->action('saveLivePhotoSection'),
                    ]),
            ]);
    }

    // ─── Trade License Form ─────────────────────────────────────────────────────

    protected function tradeLicForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('tradeLicData')
            ->components([
                Section::make('Trade License')
                    ->description('Edit trade license number or replace the document. Fields not changed will remain.')
                    ->icon('heroicon-o-building-office-2')
                    ->schema([
                        TextInput::make('trade_license_number')
                            ->label('Trade License Number')
                            ->placeholder('e.g. TL-2024-123456')
                            ->maxLength(100),

                        FileUpload::make('trade_license_file')
                            ->label('Trade License Document')
                            ->image()
                            ->maxSize(4096)
                            ->directory('trade_licenses')
                            ->disk('private')
                            ->visibility('private')
                            ->nullable(),
                    ])
                    ->columns(2)
                    ->footerActions([
                        Action::make('saveTradeLic')
                            ->label('Save Trade License')
                            ->icon('heroicon-o-check')
                            ->color('primary')
                            ->action('saveTradeLicSection'),
                    ]),
            ]);
    }

    // ─── Verification Status Form ───────────────────────────────────────────────

    protected function verifyForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('verifyData')
            ->components([
                Section::make('Verification Status Override')
                    ->description('Manually override individual verification flags.')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        \Filament\Forms\Components\Toggle::make('nid_verified')
                            ->label('NID Verified')
                            ->helperText('Mark NID documents as manually verified.'),

                        \Filament\Forms\Components\Toggle::make('live_photo_verified')
                            ->label('Live Photo Verified')
                            ->helperText('Mark live selfie as manually verified.'),
                    ])
                    ->columns(2)
                    ->footerActions([
                        Action::make('saveVerify')
                            ->label('Save Verification Flags')
                            ->icon('heroicon-o-check')
                            ->color('primary')
                            ->action('saveVerifySection'),
                    ]),
            ]);
    }

    // ─── Save Handlers ──────────────────────────────────────────────────────────

    public function saveNidSection(): void
    {
        $data    = $this->nidForm->getState();
        $updated = [];

        // NID Number — only if it changed
        $newNidNumber = $data['nid_number'] ?? null;
        if ($newNidNumber !== $this->record->nid_number) {
            $old = $this->record->nid_number;
            $this->record->nid_number = $newNidNumber;
            $updated['nid_number'] = ['old' => $old, 'new' => $newNidNumber];
        }

        // NID Front — extract from array if changed
        $frontNid = $this->extractFileFromUpload($data['front_nid'] ?? null);
        if ($frontNid && $frontNid !== $this->record->front_nid) {
            $old = $this->record->front_nid;
            $this->record->front_nid = $frontNid;
            $updated['nid_front'] = ['old' => basename((string) $old), 'new' => basename($frontNid)];
        }

        // NID Back
        $backNid = $this->extractFileFromUpload($data['back_nid'] ?? null);
        if ($backNid && $backNid !== $this->record->back_nid) {
            $old = $this->record->back_nid;
            $this->record->back_nid = $backNid;
            $updated['nid_back'] = ['old' => basename((string) $old), 'new' => basename($backNid)];
        }

        if (empty($updated)) {
            Notification::make()->title('No Changes')->body('Nothing was changed.')->warning()->send();
            return;
        }

        $this->record->save();

        foreach ($updated as $field => $values) {
            KycAuditLog::record($this->record, 'updated', $field, $values['old'], $values['new']);
        }

        Notification::make()->title('NID Section Saved')->body('Changes saved and logged.')->success()->send();
    }

    public function saveLivePhotoSection(): void
    {
        $data      = $this->livePhotoForm->getState();
        $livePhoto = $this->extractFileFromUpload($data['live_photo'] ?? null);

        if (! $livePhoto || $livePhoto === $this->record->live_photo) {
            Notification::make()->title('No Changes')->body('No new live photo was uploaded.')->warning()->send();
            return;
        }

        $old = $this->record->live_photo;
        $this->record->live_photo = $livePhoto;
        $this->record->save();

        KycAuditLog::record($this->record, 'document_replaced', 'live_photo', basename((string) $old), basename($livePhoto));

        Notification::make()->title('Live Photo Saved')->body('Live photo updated and logged.')->success()->send();
    }

    public function saveTradeLicSection(): void
    {
        $data    = $this->tradeLicForm->getState();
        $updated = [];

        $newNumber = $data['trade_license_number'] ?? null;
        if ($newNumber !== $this->record->trade_license_number) {
            $old = $this->record->trade_license_number;
            $this->record->trade_license_number = $newNumber;
            $updated['trade_license_number'] = ['old' => $old, 'new' => $newNumber];
        }

        $licFile = $this->extractFileFromUpload($data['trade_license_file'] ?? null);
        if ($licFile && $licFile !== $this->record->trade_license_file) {
            $old = $this->record->trade_license_file;
            $this->record->trade_license_file      = $licFile;
            $this->record->trade_license_submitted = true;
            $updated['trade_license_file'] = ['old' => basename((string) $old), 'new' => basename($licFile)];
        }

        if (empty($updated)) {
            Notification::make()->title('No Changes')->body('Nothing was changed.')->warning()->send();
            return;
        }

        $this->record->save();

        foreach ($updated as $field => $values) {
            KycAuditLog::record($this->record, 'updated', $field, $values['old'], $values['new']);
        }

        Notification::make()->title('Trade License Saved')->body('Changes saved and logged.')->success()->send();
    }

    public function saveVerifySection(): void
    {
        $data    = $this->verifyForm->getState();
        $updated = [];

        $newNidVerified = (bool) ($data['nid_verified'] ?? false);
        if ($newNidVerified !== (bool) $this->record->nid_verified) {
            $old = $this->record->nid_verified ? 'true' : 'false';
            $this->record->nid_verified = $newNidVerified;
            $updated['nid_verified'] = ['old' => $old, 'new' => $newNidVerified ? 'true' : 'false'];
        }

        $newLiveVerified = (bool) ($data['live_photo_verified'] ?? false);
        if ($newLiveVerified !== (bool) $this->record->live_photo_verified) {
            $old = $this->record->live_photo_verified ? 'true' : 'false';
            $this->record->live_photo_verified = $newLiveVerified;
            $updated['live_photo_verified'] = ['old' => $old, 'new' => $newLiveVerified ? 'true' : 'false'];
        }

        if (empty($updated)) {
            Notification::make()->title('No Changes')->body('Nothing was changed.')->warning()->send();
            return;
        }

        $this->record->save();

        foreach ($updated as $field => $values) {
            KycAuditLog::record($this->record, 'updated', $field, $values['old'], $values['new']);
        }

        Notification::make()->title('Verification Flags Saved')->body('Flags updated and logged.')->success()->send();
    }

    // ─── Helpers ────────────────────────────────────────────────────────────────

    /**
     * FileUpload returns either an array of paths, a string, or null.
     * Normalize to a plain string path.
     */
    protected function extractFileFromUpload(mixed $value): ?string
    {
        if (is_array($value)) {
            return ! empty($value) ? (string) reset($value) : null;
        }

        return filled($value) ? (string) $value : null;
    }

    // ─── Header Actions ─────────────────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back to Customer')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn () => CustomerResource::getUrl('view', ['record' => $this->record->id])),
        ];
    }
}
