<?php

namespace App\Providers;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\Exports\ExportColumn;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\Size;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Number;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

class FilamentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureMacros();
        $this->configureTable();
        $this->configureSections();
        $this->configureActions();
        $this->configureField();
        $this->configureColumns();
        $this->configureFilters();
        $this->configureEntries();
    }

    protected function configureMacros(): void
    {

        Stringable::macro('acronym', function (): Stringable {
            $acronyms = config('data.acronyms');

            /** @var Stringable $this */
            return $this->replaceMatches(
                pattern: '/\b('.implode('|', array_keys($acronyms)).')\b/i',
                replace: function (array $matches) use ($acronyms) {
                    return $acronyms[strtolower($matches[1])] ?? $matches[0];
                }
            );
        });
    }

    protected function configureTable(): void
    {
        Table::configureUsing(static function (Table $table) {
            $table
                ->defaultSort('created_at', 'desc')
                ->deferFilters(false)
                ->paginationPageOptions([10, 25, 50]);
        });
    }

    protected function configureSections(): void
    {
        Section::configureUsing(static function (Section $section) {
            $section
                ->collapsible()
                ->persistCollapsed();
        });
    }

    protected function configureActions(): void
    {
        ActionGroup::configureUsing(static function (ActionGroup $action) {
            $action
                ->outlined()
                ->button()
                ->hiddenLabel()
                ->size(Size::ExtraSmall);
        });

        Action::configureUsing(static function (Action $action) {
            $action->outlined();
        });
    }

    protected function configureField(): void
    {
        Field::configureUsing(static function (Field $field) {
            $field->label(function (Field $component) {
                $name = $component->getName();

                return Str::of($name)
                    ->when(
                        value: str_starts_with($name, 'is_'),
                        callback: fn (Stringable $string) => $string->remove('is_'),
                    )
                    ->headline();
            });
        });

        Select::configureUsing(static function (Select $select) {
            $select->native(false)->selectablePlaceholder(false);
        });
    }

    protected function configureColumns(): void
    {
        Column::configureUsing(static function (Column $column) {
            $column->label(function (Column $component) {
                $name = $component->getName();

                return str($name)
                    ->when(
                        value: str_starts_with($name, 'is_'),
                        callback: fn (Stringable $string) => $string->remove('is_'),
                    )
                    ->beforeLast('.')
                    ->afterLast('.')
                    ->kebab()
                    ->replace(['-', '_'], ' ')
                    ->headline()
                    ->acronym()
                    ->toString();
            });
        });

        TextColumn::configureUsing(static function (TextColumn $component) {
            if (! str_ends_with($component->getName(), '_at')) {
                return;
            }

            $component
                ->since()
                ->dateTimeTooltip('M j, Y \a\t h:i A')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: $component->getName() !== 'created_at');
        });

        ExportColumn::configureUsing(static function (ExportColumn $exportColumn) {
            $exportColumn->label(function (ExportColumn $component) {
                return str($component->getName())
                    ->beforeLast('.')
                    ->afterLast('.')
                    ->kebab()
                    ->replace(['-', '_'], ' ')
                    ->headline()
                    ->acronym()
                    ->toString();
            });
        });

        IconColumn::configureUsing(static function (IconColumn $iconColumn) {
            $iconColumn->alignCenter();
        });

        ToggleColumn::configureUsing(static function (ToggleColumn $toggleColumn) {
            $toggleColumn->alignCenter()->afterStateUpdated(function ($state) {
                Notification::make()
                    ->title('Toggled state to: '.($state ? 'Yes' : 'No'))
                    ->success()
                    ->send();
            });
        });

        TextColumn::macro('abbreviate', function (): TextColumn {
            /** @var TextColumn $this */
            if (! $this->isNumeric()) {
                throw new \LogicException('The abbreviate column can only be used on numeric columns.');
            }

            return $this->formatStateUsing(function ($state) {
                return Number::abbreviate($state);
            });
        });
    }

    private function configureFilters(): void
    {
        SelectFilter::configureUsing(static function (SelectFilter $selectFilter) {
            $selectFilter->native(false)->selectablePlaceholder(false);
        });
        TrashedFilter::configureUsing(static function (TrashedFilter $trashedFilter) {
            $trashedFilter->label('Trashed');
        });
    }

    private function configureEntries(): void
    {
        TextEntry::macro('abbreviate', function (): TextEntry {
            /** @var TextEntry $this */
            if (! $this->isNumeric()) {
                throw new \LogicException('The abbreviate entry can only be used on numeric entries.');
            }

            return $this->formatStateUsing(function ($state) {
                return Number::abbreviate($state);
            });
        });
    }
}
