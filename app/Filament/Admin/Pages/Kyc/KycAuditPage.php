<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages\Kyc;

use App\Enums\KycPermission;
use App\Enums\SupermanNavigationGroup;
use App\Models\KycAuditLog;
use App\Services\KycGate;
use BackedEnum;
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

class KycAuditPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocument;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Kyc;

    protected static ?string $navigationLabel = 'KYC Audit';

    protected static ?string $title = 'KYC Audit Log';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'kyc/audit';

    protected string $view = 'filament.admin.pages.kyc.kyc-audit';

    public static function canAccess(): bool
    {
        return KycGate::allows(KycPermission::AuditView);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(KycAuditLog::query()->with(['user', 'admin'])->latest())
            ->columns([
                TextColumn::make('admin_name')
                    ->label('Admin')
                    ->searchable()
                    ->weight('bold')
                    ->placeholder('System'),

                TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->description(fn (KycAuditLog $record) => $record->user?->email),

                TextColumn::make('action')
                    ->label('Action')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'approved'                => 'success',
                        'rejected'                => 'danger',
                        'resubmission_requested'  => 'warning',
                        'document_replaced'       => 'info',
                        'updated'                 => 'primary',
                        default                   => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => ucwords(str_replace('_', ' ', $state))),

                TextColumn::make('field_name')
                    ->label('Field')
                    ->placeholder('—')
                    ->formatStateUsing(fn (?string $state) => $state
                        ? ucwords(str_replace('_', ' ', $state))
                        : '—'),

                TextColumn::make('old_value')
                    ->label('Old Value')
                    ->placeholder('—')
                    ->limit(40)
                    ->tooltip(fn (KycAuditLog $record) => $record->old_value),

                TextColumn::make('new_value')
                    ->label('New Value')
                    ->placeholder('—')
                    ->limit(40)
                    ->tooltip(fn (KycAuditLog $record) => $record->new_value),

                TextColumn::make('note')
                    ->label('Note / Reason')
                    ->placeholder('—')
                    ->words(10)
                    ->tooltip(fn (KycAuditLog $record) => $record->note)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Date / Time')
                    ->dateTime('d-m-Y h:i A')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label('Action')
                    ->options([
                        'approved'               => 'Approved',
                        'rejected'               => 'Rejected',
                        'resubmission_requested' => 'Resubmission Requested',
                        'document_replaced'      => 'Document Replaced',
                        'updated'                => 'Updated',
                    ]),

                Filter::make('date_range')
                    ->label('Date Range')
                    ->form([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('until')->label('Until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'],  fn ($q) => $q->whereDate('created_at', '>=', $data['from']))
                        ->when($data['until'], fn ($q) => $q->whereDate('created_at', '<=', $data['until']))),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
