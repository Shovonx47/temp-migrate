<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $title ?? 'SMS Report Details' }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
            margin: 0;
            padding: 10px;
        }
        .header {
            margin-bottom: 20px;
            border-bottom: 2px solid #0d9488;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0 0 5px 0;
            color: #0f766e;
            font-size: 20px;
        }
        .meta {
            font-size: 10px;
            color: #666;
        }
        .kpi-container {
            width: 100%;
            margin-bottom: 20px;
        }
        .kpi-table {
            width: 100%;
            border-collapse: collapse;
        }
        .kpi-box {
            padding: 10px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            text-align: center;
        }
        .kpi-box .label {
            font-size: 9px;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 3px;
        }
        .kpi-box .value {
            font-size: 14px;
            font-weight: bold;
            color: #0f172a;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 10px;
        }
        table.data-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-weight: bold;
            padding: 6px 8px;
            border: 1px solid #cbd5e1;
            text-align: left;
        }
        table.data-table td {
            padding: 5px 8px;
            border: 1px solid #e2e8f0;
        }
        table.data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 9px;
            border-radius: 3px;
            font-weight: bold;
        }
        .badge-success { background-color: #dcfce7; color: #15803d; }
        .badge-danger { background-color: #fee2e2; color: #b91c1c; }
        .badge-warning { background-color: #fef3c7; color: #b45309; }
        .badge-info { background-color: #e0f2fe; color: #0369a1; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .footer {
            margin-top: 25px;
            font-size: 9px;
            color: #94a3b8;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title ?? 'SMS Report Details' }}</h1>
        <div class="meta">
            <strong>Customer:</strong> {{ $user->name ?? 'Customer' }} ({{ $user->email ?? '' }}) &nbsp;|&nbsp;
            <strong>Period:</strong> {{ $period ?? 'All Time' }} &nbsp;|&nbsp;
            <strong>Generated:</strong> {{ now()->format('d M Y, h:i A') }}
        </div>
    </div>

    @if(isset($summary))
    <div class="kpi-container">
        <table class="kpi-table">
            <tr>
                <td style="width: 16.6%;" class="kpi-box">
                    <div class="label">Total SMS</div>
                    <div class="value">{{ number_format($summary['total'] ?? 0) }}</div>
                </td>
                <td style="width: 16.6%;" class="kpi-box">
                    <div class="label">Delivered / Sent</div>
                    <div class="value" style="color: #16a34a;">{{ number_format($summary['delivered'] ?? 0) }}</div>
                </td>
                <td style="width: 16.6%;" class="kpi-box">
                    <div class="label">Failed</div>
                    <div class="value" style="color: #dc2626;">{{ number_format($summary['failed'] ?? 0) }}</div>
                </td>
                <td style="width: 16.6%;" class="kpi-box">
                    <div class="label">Success Rate</div>
                    <div class="value">{{ $summary['success_rate'] ?? '0%' }}</div>
                </td>
                <td style="width: 16.6%;" class="kpi-box">
                    <div class="label">Total Cost</div>
                    <div class="value" style="color: #0284c7;">{{ number_format($summary['total_cost'] ?? 0, 2) }} BDT</div>
                </td>
                <td style="width: 16.6%;" class="kpi-box">
                    <div class="label">Total Parts</div>
                    <div class="value">{{ number_format($summary['total_parts'] ?? 0) }}</div>
                </td>
            </tr>
        </table>
    </div>
    @endif

    @if(!empty($dateWise) && count($dateWise) > 0)
    <h3 style="margin: 15px 0 5px 0; color: #334155; font-size: 13px;">Date-Wise Summary Breakdown</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Date</th>
                <th class="text-right">Total SMS</th>
                <th class="text-right">Delivered / Sent</th>
                <th class="text-right">Failed</th>
                <th class="text-right">Success Rate</th>
                <th class="text-right">Total Cost (BDT)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dateWise as $row)
            <tr>
                <td><strong>{{ $row['date'] }}</strong></td>
                <td class="text-right">{{ number_format($row['total']) }}</td>
                <td class="text-right" style="color: #16a34a;">{{ number_format($row['delivered']) }}</td>
                <td class="text-right" style="color: #dc2626;">{{ number_format($row['failed']) }}</td>
                <td class="text-right">{{ $row['success_rate'] }}</td>
                <td class="text-right">{{ number_format($row['cost'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if(!empty($records) && count($records) > 0)
    <h3 style="margin: 20px 0 5px 0; color: #334155; font-size: 13px;">Message Details (Top {{ count($records) }} Records)</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Recipient</th>
                <th>Sender ID</th>
                <th>Content</th>
                <th class="text-center">Count</th>
                <th class="text-right">Cost</th>
                <th>Status</th>
                <th>Failure Reason</th>
                <th>Date & Time</th>
            </tr>
        </thead>
        <tbody>
            @foreach($records as $msg)
            <tr>
                <td>MSG-{{ str_pad($msg->id, 6, '0', STR_PAD_LEFT) }}</td>
                <td>{{ $msg->to }}</td>
                <td>{{ $msg->sender->sender_id ?? '—' }}</td>
                <td>{{ \Illuminate\Support\Str::limit($msg->body, 40) }}</td>
                <td class="text-center">{{ $msg->parts ?? 1 }}</td>
                <td class="text-right">{{ number_format($msg->cost ?? 0, 2) }} BDT</td>
                <td>
                    <span class="badge {{ $msg->status->value === 'delivered' || $msg->status->value === 'sent' ? 'badge-success' : ($msg->status->value === 'failed' || $msg->status->value === 'dlr_failed' ? 'badge-danger' : 'badge-warning') }}">
                        {{ ucfirst($msg->status->value ?? $msg->status) }}
                    </span>
                </td>
                <td>{{ $msg->failed_reason ?? '—' }}</td>
                <td>{{ $msg->created_at?->format('d M Y, h:i A') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="footer">
        Generated by Sendora SMS Platform &bull; Page 1 &bull; Confidential &copy; {{ date('Y') }}
    </div>
</body>
</html>
