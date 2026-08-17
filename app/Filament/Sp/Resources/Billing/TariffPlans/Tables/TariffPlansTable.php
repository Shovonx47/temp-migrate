<?php

namespace App\Filament\Sp\Resources\Billing\TariffPlans\Tables;

use App\Enums\TariffPlanStatus;
use App\Enums\TariffPlanType;
use App\Models\TariffPlan;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TariffPlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('currency')
                    ->sortable(),
                TextColumn::make('active_assignees')
                    ->label('Customers')
                    ->state(fn (TariffPlan $record): string => (string) $record->activeAssignments()->count())
                    ->alignCenter()
                    ->sortable(false),
                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(TariffPlanType::class),
                SelectFilter::make('status')
                    ->options(TariffPlanStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('archive')
                    ->label('Archive')
                    ->icon(Heroicon::OutlinedArchiveBox)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Archive this plan?')
                    ->modalDescription(fn (TariffPlan $record): string => $record->activeAssignments()->exists()
                        ? "This plan has {$record->activeAssignments()->count()} active customer(s) assigned. Archiving will not remove their assignments, but the plan can no longer be assigned to new customers."
                        : 'This plan has no active assignments. It will be archived and hidden from assignment lists.')
                    ->action(fn (TariffPlan $record) => $record->update(['status' => TariffPlanStatus::Archived]))
                    ->visible(fn (TariffPlan $record): bool => $record->status !== TariffPlanStatus::Archived),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
