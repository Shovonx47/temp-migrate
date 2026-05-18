<?php

namespace App\Filament\Imports;

use App\Models\Contact;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class ContactImporter extends Importer
{
    protected static ?string $model = Contact::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('phone')
                ->label('Phone')
                ->examples(['+8801322635808', '+8801608460717'])
                ->requiredMapping()
                ->castStateUsing(function ($state): ?string {
                    if (blank($state)) {
                        return null;
                    }

                    if (preg_match('/^(?:\+?88)?0?(1[3-9]\d{8})$/', $state, $m)) {
                        return '+880'.$m[1];
                    }

                    return null;
                })
                ->rules(['required', 'max:255', 'regex:/^(?:\+?88)?0?(1[3-9]\d{8})$/']),
            ImportColumn::make('name')
                ->label('Name')
                ->examples(['Bishwajit Adhikary', 'John Doe'])
                ->rules(['max:255']),
        ];
    }

    public function resolveRecord(): Contact
    {
        return new Contact([
            'user_id' => $this->getImport()->user_id,
        ]);
    }

    protected function afterSave(): void
    {
        $groupId = $this->getOptions()['group_id'] ?? null;

        if ($groupId) {
            $this->record->groups()->syncWithoutDetaching([$groupId]);
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = sprintf('Your contact import has completed and %s %s imported.',
            Number::format($import->successful_rows),
            str('row')->plural($import->successful_rows)
        );

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= sprintf(' %s %s failed to import.',
                Number::format($failedRowsCount),
                str('row')->plural($failedRowsCount)
            );
        }

        return $body;
    }
}
