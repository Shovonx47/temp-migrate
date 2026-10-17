<?php

namespace App\Filament\Admin\Resources\Finance\Deposits\Tables;

use App\Actions\Billing\CompleteDeposit;
use App\Enums\DepositPaymentMethod;
use App\Enums\DepositStatus;
use App\Filament\Admin\Resources\Users\Customers\CustomerResource;
use App\Filament\Exports\DepositExporter;
use App\Services\Payment\Data\PaymentResponse;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ExportAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DepositsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->header(fn (Table $table) => view('filament.admin.resources.finance.deposits.summary', [
                'livewire' => $table->getLivewire(),
            ]))
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('Customer')
                    ->sortable()
                    ->searchable()
                    ->url(fn ($record) => $record->user
                        ? CustomerResource::getUrl('view', ['record' => $record->user_id])
                        : null
                    ),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->money(fn ($record) => $record->currency)
                    ->sortable()
                    ->alignRight(),

                TextColumn::make('payment_method')
                    ->label('Method')
                    ->badge()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('depositedBy.name')
                    ->label('Deposited By')
                    ->state(fn ($record) => $record->depositedBy?->name
                        ?? ($record->payment_method?->value === 'piprapay'
                            ? 'PipraPay'
                            : '—')
                    )
                    ->sortable()
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('user_id')
                    ->label('Customer ID')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('user.business_name')
                    ->label('Company')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('user.email')
                    ->label('Customer Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('user.phone')
                    ->label('Customer Phone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('depositedBy.id')
                    ->label('Depositor ID')
                    ->state(fn ($record) => $record->depositedBy?->id ?? '—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('depositedBy.email')
                    ->label('Depositor Email')
                    ->state(fn ($record) => $record->depositedBy?->email ?? '—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('transaction_id')
                    ->label('Transaction ID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('paid_at')
                    ->label('Paid At')
                    ->dateTime('d M Y, h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->filters([
                SelectFilter::make('date_preset')
                    ->label('Quick Date')
                    ->options([
                        'today' => 'Today',
                        'yesterday' => 'Yesterday',
                        'last_7_days' => 'Last 7 Days',
                        'this_week' => 'This Week',
                        'this_month' => 'This Month',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        if (! $value) {
                            return $query;
                        }

                        return match ($value) {
                            'today' => $query
                                ->whereDate('created_at', Carbon::today()),

                            'yesterday' => $query
                                ->whereDate('created_at', Carbon::yesterday()),

                            'last_7_days' => $query
                                ->whereBetween('created_at', [
                                    Carbon::now()->subDays(6)->startOfDay(),
                                    Carbon::now()->endOfDay(),
                                ]),

                            'this_week' => $query
                                ->whereBetween('created_at', [
                                    Carbon::now()->startOfWeek(),
                                    Carbon::now()->endOfWeek(),
                                ]),

                            'this_month' => $query
                                ->whereBetween('created_at', [
                                    Carbon::now()->startOfMonth(),
                                    Carbon::now()->endOfMonth(),
                                ]),

                            default => $query,
                        };
                    }),

                Filter::make('date')
                    ->label('Custom Date')
                    ->form([
                        DatePicker::make('from')
                            ->label('From Date'),

                        DatePicker::make('until')
                            ->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $query, $date) =>
                                    $query->whereDate('created_at', '>=', $date)
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $query, $date) =>
                                    $query->whereDate('created_at', '<=', $date)
                            );
                    }),

                SelectFilter::make('user_id')
                    ->label('Customer')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('customer_details')
                    ->label('Customer Details')
                    ->form([
                        TextInput::make('company')
                            ->label('Company'),

                        TextInput::make('email')
                            ->label('Email'),

                        TextInput::make('phone')
                            ->label('Phone'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['company'] ?? null,
                                fn (Builder $query, $value) =>
                                    $query->whereHas(
                                        'user',
                                        fn (Builder $q) => $q->where('business_name', 'like', "%{$value}%")
                                    )
                            )
                            ->when(
                                $data['email'] ?? null,
                                fn (Builder $query, $value) =>
                                    $query->whereHas(
                                        'user',
                                        fn (Builder $q) => $q->where('email', 'like', "%{$value}%")
                                    )
                            )
                            ->when(
                                $data['phone'] ?? null,
                                fn (Builder $query, $value) =>
                                    $query->whereHas(
                                        'user',
                                        fn (Builder $q) => $q->where('phone', 'like', "%{$value}%")
                                    )
                            );
                    }),

                SelectFilter::make('deposited_by_id')
                    ->label('Deposited By')
                    ->relationship('depositedBy', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('payment_method')
                    ->label('Payment Method')
                    ->options(DepositPaymentMethod::class)
                    ->searchable(),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(DepositStatus::class)
                    ->searchable(),

                Filter::make('amount')
                    ->label('Amount')
                    ->form([
                        TextInput::make('min')
                            ->label('Minimum')
                            ->numeric(),

                        TextInput::make('max')
                            ->label('Maximum')
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['min'] ?? null),
                                fn (Builder $query) =>
                                    $query->where('amount', '>=', $data['min'])
                            )
                            ->when(
                                filled($data['max'] ?? null),
                                fn (Builder $query) =>
                                    $query->where('amount', '<=', $data['max'])
                            );
                    }),

                Filter::make('transaction_id')
                    ->label('Transaction ID')
                    ->form([
                        TextInput::make('value')
                            ->label('Transaction ID')
                            ->placeholder('Search transaction ID...'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            filled($data['value'] ?? null),
                            fn (Builder $query) =>
                                $query->where('transaction_id', 'like', '%' . $data['value'] . '%')
                        );
                    }),
            ])

            ->headerActions([
                ExportAction::make('export')
                    ->label('Export')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->exporter(DepositExporter::class),
            ])

            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),

                    Action::make('approve')
                        ->label('Approve')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('warning')
                        ->requiresConfirmation()
                        ->visible(fn ($record) => $record->status === DepositStatus::Pending)
                        ->action(function ($record) {
                            $completeDeposit = app(CompleteDeposit::class);

                            $completeDeposit->handle(
                                $record->payment_method->value,
                                new PaymentResponse(
                                    successful: true,
                                    gatewayDriver: $record->payment_method->value,
                                    gatewayStatus: 'completed',
                                    gatewayPaymentId: $record->transaction_id,
                                )
                            );
                        }),

                    Action::make('reject')
                        ->label('Reject')
                        ->icon(Heroicon::OutlinedXCircle)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn ($record) => $record->status === DepositStatus::Pending)
                        ->action(function ($record) {
                            $record->update([
                                'status' => DepositStatus::Failed,
                            ]);

                            Notification::make()
                                ->title('Deposit Rejected')
                                ->body('Your deposit was rejected.')
                                ->danger()
                                ->sendToDatabase($record->user);
                        }),
                ]),
            ])

            ->defaultSort('created_at', 'desc');
    }
}
