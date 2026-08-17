<?php

namespace App\Filament\Admin\Resources\Settings\Senders\Schemas;

use App\Enums\SenderType;
use App\Filament\Admin\Resources\Users\Customers\Tables\CustomersTable;
use App\Models\Sender;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\ModalTableSelect;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class SenderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Sender Details')
                    ->schema([
                        Radio::make('creation_mode')
                            ->label('Creation Mode')
                            ->options([
                                'single' => 'Single Sender',
                                'bulk' => 'Bulk Import (CSV / Excel Sheet)',
                            ])
                            ->default('single')
                            ->inline()
                            ->live()
                            ->dehydrated(false)
                            ->columnSpanFull()
                            ->visible(fn (?Sender $record) => $record === null),

                        FileUpload::make('file')
                            ->label('CSV / Excel File')
                            ->disk('local')
                            ->directory('uploads/sender-imports')
                            ->acceptedFileTypes([
                                'text/csv',
                                'text/plain',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ])
                            ->helperText(fn (): HtmlString => new HtmlString(
                                'Upload a CSV or Excel sheet containing Sender IDs (CLIs) and optionally Bill MSISDN numbers, Gateways, or Types (masking / non-masking). '.
                                '<a href="'.asset('CliList.csv').'" download class="font-medium text-primary-600 underline hover:text-primary-500 dark:text-primary-400">Download sample CSV</a>.'
                            ))
                            ->required(fn (Get $get) => $get('creation_mode') === 'bulk')
                            ->visible(fn (Get $get, ?Sender $record) => $record === null && $get('creation_mode') === 'bulk')
                            ->columnSpanFull(),

                        Grid::make(2)->schema([
                            Select::make('gateway_id')
                                ->label(fn (Get $get) => $get('creation_mode') === 'bulk' ? 'Default Gateway' : 'Gateway')
                                ->relationship('gateway', 'name')
                                ->required(),
                            TextInput::make('sender_id')
                                ->label('Sender ID')
                                ->required(fn (Get $get) => $get('creation_mode') !== 'bulk')
                                ->visible(fn (Get $get) => $get('creation_mode') !== 'bulk')
                                ->maxLength(255),
                            Select::make('type')
                                ->label(fn (Get $get) => $get('creation_mode') === 'bulk' ? 'Default Type' : 'Type')
                                ->options(SenderType::maskingCategoryOptions())
                                ->default(SenderType::MaskingCategory)
                                ->required(),
                            Select::make('is_enabled')
                                ->label('Enabled')
                                ->boolean()
                                ->default(true),

                            Fieldset::make('Assigned Customers')
                                ->columnSpanFull()
                                ->schema([
                                    ModalTableSelect::make('users')
                                        ->hiddenLabel()
                                        ->multiple()
                                        ->relationship(
                                            'users',
                                            'name',
                                            modifyQueryUsing: function ($query) {
                                                $query->where('type', 'customer');
                                            }
                                        )
                                        ->tableConfiguration(CustomersTable::class),
                                ]),
                        ]),
                    ]),
            ]);
    }
}
