<?php

namespace App\Filament\Customer\Pages;

use App\Enums\CustomerNavigationGroup;
use App\Enums\MessageStatus;
use App\Models\Message;
use App\Models\Sender;
use App\Services\ReportExportService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use UnitEnum;

class SmsReportDetails extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|UnitEnum|null $navigationGroup = CustomerNavigationGroup::Billing;

    protected static ?string $navigationLabel = 'SMS Report Details';

    protected static ?string $title = 'SMS Report Details';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.customer.pages.sms-report-details';

    public function getHeaderActions(): array
    {
        return [
            Action::make('exportAs')
                ->label('Export As')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->modalHeading('Export Report')
                ->modalDescription('Select your preferred report format to download.')
                ->modalSubmitActionLabel('Download Report')
                ->schema([
                    \Filament\Forms\Components\Radio::make('format')
                        ->label('Select Format')
                        ->options([
                            'excel' => 'Excel / CSV Spreadsheet (.csv)',
                            'pdf' => 'PDF Document (.pdf)',
                        ])
                        ->default('excel')
                        ->required(),
                ])
                ->action(function (array $data) {
                    return ($data['format'] ?? 'excel') === 'pdf'
                        ? $this->exportPdf()
                        : $this->exportExcel();
                }),
        ];
    }

    public function getSummaryStats(): array
    {
        $user = auth()->user();
        if (! $user) {
            return [];
        }

        $query = $this->getFilteredQuery();

        $stats = (clone $query)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("COALESCE(SUM(CASE WHEN status IN ('sent', 'delivered') THEN 1 ELSE 0 END), 0) as successful")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END), 0) as delivered")
            ->selectRaw("COALESCE(SUM(CASE WHEN status IN ('failed', 'dlr_failed') THEN 1 ELSE 0 END), 0) as failed")
            ->selectRaw("COALESCE(SUM(CASE WHEN status NOT IN ('failed', 'dlr_failed') THEN cost ELSE 0 END), 0) as total_cost")
            ->selectRaw('COALESCE(SUM(parts), 0) as total_parts')
            ->selectRaw('COALESCE(AVG(parts), 0) as avg_parts')
            ->selectRaw('COALESCE(SUM(is_unicode), 0) as unicode')
            ->first();

        $total = (int) ($stats->total ?? 0);
        $successful = (int) ($stats->successful ?? 0);
        $delivered = (int) ($stats->delivered ?? 0);
        $failed = (int) ($stats->failed ?? 0);
        $totalCost = (float) ($stats->total_cost ?? 0);
        $totalParts = (int) ($stats->total_parts ?? 0);
        $avgParts = (float) ($stats->avg_parts ?? 0);
        $unicode = (int) ($stats->unicode ?? 0);

        $successRate = $total > 0 ? round(($successful / $total) * 100, 1) : 0;
        $deliveryRate = $total > 0 ? round(($delivered / $total) * 100, 1) : 0;
        $failureRate = $total > 0 ? round(($failed / $total) * 100, 1) : 0;

        return [
            'total' => $total,
            'successful' => $successful,
            'delivered' => $delivered,
            'failed' => $failed,
            'success_rate' => $successRate.'%',
            'delivery_rate' => $deliveryRate.'%',
            'failure_rate' => $failureRate.'%',
            'total_cost' => $totalCost,
            'total_parts' => $totalParts,
            'avg_parts' => round($avgParts, 1),
            'unicode' => $unicode,
        ];
    }

    public function getDateWiseBreakdown(): array
    {
        $user = auth()->user();
        if (! $user) {
            return [];
        }

        $query = $this->getFilteredQuery();

        $records = (clone $query)
            ->selectRaw('DATE(created_at) as date_val')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("COALESCE(SUM(CASE WHEN status IN ('sent', 'delivered') THEN 1 ELSE 0 END), 0) as successful")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END), 0) as delivered")
            ->selectRaw("COALESCE(SUM(CASE WHEN status IN ('failed', 'dlr_failed') THEN 1 ELSE 0 END), 0) as failed")
            ->selectRaw("COALESCE(SUM(CASE WHEN status NOT IN ('failed', 'dlr_failed') THEN cost ELSE 0 END), 0) as cost")
            ->groupBy('date_val')
            ->orderBy('date_val', 'desc')
            ->limit(30)
            ->get();

        return $records->map(function ($row) {
            $total = (int) $row->total;
            $success = (int) $row->successful;
            $rate = $total > 0 ? round(($success / $total) * 100, 1).'%' : '0%';

            return [
                'date' => Carbon::parse($row->date_val)->format('d M Y'),
                'total' => $total,
                'successful' => $success,
                'delivered' => (int) $row->delivered,
                'failed' => (int) $row->failed,
                'success_rate' => $rate,
                'cost' => (float) $row->cost,
            ];
        })->toArray();
    }

    protected function getFilteredQuery(): Builder
    {
        $user = auth()->user();
        $query = Message::query()->where('user_id', $user->id);

        $tableFilters = $this->getTableFiltersForm()->getRawState();

        if (! empty($tableFilters['sender_id']['value'])) {
            $query->where('sender_id', $tableFilters['sender_id']['value']);
        }

        if (! empty($tableFilters['status']['value'])) {
            $query->where('status', $tableFilters['status']['value']);
        }

        if (! empty($tableFilters['date_filter']['created_from'])) {
            $query->whereDate('created_at', '>=', $tableFilters['date_filter']['created_from']);
        }

        if (! empty($tableFilters['date_filter']['created_until'])) {
            $query->whereDate('created_at', '<=', $tableFilters['date_filter']['created_until']);
        }

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => $this->getFilteredQuery()->with('sender'))
            ->heading('Detailed Message Log')
            ->defaultSort('id', 'desc')
            ->poll('30s')
            ->columns([
                TextColumn::make('id')
                    ->label('Message ID')
                    ->formatStateUsing(fn ($state) => 'MSG-'.str_pad((string) $state, 6, '0', STR_PAD_LEFT))
                    ->searchable(),
                TextColumn::make('to')
                    ->label('Recipient')
                    ->searchable(),
                TextColumn::make('sender.sender_id')
                    ->label('Sender ID')
                    ->placeholder('—'),
                TextColumn::make('body')
                    ->label('Content')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->body),
                TextColumn::make('parts')
                    ->label('Parts')
                    ->numeric()
                    ->alignCenter(),
                TextColumn::make('cost')
                    ->label('Cost')
                    ->money('BDT')
                    ->alignRight(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('failed_reason')
                    ->label('Failure Reason')
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Date & Time')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('sender_id')
                    ->label('Sender')
                    ->options(fn () => Sender::accessible()->pluck('sender_id', 'id')),
                SelectFilter::make('status')
                    ->options(MessageStatus::class),
                Filter::make('date_filter')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        DatePicker::make('created_from')
                            ->label('From Date'),
                        DatePicker::make('created_until')
                            ->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['created_from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                            ->when($data['created_until'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d));
                    }),
            ], layout: FiltersLayout::AboveContent);
    }

    public function exportExcel()
    {
        $user = auth()->user();
        $query = $this->getFilteredQuery()->with('sender')->latest('id');

        $headers = [
            'Message ID',
            'Recipient',
            'Sender ID',
            'Content',
            'Parts',
            'Cost (BDT)',
            'Status',
            'Failure Reason',
            'Date & Time',
        ];

        $rows = $query->lazy(500)->map(function ($msg) {
            return [
                'MSG-'.str_pad((string) $msg->id, 6, '0', STR_PAD_LEFT),
                $msg->to,
                $msg->sender->sender_id ?? '',
                $msg->body,
                $msg->parts ?? 1,
                number_format($msg->cost ?? 0, 4),
                $msg->status->value ?? $msg->status,
                $msg->failed_reason ?? '',
                $msg->created_at?->format('Y-m-d H:i:s'),
            ];
        });

        $filename = 'sms-report-details-'.now()->format('Y-m-d-His').'.csv';

        return ReportExportService::downloadCsv($filename, $headers, $rows);
    }

    public function exportPdf()
    {
        $user = auth()->user();
        $records = $this->getFilteredQuery()->with('sender')->latest('id')->limit(500)->get();
        $summary = $this->getSummaryStats();
        $dateWise = $this->getDateWiseBreakdown();

        $data = [
            'title' => 'SMS Detailed Report',
            'user' => $user,
            'summary' => $summary,
            'dateWise' => $dateWise,
            'records' => $records,
            'period' => 'Filtered Report (Generated '.now()->format('d M Y').')',
        ];

        $filename = 'sms-report-details-'.now()->format('Y-m-d-His').'.pdf';

        return ReportExportService::downloadPdf($filename, 'reports.pdf.sms-report-details', $data);
    }
}
