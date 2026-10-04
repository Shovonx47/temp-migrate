<?php

namespace App\Filament\Admin\Pages\Billing;

use App\Enums\SupermanNavigationGroup;
use App\Enums\TariffPlanAssignmentStatus;
use App\Models\TariffPlan;
use App\Models\TariffPlanAssignment;
use App\Models\User;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class UserAssignmentPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Billing;

    protected static ?string $navigationLabel = 'User-wise Assignment';

    protected static ?string $title = 'User-wise Tariff Assignment';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.admin.billing.user-assignment';

    /** The currently selected customer ID. */
    public ?int $selectedCustomerId = null;

    /** Form state */
    public ?int $newPlanId = null;

    public ?string $effectiveFrom = null;

    public ?string $assignmentNotes = null;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getCurrentAssignment(): ?TariffPlanAssignment
    {
        if (! $this->selectedCustomerId) {
            return null;
        }

        return TariffPlanAssignment::with('tariffPlan')
            ->where('user_id', $this->selectedCustomerId)
            ->where('status', TariffPlanAssignmentStatus::Active->value)
            ->latest('effective_from')
            ->first();
    }

    public function getSelectedCustomer(): ?User
    {
        if (! $this->selectedCustomerId) {
            return null;
        }

        return User::customers()->find($this->selectedCustomerId);
    }

    /**
     * Assign a new plan to the selected customer.
     * Supersedes any existing active assignment.
     */
    public function assignPlan(): void
    {
        $this->validate([
            'selectedCustomerId' => 'required|exists:users,id',
            'newPlanId' => 'required|exists:tariff_plans,id',
            'effectiveFrom' => 'required|date',
        ], [
            'selectedCustomerId.required' => 'Please select a customer.',
            'newPlanId.required' => 'Please select a tariff plan.',
            'effectiveFrom.required' => 'Please set an effective date.',
        ]);

        $plan = TariffPlan::find($this->newPlanId);
        if ($plan->status->value !== 'active') {
            Notification::make()->danger()->title('Cannot assign a non-Active plan.')->send();

            return;
        }

        // Supersede all existing active assignments for this user
        TariffPlanAssignment::where('user_id', $this->selectedCustomerId)
            ->where('status', TariffPlanAssignmentStatus::Active->value)
            ->update(['status' => TariffPlanAssignmentStatus::Superseded->value]);

        // Create the new assignment
        TariffPlanAssignment::create([
            'user_id' => $this->selectedCustomerId,
            'tariff_plan_id' => $this->newPlanId,
            'assigned_by' => auth()->id(),
            'effective_from' => $this->effectiveFrom,
            'status' => TariffPlanAssignmentStatus::Active->value,
            'notes' => $this->assignmentNotes,
        ]);

        $this->newPlanId = null;
        $this->effectiveFrom = null;
        $this->assignmentNotes = null;

        Notification::make()
            ->success()
            ->title('Plan assigned successfully.')
            ->send();
    }

    /** Assignment history table for the selected customer. */
    public function table(Table $table): Table
    {
        return $table
            ->query(
                TariffPlanAssignment::with(['tariffPlan', 'assignedBy'])
                    ->when(
                        $this->selectedCustomerId,
                        fn (Builder $q) => $q->where('user_id', $this->selectedCustomerId),
                        fn (Builder $q) => $q->whereRaw('1=0'), // empty when no customer selected
                    )
            )
            ->columns([
                TextColumn::make('tariffPlan.name')
                    ->label('Plan'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('effective_from')
                    ->label('Effective From')
                    ->date(),
                TextColumn::make('assignedBy.name')
                    ->label('Assigned By')
                    ->placeholder('System'),
                TextColumn::make('created_at')
                    ->label('Assigned At')
                    ->dateTime(),
                TextColumn::make('notes')
                    ->placeholder('—')
                    ->wrap(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated(false);
    }
}
