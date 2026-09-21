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
                $query->where('type', 'customer')
                    ->with(['activeTariffAssignment.tariffPlan']);
            })
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('nid_number')
                    ->label('NID Number')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),

                TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable(),

                TextColumn::make('kyc_status')
                    ->label('KYC Status')
                    ->badge(),

                TextColumn::make('status')
                    ->label('Account Status')
                    ->badge(),

                TextColumn::make('balance')
                    ->label('Balance')
                    ->money('BDT'),

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
                SelectFilter::make('status')
                    ->label('Account Status')
                    ->options(UserStatus::class),
                TernaryFilter::make('trade_license_submitted')
                    ->label('Trade License')
                    ->placeholder('All')
                    ->trueLabel('Submitted')
                    ->falseLabel('Not Submitted'),
                TernaryFilter::make('api_access')
                    ->label('API Access')
                    ->placeholder('All')
                    ->trueLabel('Enabled')
                    ->falseLabel('Disabled'),
                TernaryFilter::make('sms_auto_approved')
                    ->label('SMS Auto-approved')
                    ->placeholder('All')
                    ->trueLabel('Auto-approved')
                    ->falseLabel('Requires approval'),
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
                        ->label('View Customer & KYC'),
                    ApproveKycAction::make(),
                    RequestKycResubmissionAction::make(),
                    RejectKycAction::make(),
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
