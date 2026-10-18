<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\Customers\Tables;

use App\Enums\KycStatus;
use App\Enums\UserStatus;
use App\Filament\Admin\Resources\Users\Customers\Actions\ApprovalAction;
use App\Filament\Admin\Resources\Users\Customers\Actions\ApproveKycAction;
use App\Filament\Admin\Resources\Users\Customers\Actions\DepositAction;
use App\Filament\Admin\Resources\Users\Customers\Actions\LoginAction;
use App\Filament\Admin\Resources\Users\Customers\Actions\RejectKycAction;
use App\Filament\Admin\Resources\Users\Customers\Actions\RequestKycResubmissionAction;
use App\Models\TariffPlan;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->where('type', 'customer')
                    ->with(['activeTariffAssignment.tariffPlan'])
                    ->select('users.*')
                    ->selectSub(
                        DB::table('sessions')
                            ->selectRaw('FROM_UNIXTIME(MAX(last_activity))')
                            ->whereColumn('sessions.user_id', 'users.id'),
                        'latest_session_activity'
                    );
            })
            ->columns([
                ViewColumn::make('login_as')
                    ->label('Login As')
                    ->view('filament.admin.resources.customers.columns.login-as'),

                TextColumn::make('business_name')
                    ->label('Company Name')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—')
                    ->wrap(false),

                TextColumn::make('balance')
                    ->label('Balance')
                    ->money('BDT')
                    ->alignEnd()
                    ->sortable()
                    ->summarize(
                        Sum::make()
                            ->label('Total Balance')
                            ->money('BDT')
                    )
                    ->wrap(false),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap(false),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->wrap(false),

                TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->wrap(false),

                TextColumn::make('activeTariffAssignment.tariffPlan.name')
                    ->label('Tariff')
                    ->placeholder('—')
                    ->wrap(false),

                TextColumn::make('nid_number')
                    ->label('NID Number')
                    ->searchable()
                    ->placeholder('—')
                    ->wrap(false),

                TextColumn::make('kyc_status')
                    ->label('KYC Status')
                    ->badge()
                    ->sortable()
                    ->wrap(false),

                TextColumn::make('status')
                    ->label('Account Status')
                    ->badge()
                    ->sortable()
                    ->wrap(false),

                TextColumn::make('last_login_at')
                    ->label('Last Login')
                    ->state(function (User $record): ?Carbon {
                        if ($record->last_login_at) {
                            return $record->last_login_at;
                        }
                        if (! empty($record->latest_session_activity)) {
                            return Carbon::parse($record->latest_session_activity);
                        }

                        return null;
                    })
                    ->dateTime('M d, Y h:i A')
                    ->placeholder('Never')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderByRaw('COALESCE(users.last_login_at, (SELECT FROM_UNIXTIME(MAX(last_activity)) FROM sessions WHERE sessions.user_id = users.id)) '.$direction);
                    })
                    ->wrap(false),

                TextColumn::make('created_by_admin')
                    ->label('Source')
                    ->state(fn (User $record): string => $record->created_by_admin ? 'Admin' : 'Self')
                    ->badge()
                    ->color(fn (User $record): string => $record->created_by_admin ? 'info' : 'gray')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('M d, Y h:i A')
                    ->sortable()
                    ->wrap(false),
            ])
            ->filters([
                SelectFilter::make('created_by_admin')
                    ->label('Creation Source')
                    ->options([
                        '1' => 'Super Admin Created',
                        '0' => 'Self-Registered',
                    ]),

                SelectFilter::make('kyc_status')
                    ->label('KYC Status')
                    ->options(KycStatus::class),

                SelectFilter::make('status')
                    ->label('Account Status')
                    ->options(UserStatus::class),

                SelectFilter::make('tariff_plan')
                    ->label('Tariff Plan')
                    ->options(fn () => TariffPlan::query()->orderBy('name')->pluck('name', 'id')->toArray())
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'] ?? null,
                            fn (Builder $q, $tariffPlanId) => $q->whereHas(
                                'tariffPlanAssignments',
                                fn (Builder $sub) => $sub->where('tariff_plan_id', $tariffPlanId)
                                    ->where('status', 'active')
                            )
                        );
                    }),

                SelectFilter::make('balance_tier')
                    ->label('Balance Range')
                    ->options([
                        'positive' => 'Funded (> 0 BDT)',
                        'zero' => 'Zero Balance (0 BDT)',
                        'low' => 'Low (< 100 BDT)',
                        'high' => 'High (≥ 1,000 BDT)',
                        'vip' => 'VIP (≥ 10,000 BDT)',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'positive' => $query->where('balance', '>', 0),
                            'zero' => $query->where('balance', '<=', 0),
                            'low' => $query->where('balance', '>=', 0)->where('balance', '<', 100),
                            'high' => $query->where('balance', '>=', 1000),
                            'vip' => $query->where('balance', '>=', 10000),
                            default => $query,
                        };
                    }),

                Filter::make('registered_from')
                    ->label('Registered From')
                    ->schema([
                        DatePicker::make('from')
                            ->label('Registered From'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['from'] ?? null,
                            fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date)
                        );
                    }),

                Filter::make('registered_until')
                    ->label('Registered Until')
                    ->schema([
                        DatePicker::make('until')
                            ->label('Registered Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['until'] ?? null,
                            fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date)
                        );
                    }),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns([
                'default' => 1,
                'sm' => 2,
                'md' => 3,
                'lg' => 6,
                'xl' => 6,
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('View Customer & KYC'),
                    LoginAction::make(),
                    DepositAction::make(),
                    EditAction::make()
                        ->label('Edit Customer'),
                    ApproveKycAction::make(),
                    RequestKycResubmissionAction::make(),
                    RejectKycAction::make(),
                    ApprovalAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
