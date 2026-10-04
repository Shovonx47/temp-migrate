<?php

declare(strict_types=1);

namespace App\Filament\Sp\Resources\Roles\Schemas;

use App\Enums\SpPermission;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        $options = [];
        foreach (SpPermission::groups() as $groupName => $perms) {
            foreach ($perms as $key => $label) {
                $options[$key] = "{$label} — [{$groupName}]";
            }
        }

        return $schema
            ->columns(1)
            ->components([
                Section::make('Role Information')
                    ->description('Define the role name and a brief description.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Role Name')
                            ->placeholder('e.g. Sales Executive, Support Manager')
                            ->required()
                            ->maxLength(100),

                        Textarea::make('description')
                            ->label('Description')
                            ->placeholder('Brief description of responsibilities and access level')
                            ->rows(3)
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ])
                    ->columns(1),

                Section::make('Permissions')
                    ->description('Select the permissions for employees assigned this role. Unchecked items will be blocked both in the UI and backend API.')
                    ->schema([
                        CheckboxList::make('permissions')
                            ->hiddenLabel()
                            ->options($options)
                            ->columns(2)
                            ->bulkToggleable()
                            ->searchable()
                            ->required(),
                    ]),
            ]);
    }
}
