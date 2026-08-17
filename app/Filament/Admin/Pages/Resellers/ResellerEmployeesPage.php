<?php

namespace App\Filament\Admin\Pages\Resellers;

use App\Enums\SupermanNavigationGroup;
use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class ResellerEmployeesPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Resellers;

    protected static ?string $navigationLabel = 'S P Employees';

    protected static ?string $title = 'S P Employees';

    protected static ?int $navigationSort = 19;

    protected static ?string $slug = 'sps/employees';

    protected string $view = 'filament.admin.blank';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->where('type', UserType::Sp)
                    ->whereNotNull('sp_id')
                    ->with('sp')
                    ->latest()
            )
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sp.name')
                    ->label('Owning S P')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('sp_id')
                    ->label('Owning S P')
                    ->relationship('sp', 'name', fn ($query) => $query->where('type', UserType::Sp)->whereNull('sp_id')),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'banned' => 'Banned',
                    ]),
            ])
            ->recordActions([
                Action::make('suspend')
                    ->label('Suspend')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (User $record) => $record->status !== UserStatus::Banned)
                    ->action(function (User $record): void {
                        $record->update(['status' => UserStatus::Banned]);
                        Notification::make()
                            ->title('S P Employee suspended')
                            ->success()
                            ->send();
                    }),
                Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (User $record) => $record->status !== UserStatus::Approved)
                    ->action(function (User $record): void {
                        $record->update(['status' => UserStatus::Approved]);
                        Notification::make()
                            ->title('S P Employee approved')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }
}
