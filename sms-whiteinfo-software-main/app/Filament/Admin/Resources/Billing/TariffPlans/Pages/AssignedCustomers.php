<?php

namespace App\Filament\Admin\Resources\Billing\TariffPlans\Pages;

use App\Filament\Admin\Resources\Billing\TariffPlans\TariffPlanResource;
use App\Filament\Admin\Resources\Users\Customers\CustomerResource;
use App\Models\TariffPlanAssignment;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class AssignedCustomers extends ViewRecord implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = TariffPlanResource::class;

    protected string $view = 'filament.admin.resources.billing.tariff-plans.pages.assigned-customers';

    public int $activeCustomerAssignmentsCount = 0;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->activeCustomerAssignmentsCount = $this->getRecord()->activeCustomerAssignments()->count();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back to tariff plans')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(TariffPlanResource::getUrl('index')),
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return "{$this->getRecordTitle()} — Assigned Customers";
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                TariffPlanAssignment::query()
                    ->active()
                    ->where('tariff_plan_id', $this->getRecord()->getKey())
                    ->whereHas('user', fn (Builder $query): Builder => $query->customers())
                    ->with([
                        'user:id,name,email,phone,business_name,status,balance',
                        'assignedBy:id,name',
                    ])
            )
            ->columns([
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('user.business_name')
                    ->label('Business')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('user.phone')
                    ->label('Phone')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('user.status')
                    ->label('Account status')
                    ->badge(),
                TextColumn::make('effective_from')
                    ->label('Effective from')
                    ->date()
                    ->sortable(),
                TextColumn::make('assignedBy.name')
                    ->label('Assigned by')
                    ->placeholder('System'),
                TextColumn::make('notes')
                    ->label('Notes')
                    ->wrap()
                    ->placeholder('—'),
            ])
            ->recordActions([
                Action::make('viewCustomer')
                    ->label('View customer')
                    ->icon('heroicon-o-eye')
                    ->url(fn (TariffPlanAssignment $record): string => CustomerResource::getUrl('view', ['record' => $record->user_id])),
                Action::make('editCustomer')
                    ->label('Edit customer')
                    ->icon('heroicon-o-pencil-square')
                    ->color('gray')
                    ->url(fn (TariffPlanAssignment $record): string => CustomerResource::getUrl('edit', ['record' => $record->user_id])),
            ])
            ->defaultSort('effective_from', 'desc');
    }
}
