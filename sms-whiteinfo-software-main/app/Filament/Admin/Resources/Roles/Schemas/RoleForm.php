<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Roles\Schemas;

use App\Enums\SupermanPermission;
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
        foreach (SupermanPermission::groups() as $groupName => $perms) {
            foreach ($perms as $key => $label) {
                $options[$key] = "{$label} — [{$groupName}]";
            }
        }

        return $schema
            ->columns(1)
            ->components([
                Section::make('Role Details')
                    ->description('Define an internal staff role for the main company.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Role Name')
                            ->placeholder('e.g. Admin, Manager, Sales Manager, Support, Accountant')
                            ->required()
                            ->maxLength(100),

                        Textarea::make('description')
                            ->label('Description')
                            ->placeholder('Scope of duties and access permissions')
                            ->rows(3)
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ])
                    ->columns(1),

                Section::make('Assigned Permissions')
                    ->description('Check the system modules and actions this internal role is allowed to access.')
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
