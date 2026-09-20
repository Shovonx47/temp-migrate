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
                                    ->state(fn ($record) => filled($record->front_nid) ? route('kyc-file', [$record->getKey(), 'front_nid']) : null)
                                    ->url(fn ($record) => filled($record->front_nid) ? route('kyc-file', [$record->getKey(), 'front_nid']) : null, shouldOpenInNewTab: true)
                                    ->placeholder('No document uploaded'),

                                ImageEntry::make('back_nid')
                                    ->label('Back Side of NID')
                                    ->state(fn ($record) => filled($record->back_nid) ? route('kyc-file', [$record->getKey(), 'back_nid']) : null)
                                    ->url(fn ($record) => filled($record->back_nid) ? route('kyc-file', [$record->getKey(), 'back_nid']) : null, shouldOpenInNewTab: true)
                                    ->placeholder('No document uploaded'),

                                ImageEntry::make('live_photo')
                                    ->label('Live Camera Selfie')
                                    ->state(fn ($record) => filled($record->live_photo) ? route('kyc-file', [$record->getKey(), 'live_photo']) : null)
                                    ->url(fn ($record) => filled($record->live_photo) ? route('kyc-file', [$record->getKey(), 'live_photo']) : null, shouldOpenInNewTab: true)
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
                            ->state(fn ($record) => filled($record->trade_license_file) ? 'View Document' : 'Not Submitted')
                            ->url(fn ($record) => filled($record->trade_license_file) ? route('kyc-file', [$record->getKey(), 'trade_license_file']) : null, shouldOpenInNewTab: true)
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
                                        if (! empty($approved)) {
                                            return implode(', ', $approved);
                                        }

                                        $settings = app(\App\Settings\SecuritySettings::class);
                                        return $settings->allow_all_when_whitelist_empty
                                            ? 'Permissive: All IPs permitted'
                                            : 'Strict: No active IPs (Direct API blocked)';
                                    })
                                    ->badge()
                                    ->color(fn ($state) => match (true) {
                                        str_contains((string) $state, 'Strict') => 'danger',
                                        str_contains((string) $state, 'Permissive') => 'warning',
                                        default => 'success',
                                    }),
                            ]),
                    ]),
            ]);
    }
}
