<?php

namespace App\Filament\Admin\Resources\Users\Customers\Tables;

use App\Enums\UserApproval;
use App\Filament\Admin\Resources\Users\Customers\Actions\ApprovalAction;
use App\Filament\Admin\Resources\Users\Customers\Actions\DepositAction;
use App\Filament\Admin\Resources\Users\Customers\Actions\LoginAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                $query->where('type', 'customer');
            })
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable(),
                TextColumn::make('balance')
                    ->label('Balance')
                    ->money('BDT'),
                TextColumn::make('approval')
                    ->label('Approval')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('approval')
                    ->options(UserApproval::class),
                TernaryFilter::make('email_verified_at')
                    ->label('Email Verified')
                    ->nullable()
                    ->placeholder('All')
                    ->trueLabel('Verified')
                    ->falseLabel('Not Verified'),
                TernaryFilter::make('phone_verified_at')
                    ->label('Phone Verified')
                    ->nullable()
                    ->placeholder('All')
                    ->trueLabel('Verified')
                    ->falseLabel('Not Verified'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('View Customer'),
                    DepositAction::make(),
                    ApprovalAction::make(),
                    LoginAction::make(),
                    EditAction::make()
                        ->label('Edit Customer'),
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
