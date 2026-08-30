<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages\Kyc;

use App\Enums\KycPermission;
use App\Enums\KycStatus;
use App\Enums\SupermanNavigationGroup;
use App\Enums\UserStatus;
use App\Filament\Admin\Resources\Users\Customers\Actions\ApproveKycAction;
use App\Filament\Admin\Resources\Users\Customers\Actions\RejectKycAction;
use App\Filament\Admin\Resources\Users\Customers\Actions\RequestKycResubmissionAction;
use App\Models\User;
use App\Services\KycGate;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class VerificationRequestsPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Kyc;

    protected static ?string $navigationLabel = 'Verification Requests';

    protected static ?string $title = 'KYC Verification Queue';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'kyc/requests';

    protected string $view = 'filament.admin.pages.kyc.verification-requests';

    public static function canAccess(): bool
    {
        return KycGate::allows(KycPermission::Review);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->where('type', 'customer')
                    ->whereIn('kyc_status', [
                        KycStatus::Pending->value,
                        KycStatus::UnderReview->value,
                        KycStatus::ResubmitRequired->value,
                    ])
                    ->orderBy('kyc_submitted_at', 'asc')
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Customer Name')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('nid_number')
                    ->label('NID Number')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('phone')
                    ->label('Mobile')
                    ->copyable(),

                TextColumn::make('kyc_status')
                    ->label('KYC Status')
                    ->badge()
                    ->sortable(),

                TextColumn::make('trade_license_submitted')
                    ->label('Trade License')
                    ->state(fn ($record) => $record->trade_license_submitted ? 'Submitted' : 'Not Submitted')
                    ->badge()
                    ->color(fn ($record) => $record->trade_license_submitted ? 'info' : 'gray'),

                TextColumn::make('kyc_submitted_at')
                    ->label('Submitted At')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('kyc_rejected_reason')
                    ->label('Last Admin Note')
                    ->words(8)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('kyc_status')
                    ->label('KYC Status')
                    ->options([
                        KycStatus::Pending->value => KycStatus::Pending->getLabel(),
                        KycStatus::UnderReview->value => KycStatus::UnderReview->getLabel(),
                        KycStatus::ResubmitRequired->value => KycStatus::ResubmitRequired->getLabel(),
                    ]),
            ])
            ->recordActions([
                ActionGroup::make([
                    ApproveKycAction::make(),
                    RequestKycResubmissionAction::make(),
                    RejectKycAction::make(),
                ]),
            ]);
    }
}
