<?php

namespace App\Filament\Admin\Resources\Users\Customers\Pages;

use App\Filament\Admin\Resources\Users\Customers\Actions\ApprovalAction;
use App\Filament\Admin\Resources\Users\Customers\Actions\ApproveKycAction;
use App\Filament\Admin\Resources\Users\Customers\Actions\DepositAction;
use App\Filament\Admin\Resources\Users\Customers\Actions\LoginAction;
use App\Filament\Admin\Resources\Users\Customers\Actions\RejectKycAction;
use App\Filament\Admin\Resources\Users\Customers\Actions\RequestKycResubmissionAction;
use App\Filament\Admin\Resources\Users\Customers\CustomerResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LoginAction::make(),
            DepositAction::make(),
            ApprovalAction::make(),

            // KYC quick-actions from the view page
            ApproveKycAction::make(),
            RequestKycResubmissionAction::make(),
            RejectKycAction::make(),

            // Navigate to the full KYC edit page
            Action::make('manage_kyc')
                ->label('Manage KYC')
                ->icon('heroicon-o-identification')
                ->color('info')
                ->url(fn () => CustomerResource::getUrl('manage-kyc', ['record' => $this->record->id])),

            EditAction::make()
                ->label('Edit Customer'),
        ];
    }
}
