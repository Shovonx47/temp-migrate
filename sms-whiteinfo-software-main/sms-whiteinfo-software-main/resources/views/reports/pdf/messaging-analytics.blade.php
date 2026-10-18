<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Messaging Analytics Report</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
            margin: 0;
            padding: 15px;
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
        .kpi-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px;
            margin-bottom: 20px;
        }
        .kpi-box {
            padding: 12px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            text-align: center;
        }
        .kpi-box .label {
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .kpi-box .value {
            font-size: 16px;
            font-weight: bold;
            color: #0f172a;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 10px;
        }
        table.data-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-weight: bold;
            padding: 8px 10px;
            border: 1px solid #cbd5e1;
            text-align: left;
        }
        table.data-table td {
            padding: 6px 10px;
            border: 1px solid #e2e8f0;
        }
        table.data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .text-right { text-align: right; }
        .footer {
            margin-top: 30px;
            font-size: 9px;
            color: #94a3b8;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Messaging Analytics Report</h1>
        <div class="meta">
            <strong>Customer:</strong> {{ $user->name ?? 'Customer' }} ({{ $user->email ?? '' }}) &nbsp;|&nbsp;
            <strong>Period:</strong> {{ $period ?? 'Selected Period' }} &nbsp;|&nbsp;
            <strong>Generated:</strong> {{ now()->format('d M Y, h:i A') }}
        </div>
    </div>

    <div style="margin-bottom: 15px; font-weight: bold; color: #1e293b; font-size: 13px;">Performance Summary</div>
    <table class="kpi-table" style="margin-left: -8px; margin-right: -8px;">
        <tr>
            <td style="width: 25%;" class="kpi-box">
                <div class="label">Total Messages</div>
                <div class="value">{{ number_format($stats['total'] ?? 0) }}</div>
            </td>
            <td style="width: 25%;" class="kpi-box">
                <div class="label">Success Rate</div>
                <div class="value" style="color: #16a34a;">{{ $stats['success_rate'] ?? '0%' }}</div>
            </td>
            <td style="width: 25%;" class="kpi-box">
                <div class="label">Delivered</div>
                <div class="value" style="color: #0284c7;">{{ number_format($stats['delivered'] ?? 0) }}</div>
            </td>
            <td style="width: 25%;" class="kpi-box">
                <div class="label">Failed</div>
                <div class="value" style="color: #dc2626;">{{ number_format($stats['failed'] ?? 0) }}</div>
            </td>
        </tr>
        <tr>
            <td style="width: 25%;" class="kpi-box">
                <div class="label">Total Cost</div>
                <div class="value" style="color: #d97706;">{{ number_format($stats['total_cost'] ?? 0, 2) }} BDT</div>
            </td>
            <td style="width: 25%;" class="kpi-box">
                <div class="label">Avg Parts / SMS</div>
                <div class="value">{{ number_format($stats['avg_parts'] ?? 1, 1) }}</div>
            </td>
            <td style="width: 25%;" class="kpi-box">
                <div class="label">Unicode Messages</div>
                <div class="value">{{ number_format($stats['unicode'] ?? 0) }}</div>
            </td>
            <td style="width: 25%;" class="kpi-box">
                <div class="label">Total SMS Parts</div>
                <div class="value">{{ number_format($stats['total_parts'] ?? 0) }}</div>
            </td>
        </tr>
    </table>

    @if(!empty($dailyStats) && count($dailyStats) > 0)
    <div style="margin: 20px 0 5px 0; font-weight: bold; color: #1e293b; font-size: 13px;">Daily Messaging Breakdown</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Date</th>
                <th class="text-right">Total Messages</th>
                <th class="text-right">Successful</th>
                <th class="text-right">Delivered</th>
                <th class="text-right">Failed</th>
                <th class="text-right">Success Rate</th>
                <th class="text-right">Spend (BDT)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dailyStats as $day)
            <tr>
                <td><strong>{{ $day['date'] }}</strong></td>
                <td class="text-right">{{ number_format($day['total']) }}</td>
                <td class="text-right" style="color: #16a34a;">{{ number_format($day['successful']) }}</td>
                <td class="text-right">{{ number_format($day['delivered']) }}</td>
                <td class="text-right" style="color: #dc2626;">{{ number_format($day['failed']) }}</td>
                <td class="text-right">{{ $day['success_rate'] }}</td>
                <td class="text-right">{{ number_format($day['cost'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="footer">
        Generated by Sendora SMS Platform &bull; Confidential &copy; {{ date('Y') }}
    </div>
</body>
</html>
