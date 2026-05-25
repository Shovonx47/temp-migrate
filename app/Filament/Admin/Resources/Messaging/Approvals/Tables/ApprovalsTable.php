<?php

namespace App\Filament\Admin\Resources\Messaging\Approvals\Tables;

use App\Enums\SmsApproval;
use App\Filament\Admin\Resources\Messaging\Approvals\Actions\ApproveAction;
use App\Filament\Admin\Resources\Messaging\Approvals\Actions\RejectAction;
use Filament\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ApprovalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->whereNotNull('approval'))
            ->columns([
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sender.sender_id')
                    ->label('Sender ID')
                    ->searchable(),
                TextColumn::make('body')
                    ->label('Message')
                    ->limit(60)
                    ->tooltip(fn (string $state): string => $state),
                TextColumn::make('total_recipients')
                    ->label('Recipients')
                    ->abbreviate()
                    ->alignRight(),
                TextColumn::make('source_type')
                    ->label('Source')
                    ->badge(),
                TextColumn::make('approval')
                    ->label('Approval')
                    ->badge(),
                TextColumn::make('approvedBy.name')
                    ->label('Reviewed By')
                    ->placeholder('—'),
                TextColumn::make('approval_note')
                    ->label('Note')
                    ->limit(40)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('approval')
                    ->label('Approval Status')
                    ->options(SmsApproval::class)
                    ->default(SmsApproval::Pending->value),
            ])
            ->recordActions([
                ActionGroup::make([
                    ApproveAction::make(),
                    RejectAction::make(),
                ]),
            ]);
    }
}
