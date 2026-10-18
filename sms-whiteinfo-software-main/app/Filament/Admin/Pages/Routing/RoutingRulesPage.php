<?php

namespace App\Filament\Admin\Pages\Routing;

use App\Enums\SupermanNavigationGroup;
use App\Models\Country;
use App\Models\Gateway;
use App\Models\Operator;
use App\Models\RoutePlan;
use App\Models\RoutePlanGateway;
use App\Models\RouteRule;
use App\Models\Sender;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use UnitEnum;

class RoutingRulesPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Routing;

    protected static ?string $navigationLabel = 'Routing Rules & Plans';

    protected static ?string $title = 'SMS Routing Plans & Rule Matrix';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'routing/rules';

    protected string $view = 'filament.admin.pages.routing.routing-rules-page';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make('create_plan')
                ->label('New Route Plan')
                ->icon(Heroicon::OutlinedPlus)
                ->model(RoutePlan::class)
                ->form([
                    TextInput::make('name')
                        ->label('Plan Name')
                        ->placeholder('e.g. High Priority OTP Route')
                        ->required(),
                    TextInput::make('code')
                        ->label('Plan Code')
                        ->placeholder('e.g. OTP_PRIORITY')
                        ->required(),
                    Select::make('traffic_type')
                        ->label('Traffic Type')
                        ->options([
                            'all' => 'All Traffic (Universal)',
                            'otp' => 'OTP Messages',
                            'transactional' => 'Transactional Alerts',
                            'promotional' => 'Promotional / Marketing',
                        ])
                        ->default('all')
                        ->required(),
                    Select::make('failover_strategy')
                        ->label('Failover Strategy')
                        ->options([
                            'priority_ordered' => 'Priority 1 -> Priority 2 -> Priority 3 (Strict Order)',
                            'round_robin' => 'Round Robin (Load Balanced)',
                            'lowest_cost' => 'Least Cost Routing (Cost Optimized)',
                        ])
                        ->default('priority_ordered')
                        ->required(),
                    Textarea::make('description')
                        ->label('Description')
                        ->placeholder('Optional description of this routing profile'),
                    Toggle::make('is_default')
                        ->label('Set as Default for this Traffic Type')
                        ->default(false),
                    Toggle::make('is_enabled')
                        ->label('Plan Enabled')
                        ->default(true),
                    Repeater::make('gateways')
                        ->label('Provider Priority Chain (1 -> 2 -> 3)')
                        ->schema([
                            Select::make('gateway_id')
                                ->label('Provider / Gateway')
                                ->options(Gateway::where('is_enabled', true)->pluck('name', 'id'))
                                ->required(),
                            TextInput::make('priority')
                                ->label('Priority (1=Primary, 2=Failover 1, 3=Failover 2)')
                                ->numeric()
                                ->minValue(1)
                                ->default(1)
                                ->required(),
                        ])
                        ->columns(2)
                        ->defaultItems(2),
                ])
                ->using(function (array $data): RoutePlan {
                    $gateways = $data['gateways'] ?? [];
                    unset($data['gateways']);

                    if (! empty($data['is_default'])) {
                        RoutePlan::where('traffic_type', $data['traffic_type'])->update(['is_default' => false]);
                    }

                    $plan = RoutePlan::create($data);

                    foreach ($gateways as $item) {
                        RoutePlanGateway::create([
                            'route_plan_id' => $plan->id,
                            'gateway_id' => $item['gateway_id'],
                            'priority' => $item['priority'] ?? 1,
                            'is_active' => true,
                        ]);
                    }

                    return $plan;
                }),

            CreateAction::make('create_rule')
                ->label('New Routing Rule')
                ->icon(Heroicon::OutlinedArrowsRightLeft)
                ->color('gray')
                ->model(RouteRule::class)
                ->form([
                    TextInput::make('name')
                        ->label('Rule Name')
                        ->placeholder('e.g. Grameenphone Masking via Infozillion')
                        ->required(),
                    Select::make('route_plan_id')
                        ->label('Route Plan')
                        ->options(RoutePlan::pluck('name', 'id'))
                        ->required(),
                    Select::make('operator_id')
                        ->label('Target Operator (Optional)')
                        ->options(Operator::pluck('name', 'id'))
                        ->placeholder('Any Operator / All'),
                    Select::make('country_id')
                        ->label('Target Country (Optional)')
                        ->options(Country::pluck('name', 'id'))
                        ->placeholder('Any Country / All'),
                    Select::make('sender_id')
                        ->label('Sender CLI (Optional)')
                        ->options(Sender::pluck('sender_id', 'id'))
                        ->placeholder('Any Sender CLI / All'),
                    Select::make('traffic_type')
                        ->label('Traffic Type (Optional)')
                        ->options([
                            'otp' => 'OTP',
                            'transactional' => 'Transactional',
                            'promotional' => 'Promotional',
                        ])
                        ->placeholder('Any Traffic Type'),
                    TextInput::make('priority')
                        ->label('Rule Evaluation Priority')
                        ->numeric()
                        ->default(1)
                        ->required(),
                    Toggle::make('is_enabled')
                        ->label('Rule Enabled')
                        ->default(true),
                ]),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(RoutePlan::query()->with(['planGateways.gateway']))
            ->columns([
                TextColumn::make('name')
                    ->label('Plan Name')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('code')
                    ->label('Code')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('traffic_type')
                    ->label('Traffic')
                    ->badge()
                    ->state(fn (RoutePlan $r) => strtoupper($r->traffic_type))
                    ->color(fn (RoutePlan $r) => match ($r->traffic_type) {
                        'otp' => 'danger',
                        'transactional' => 'info',
                        'promotional' => 'warning',
                        default => 'success',
                    }),

                TextColumn::make('gateways_summary')
                    ->label('Priority Chain (1 → 2 → 3)')
                    ->state(function (RoutePlan $record): string {
                        $items = $record->planGateways->sortBy('priority');
                        if ($items->isEmpty()) {
                            return 'No providers configured';
                        }

                        return $items->map(fn ($pg) => "P{$pg->priority}: ".($pg->gateway?->name ?? 'N/A'))->implode('  ➔  ');
                    })
                    ->badge()
                    ->color('primary'),

                TextColumn::make('is_default')
                    ->label('Default')
                    ->badge()
                    ->formatStateUsing(fn (bool $state) => $state ? 'YES' : 'NO')
                    ->color(fn (bool $state) => $state ? 'success' : 'gray')
                    ->alignCenter(),

                TextColumn::make('is_enabled')
                    ->label('Status')
                    ->badge()
                    ->state(fn (RoutePlan $r) => $r->is_enabled ? 'ACTIVE' : 'INACTIVE')
                    ->color(fn (RoutePlan $r) => $r->is_enabled ? 'success' : 'danger'),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make()
                    ->form([
                        TextInput::make('name')->required(),
                        Select::make('traffic_type')
                            ->options([
                                'all' => 'All Traffic',
                                'otp' => 'OTP Messages',
                                'transactional' => 'Transactional Alerts',
                                'promotional' => 'Promotional / Marketing',
                            ])
                            ->required(),
                        Select::make('failover_strategy')
                            ->options([
                                'priority_ordered' => 'Priority 1 -> Priority 2 -> Priority 3 (Strict Order)',
                                'round_robin' => 'Round Robin (Load Balanced)',
                                'lowest_cost' => 'Least Cost Routing (Cost Optimized)',
                            ])
                            ->required(),
                        Toggle::make('is_default')->label('Default for Traffic Type'),
                        Toggle::make('is_enabled')->label('Plan Enabled'),
                    ]),
                \Filament\Actions\DeleteAction::make(),
            ]);
    }
}
