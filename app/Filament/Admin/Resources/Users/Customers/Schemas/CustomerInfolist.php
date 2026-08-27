<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\Customers\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class CustomerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Customer Information')
                    ->icon(Heroicon::OutlinedUser)
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Full Name')
                                    ->weight('bold'),

                                TextEntry::make('email')
                                    ->label('Email Address')
                                    ->copyable()
                                    ->url(fn ($state) => "mailto:{$state}"),

                                TextEntry::make('phone')
                                    ->label('Mobile Number')
                                    ->copyable()
                                    ->url(fn ($state) => "tel:{$state}"),

                                TextEntry::make('status')
                                    ->label('Account Status')
                                    ->badge(),

                                TextEntry::make('kyc_status')
                                    ->label('KYC Status')
                                    ->badge(),

                                TextEntry::make('balance')
                                    ->label('Account Balance')
                                    ->prefix('৳')
                                    ->numeric(decimalPlaces: 2),

                                TextEntry::make('email_verified_at')
                                    ->label('Email Verification')
                                    ->state(fn ($record) => $record->email_verified_at ? 'Verified ('.$record->email_verified_at->format('M d, Y H:i').')' : 'Unverified')
                                    ->badge()
                                    ->color(fn ($record) => $record->email_verified_at ? 'success' : 'danger'),

                                TextEntry::make('phone_verified_at')
                                    ->label('Mobile Verification')
                                    ->state(fn ($record) => $record->phone_verified_at ? 'Verified ('.$record->phone_verified_at->format('M d, Y H:i').')' : 'Unverified')
                                    ->badge()
                                    ->color(fn ($record) => $record->phone_verified_at ? 'success' : 'danger'),

                                TextEntry::make('created_at')
                                    ->label('Registered At')
                                    ->dateTime(),
                            ]),
                    ]),

                Section::make('KYC & Identity Verification')
                    ->icon(Heroicon::OutlinedIdentification)
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('nid_number')
                                    ->label('NID Number')
                                    ->weight('bold')
                                    ->placeholder('Not Provided')
                                    ->copyable(),

                                TextEntry::make('kyc_submitted_at')
                                    ->label('KYC Submitted At')
                                    ->dateTime()
                                    ->placeholder('—'),

                                TextEntry::make('kyc_verified_at')
                                    ->label('KYC Verified At')
                                    ->dateTime()
                                    ->placeholder('—'),
                            ]),

                        TextEntry::make('kyc_rejected_reason')
                            ->label('Admin Rejection / Resubmission Reason')
                            ->color('danger')
                            ->visible(fn ($record) => filled($record->kyc_rejected_reason)),

                        Grid::make(3)
                            ->schema([
                                ImageEntry::make('front_nid')
                                    ->label('Front Side of NID')
                                    ->disk('private')
                                    ->visibility('private')
                                    ->placeholder('No document uploaded'),

                                ImageEntry::make('back_nid')
                                    ->label('Back Side of NID')
                                    ->disk('private')
                                    ->visibility('private')
                                    ->placeholder('No document uploaded'),

                                ImageEntry::make('live_photo')
                                    ->label('Live Camera Selfie')
                                    ->disk('private')
                                    ->visibility('private')
                                    ->placeholder('No live photo captured'),
                            ]),
                    ]),

                Section::make('Business Information & Trade License')
                    ->icon(Heroicon::OutlinedBuildingOffice2)
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('business_name')
                                    ->label('Business / Company Name')
                                    ->placeholder('—'),

                                TextEntry::make('trade_license_number')
                                    ->label('Trade License Number')
                                    ->placeholder('Not Submitted')
                                    ->badge(fn ($state) => $state === null ? 'Not Submitted' : null)
                                    ->color(fn ($state) => $state === null ? 'gray' : 'info'),

                                TextEntry::make('trade_license_submitted')
                                    ->label('Trade License Status')
                                    ->state(fn ($record) => $record->trade_license_submitted ? 'Submitted' : 'Not Submitted')
                                    ->badge()
                                    ->color(fn ($record) => $record->trade_license_submitted ? 'info' : 'gray'),
                            ]),

                        TextEntry::make('business_address')
                            ->label('Business Address')
                            ->placeholder('—'),

                        TextEntry::make('trade_license_file')
                            ->label('Trade License Document')
                            ->state(fn ($record) => filled($record->trade_license_file) ? 'Document Uploaded' : 'Not Submitted')
                            ->badge()
                            ->color(fn ($record) => filled($record->trade_license_file) ? 'success' : 'gray'),
                    ]),

                Section::make('SMS Permissions & Tariff Plan')
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                IconEntry::make('api_access')
                                    ->label('API Access')
                                    ->boolean(),

                                IconEntry::make('sms_auto_approved')
                                    ->label('SMS Auto-approved')
                                    ->boolean(),

                                TextEntry::make('activeTariffAssignment.tariffPlan.name')
                                    ->label('Active Tariff Plan')
                                    ->placeholder('No plan assigned — using operator defaults.')
                                    ->badge()
                                    ->color('info'),

                                TextEntry::make('ip_whitelist')
                                    ->label('API IP Whitelist')
                                    ->state(function ($record) {
                                        $approved = $record->ipWhitelistRequests()->approved()->pluck('ip_address')->toArray();
                                        return empty($approved) ? 'No restriction (All IPs allowed)' : implode(', ', $approved);
                                    })
                                    ->badge(fn ($state) => $state !== 'No restriction (All IPs allowed)')
                                    ->color(fn ($state) => $state !== 'No restriction (All IPs allowed)' ? 'success' : 'gray'),
                            ]),
                    ]),
            ]);
    }
}
