<?php

namespace App\Filament\Customer\Resources\Messaging\MessageBatches\Schemas;

use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class MessageBatchInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Message')
                    ->icon(Heroicon::ChatBubbleLeftRight)
                    ->schema([
                        TextEntry::make('body')
                            ->label('Template')
                            ->columnSpanFull(),
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('status')
                                    ->badge(),
                                TextEntry::make('source_type')
                                    ->label('Source')
                                    ->badge(),
                                TextEntry::make('sender.sender_id')
                                    ->label('Sender ID')
                                    ->placeholder('—'),
                            ]),
                    ]),

                Section::make('Recipients')
                    ->icon(Heroicon::Users)
                    ->afterHeader([
                        Action::make('downloadInvalidReport')
                            ->label('Download invalid list')
                            ->icon(Heroicon::ArrowDownTray)
                            ->color('warning')
                            ->url(fn ($record): ?string => $record->failed_report_path
                                ? route('customer.messages.failed-report', [
                                    'batch' => $record->id,
                                    'filename' => basename($record->failed_report_path),
                                ])
                                : null)
                            ->openUrlInNewTab()
                            ->visible(fn ($record): bool => (bool) $record->failed_report_path
                                && (int) $record->invalid_count > 0),
                    ])
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('total_recipients')
                                    ->label('Total')
                                    ->numeric()
                                    ->icon(Heroicon::UserGroup),
                                TextEntry::make('valid_count')
                                    ->label('Valid')
                                    ->numeric()
                                    ->color('success')
                                    ->icon(Heroicon::CheckCircle),
                                TextEntry::make('invalid_count')
                                    ->label('Invalid')
                                    ->numeric()
                                    ->color('danger')
                                    ->icon(Heroicon::XCircle),
                                TextEntry::make('progress_label')
                                    ->label('Progress')
                                    ->icon(Heroicon::ChartBar)
                                    ->color(fn ($record): string => match (true) {
                                        (int) $record->total_recipients === 0 => 'gray',
                                        $record->processed_count >= (int) $record->total_recipients => 'success',
                                        default => 'primary',
                                    }),
                            ]),
                    ]),

                Section::make('Timeline')
                    ->icon(Heroicon::Clock)
                    ->collapsible()
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Created')
                                    ->dateTime(),
                                TextEntry::make('started_at')
                                    ->label('Started')
                                    ->dateTime()
                                    ->placeholder('—'),
                                TextEntry::make('completed_at')
                                    ->label('Completed')
                                    ->dateTime()
                                    ->placeholder('—'),
                            ]),
                    ]),
            ]);
    }
}
