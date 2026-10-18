<?php

declare(strict_types=1);

namespace App\Filament\Customer\Pages\Account;

use App\Enums\KycStatus;
use App\Filament\Customer\Pages\Dashboard;
use App\Filament\Forms\Components\LivePhotoCapture;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Panel;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class Kyc extends Page implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public function mount(): void
    {
        $user = Filament::auth()->user();

        $fillData = [
            'nid_number' => $user?->nid_number,
            'front_nid' => $user?->front_nid ? [$user->front_nid] : [],
            'back_nid' => $user?->back_nid ? [$user->back_nid] : [],
            'live_photo' => null,
            'trade_license_number' => $user?->trade_license_number,
            'trade_license_file' => $user?->trade_license_file ? [$user->trade_license_file] : [],
        ];

        $this->content->fill($fillData);
    }

    public static function getSlug(?Panel $panel = null): string
    {
        return 'account/kyc';
    }

    public function getTitle(): string
    {
        $user = Filament::auth()->user();

        return match ($user?->kyc_status) {
            KycStatus::ResubmitRequired => 'KYC Resubmission Required',
            default                     => 'KYC Verification Required',
        };
    }

    public function getHeading(): string
    {
        $user = Filament::auth()->user();

        return match ($user?->kyc_status) {
            KycStatus::ResubmitRequired => 'Please update and resubmit your KYC documents',
            default                     => 'Your account requires KYC verification',
        };
    }

    public function getSubheading(): string
    {
        $user = Filament::auth()->user();

        if ($user?->kyc_status === KycStatus::ResubmitRequired && filled($user?->kyc_rejected_reason)) {
            return 'Admin Note: '.$user->kyc_rejected_reason;
        }

        return 'We require KYC verification to ensure the security of our platform. Please submit the necessary documents to complete your account verification.';
    }

    /**
     * Returns the list of fields the admin has requested for resubmission.
     * Returns null when it's an initial (non-resubmission) submission.
     */
    protected function getRequestedResubmitFields(): ?array
    {
        $user = Filament::auth()->user();

        if ($user?->kyc_status !== KycStatus::ResubmitRequired) {
            return null;
        }

        return $user->kyc_resubmit_fields ?? [];
    }

    /**
     * Whether a specific field should be shown to the customer.
     * On initial submit: all fields shown.
     * On resubmission: only the admin-requested fields shown.
     */
    protected function shouldShowField(string $fieldKey): bool
    {
        $requested = $this->getRequestedResubmitFields();

        // null = initial submission, show everything
        if ($requested === null) {
            return true;
        }

        return in_array($fieldKey, $requested, true);
    }

    public function content(Schema $schema): Schema
    {
        $user           = Filament::auth()->user();
        $isResubmission = $user?->kyc_status === KycStatus::ResubmitRequired;

        // Build the NID section components dynamically
        $nidComponents = [];

        if ($this->shouldShowField('nid_number')) {
            $nidComponents[] = TextInput::make('nid_number')
                ->label('NID / National ID Number')
                ->placeholder('e.g. 1234567890')
                ->required(!$isResubmission)
                ->maxLength(30);
        }

        if ($this->shouldShowField('nid_front')) {
            $nidComponents[] = FileUpload::make('front_nid')
                ->label('Front Side of NID')
                ->image()
                ->required(!$isResubmission)
                ->maxSize(4096)
                ->directory('ids')
                ->visibility('private');
        }

        if ($this->shouldShowField('nid_back')) {
            $nidComponents[] = FileUpload::make('back_nid')
                ->label('Back Side of NID')
                ->image()
                ->required(!$isResubmission)
                ->maxSize(4096)
                ->directory('ids')
                ->visibility('private');
        }

        // Build Live Photo section
        $livePhotoComponents = [];
        if ($this->shouldShowField('live_photo')) {
            $livePhotoComponents[] = LivePhotoCapture::make('live_photo')
                ->label('Live Photo (Camera Only)')
                ->validationAttribute('Live Photo')
                ->validationMessages([
                    'required' => 'Please capture a live photo using your webcam or phone camera before submitting.',
                ])
                ->required(!$isResubmission);
        }

        // Build Trade License section
        $tradeLicenseComponents = [];
        if ($this->shouldShowField('trade_license')) {
            $tradeLicenseComponents[] = TextInput::make('trade_license_number')
                ->label('Trade License Number (Optional)')
                ->placeholder('e.g. TL-2024-123456')
                ->maxLength(100);

            $tradeLicenseComponents[] = FileUpload::make('trade_license_file')
                ->label('Trade License Document (Optional)')
                ->image()
                ->maxSize(4096)
                ->directory('trade_licenses')
                ->visibility('private');
        }

        // Determine which sections to show
        $sections = [];

        $hasNidSection         = count($nidComponents) > 0;
        $hasLivePhotoSection   = count($livePhotoComponents) > 0;
        $hasTradeLicSection    = count($tradeLicenseComponents) > 0;

        if ($hasNidSection) {
            $sections[] = Section::make($isResubmission ? '⚠ Identity Documents — Correction Required' : 'ℹ Identity Documents')
                ->description($isResubmission
                    ? ($user?->kyc_rejected_reason ?? 'Please resubmit your documents per admin instructions.')
                    : 'Upload clear photographs of your National ID card (front and back). Also enter your NID number.')
                ->schema($nidComponents);
        }

        if ($hasLivePhotoSection) {
            $sections[] = Section::make($isResubmission ? '⚠ Live Selfie — Correction Required' : 'Live Camera Selfie')
                ->description('Take a live selfie using your device camera. Gallery uploads are not accepted.')
                ->schema($livePhotoComponents);
        }

        if ($hasTradeLicSection) {
            $sections[] = Section::make($isResubmission ? '⚠ Trade License — Correction Required' : 'Trade License (Optional)')
                ->description('Upload your trade license if you are a registered business.')
                ->schema($tradeLicenseComponents);
        }

        // If initial submission and no trade license section included yet, add it as optional
        if (! $isResubmission && ! $hasTradeLicSection) {
            $sections[] = Section::make('Trade License (Optional)')
                ->description('Upload your trade license if you are a registered business.')
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
                        ->visibility('private'),
                ]);
        }

        $sections[] = Actions::make([
            Action::make('submit')
                ->label($isResubmission ? 'Resubmit KYC Documents' : 'Submit KYC')
                ->action('submit')
                ->color($isResubmission ? 'warning' : 'success')
                ->extraAttributes(['style' => 'width: 100%']),
        ])->fullWidth();

        $sections[] = $this->getLogoutAction();

        return $schema
            ->statePath('data')
            ->components($sections);
    }

    public function submit(): void
    {
        $data           = $this->content->getState();
        $user           = Filament::auth()->user();
        $isResubmission = $user?->kyc_status === KycStatus::ResubmitRequired;
        $requestedFields = $user->kyc_resubmit_fields ?? null;

        // Sanitize FileUpload arrays — FileUpload returns an array of filenames
        if (is_array($data['front_nid'] ?? null)) {
            $data['front_nid'] = ! empty($data['front_nid']) ? (string) reset($data['front_nid']) : null;
        }
        if (is_array($data['back_nid'] ?? null)) {
            $data['back_nid'] = ! empty($data['back_nid']) ? (string) reset($data['back_nid']) : null;
        }
        if (is_array($data['trade_license_file'] ?? null)) {
            $data['trade_license_file'] = ! empty($data['trade_license_file']) ? (string) reset($data['trade_license_file']) : null;
        }

        // Process Live Photo base64 data URL
        if (filled($data['live_photo'] ?? null) && str_starts_with((string) $data['live_photo'], 'data:image')) {
            $base64Image = preg_replace('#^data:image/\w+;base64,#i', '', (string) $data['live_photo']);
            $decoded     = base64_decode($base64Image);
            $filename    = 'live_photos/live_'.Str::uuid().'.jpg';
            Storage::disk('private')->put($filename, $decoded);
            $data['live_photo'] = $filename;
        }

        /*
         * PARTIAL SAVE — only update the fields that were submitted.
         * Previously correct / approved fields remain UNCHANGED.
         * If resubmission and a field wasn't requested, we keep the existing value.
         */
        $updatePayload = [
            'kyc_status'          => KycStatus::UnderReview,
            'kyc_submitted_at'    => now(),
            'kyc_rejected_reason' => null,
            'kyc_resubmit_fields' => null,   // clear after resubmission
        ];

        // Only overwrite fields that were actually submitted/changed
        if (array_key_exists('nid_number', $data) && filled($data['nid_number'])) {
            $updatePayload['nid_number'] = $data['nid_number'];
        }
        if (array_key_exists('front_nid', $data) && filled($data['front_nid'])) {
            $updatePayload['front_nid'] = $data['front_nid'];
        }
        if (array_key_exists('back_nid', $data) && filled($data['back_nid'])) {
            $updatePayload['back_nid'] = $data['back_nid'];
        }
        if (array_key_exists('live_photo', $data) && filled($data['live_photo'])) {
            $updatePayload['live_photo'] = $data['live_photo'];
        }
        if (array_key_exists('trade_license_number', $data)) {
            $updatePayload['trade_license_number'] = $data['trade_license_number'];
        }
        if (array_key_exists('trade_license_file', $data) && filled($data['trade_license_file'])) {
            $updatePayload['trade_license_file']      = $data['trade_license_file'];
            $updatePayload['trade_license_submitted'] = true;
        }

        $user->update($updatePayload);

        Notification::make()
            ->title('KYC submitted successfully')
            ->body('Our team will review your documents and notify you shortly.')
            ->success()
            ->send();

        $this->redirect(Dashboard::getUrl(panel: 'customer'));
    }
}
