<?php

namespace App\Filament\Admin\Pages\ApiDocumentation;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ApiDocumentationPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::ApiDocumentation;

    protected static ?string $navigationLabel = 'API Documentation';

    protected static ?string $title = 'API Documentation';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'api-docs';

    protected string $view = 'filament.admin.blank';
}
