<?php

namespace App\Filament\Customer\Resources\Audience\Contacts\Schemas;

use App\Models\Contact;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContactForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Contact Details')->schema([
                    TextInput::make('name')
                        ->label('Name')
                        ->placeholder('Enter contact name')
                        ->maxLength(255),
                    TextInput::make('phone')
                        ->label('Phone')
                        ->placeholder('01712345678 or +8801712345678')
                        ->tel()
                        ->required()
                        ->maxLength(255)
                        ->rules(['regex:/^(?:\+?88)?0?1[3-9]\d{8}$/'])
                        ->validationMessages([
                            'regex' => 'Please enter a valid 11-digit Bangladeshi mobile number (e.g. 017XXXXXXXX).',
                        ])
                        ->unique(
                            table: Contact::class,
                            column: 'phone',
                            ignoreRecord: true,
                            modifyRuleUsing: function ($rule) {
                                return $rule->where('user_id', auth()->id())->whereNull('deleted_at');
                            },
                        ),
                ])->columns(2),
            ]);
    }
}
