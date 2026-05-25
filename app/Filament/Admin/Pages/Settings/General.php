<?php

namespace App\Filament\Admin\Pages\Settings;

use App\Enums\SupermanNavigationGroup;
use App\Settings\GeneralSettings;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class General extends SettingsPage
{
    protected static string $settings = GeneralSettings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Settings;

    protected static ?string $navigationLabel = 'General';

    protected static ?string $slug = 'settings/general';

    public static function canAccess(): bool
    {
        return Filament::auth()->user()->type->isSuperman();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('appName')
                            ->label('App Name')
                            ->required(),
                        FileUpload::make('logoUrl')
                            ->disk('public')
                            ->label('Logo')
                            ->required(),
                    ]),
            ]);
    }
}
