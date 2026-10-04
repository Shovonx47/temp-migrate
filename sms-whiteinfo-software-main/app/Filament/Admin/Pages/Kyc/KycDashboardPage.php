<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages\Kyc;

use App\Enums\KycPermission;
use App\Enums\KycStatus;
use App\Enums\SupermanNavigationGroup;
use App\Filament\Admin\Resources\Users\Customers\Actions\ApproveKycAction;
use App\Filament\Admin\Resources\Users\Customers\Actions\RejectKycAction;
use App\Filament\Admin\Resources\Users\Customers\Actions\RequestKycResubmissionAction;
use App\Filament\Admin\Resources\Users\Customers\CustomerResource;
use App\Models\User;
use App\Services\KycGate;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput as FormTextInput;

class KycDashboardPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Kyc;

    protected static ?string $navigationLabel = 'KYC Dashboard';

    protected static ?string $title = 'KYC Dashboard';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'kyc/dashboard';

    protected string $view = 'filament.admin.pages.kyc.kyc-dashboard';

    public static function canAccess(): bool
    {
        return KycGate::allows(KycPermission::View);
    }

    // ─── Stats (used in view) ───────────────────────────────────────────────────

    public function getStats(): array
    {
        $base = User::query()->where('type', 'customer');

        return [
            'total'            => (clone $base)->count(),
            'pending'          => (clone $base)->where('kyc_status', KycStatus::Pending->value)->count(),
            'under_review'     => (clone $base)->where('kyc_status', KycStatus::UnderReview->value)->count(),
            'verified'         => (clone $base)->where('kyc_status', KycStatus::Verified->value)->count(),
            'rejected'         => (clone $base)->where('kyc_status', KycStatus::Rejected->value)->count(),
            'resubmit'         => (clone $base)->where('kyc_status', KycStatus::ResubmitRequired->value)->count(),
            'today_submitted'  => (clone $base)->whereDate('kyc_submitted_at', today())->count(),
            'today_approved'   => (clone $base)->whereDate('kyc_verified_at', today())->count(),
        ];
    }

    // ─── Table ──────────────────────────────────────────────────────────────────

    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->where('type', 'customer')
                    ->orderBy('kyc_submitted_at', 'desc')
            )
            ->columns([
                TextColumn::make('id')
                    ->label('User ID')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('name')
                    ->label('Full Name')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('phone')
                    ->label('Mobile')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('nid_number')
                    ->label('NID Number')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('kyc_status')
                    ->label('KYC Status')
                    ->badge()
                    ->sortable(),

                TextColumn::make('kyc_submitted_at')
                    ->label('Submitted At')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('kyc_verified_at')
                    ->label('Approved At')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Registered')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('kyc_status')
                    ->label('KYC Status')
                    ->options(KycStatus::class),

                Filter::make('date_range')
                    ->label('Submission Date Range')
                    ->form([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('until')->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'],  fn ($q) => $q->whereDate('kyc_submitted_at', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('kyc_submitted_at', '<=', $data['until']));
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('view_kyc')
                        ->label('View Customer')
                        ->icon(Heroicon::OutlinedEye)
                        ->url(fn (User $record) => CustomerResource::getUrl('view', ['record' => $record->id])),

                    Action::make('manage_kyc')
                        ->label('Manage KYC')
                        ->icon(Heroicon::OutlinedIdentification)
                        ->color('info')
                        ->url(fn (User $record) => CustomerResource::getUrl('manage-kyc', ['record' => $record->id])),

                    ApproveKycAction::make('approve_kyc_dash'),
                    RequestKycResubmissionAction::make('resubmit_kyc_dash'),
                    RejectKycAction::make('reject_kyc_dash'),
                ]),
            ]);
    }
}
