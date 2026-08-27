<?php

declare(strict_types=1);

namespace App\Filament\Customer\Pages\Account;

use App\Enums\KycStatus;
use App\Filament\Customer\Pages\Dashboard;
use App\Filament\Forms\Components\LivePhotoCapture;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
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

    public static function getSlug(?Panel $panel = null): string
    {
        return 'account/kyc';
    }

    public function getTitle(): string
    {
        $user = Filament::auth()->user();

        return match ($user?->kyc_status) {
            KycStatus::ResubmitRequired => 'KYC Resubmission Required',
            default => 'KYC Verification Required',
        };
    }

    public function getHeading(): string
    {
        $user = Filament::auth()->user();

        return match ($user?->kyc_status) {
            KycStatus::ResubmitRequired => 'Please update and resubmit your KYC documents',
            default => 'Your account requires KYC verification',
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

    public function content(Schema $schema): Schema
    {
        $user = Filament::auth()->user();
        $isResubmission = $user?->kyc_status === KycStatus::ResubmitRequired;

        return $schema
            ->statePath('data')
            ->components([
                // Show admin note if resubmission
                Section::make($isResubmission ? '⚠ Resubmission Required' : 'ℹ Identity Documents')
                    ->description($isResubmission
                        ? ($user?->kyc_rejected_reason ?? 'Please resubmit your documents per admin instructions.')
                        : 'Upload clear photographs of your National ID card (front and back).')
                    ->schema([
                        FileUpload::make('front_nid')
                            ->label('Front Side of NID')
                            ->image()
                            ->required()
                            ->maxSize(4096)
                            ->directory('ids')
                            ->visibility('private'),

                        FileUpload::make('back_nid')
                            ->label('Back Side of NID')
                            ->image()
                            ->required()
                            ->maxSize(4096)
                            ->directory('ids')
                            ->visibility('private'),
                    ]),

                Section::make('Live Camera Selfie')
                    ->description('Take a live selfie using your device camera. Gallery uploads are not accepted.')
                    ->schema([
                        LivePhotoCapture::make('live_photo')
                            ->label('Live Photo (Camera Only)')
                            ->required(),
                    ]),

                Actions::make([
                    Action::make('submit')
                        ->label($isResubmission ? 'Resubmit KYC Documents' : 'Submit KYC')
                        ->action('submit')
                        ->color($isResubmission ? 'warning' : 'success')
                        ->extraAttributes(['style' => 'width: 100%']),
                ])
                    ->fullWidth(),

                $this->getLogoutAction(),
            ]);
    }

    public function submit(): void
    {
        $data = $this->content->getState();
        $user = Filament::auth()->user();

        // Sanitize FileUpload arrays
        if (is_array($data['front_nid'] ?? null)) {
            $data['front_nid'] = ! empty($data['front_nid']) ? (string) reset($data['front_nid']) : null;
        }
        if (is_array($data['back_nid'] ?? null)) {
            $data['back_nid'] = ! empty($data['back_nid']) ? (string) reset($data['back_nid']) : null;
        }

        // Process Live Photo base64 data URL
        if (filled($data['live_photo'] ?? null) && str_starts_with((string) $data['live_photo'], 'data:image')) {
            $base64Image = preg_replace('#^data:image/\w+;base64,#i', '', (string) $data['live_photo']);
            $decoded = base64_decode($base64Image);
            $filename = 'live_photos/live_'.Str::uuid().'.jpg';
            Storage::disk('private')->put($filename, $decoded);
            $data['live_photo'] = $filename;
        }

        $user->update([
            'front_nid' => $data['front_nid'] ?? $user->front_nid,
            'back_nid' => $data['back_nid'] ?? $user->back_nid,
            'live_photo' => $data['live_photo'] ?? $user->live_photo,
            'kyc_status' => KycStatus::UnderReview,
            'kyc_submitted_at' => now(),
            'kyc_rejected_reason' => null,
        ]);

        Notification::make()
            ->title('KYC submitted successfully')
            ->body('Our team will review your documents and notify you shortly.')
            ->success()
            ->send();

        $this->redirect(Dashboard::getUrl(panel: 'customer'));
    }
}
