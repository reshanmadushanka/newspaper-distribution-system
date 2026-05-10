<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Daily Sales Report - {{ $report['date'] }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 0;
        }
        
        :root {
            --primary: #0f172a;
            --accent: #2563eb;
            --success: #059669;
            --danger: #dc2626;
            --warning: #d97706;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-400: #94a3b8;
            --gray-600: #475569;
            --gray-800: #1e293b;
        }

        body {
            font-family: 'Inter', 'DejaVu Sans', sans-serif;
            font-size: 8pt;
            line-height: 1.5;
            color: var(--gray-800);
            margin: 0;
            padding: 0;
            background: #fff;
        }

        .header {
            background: var(--primary);
            color: white;
            padding: 25px 40px;
            position: relative;
        }

        .header-content {
            display: table;
            width: 100%;
        }

        .header-left {
            display: table-cell;
            vertical-align: middle;
        }

        .header-right {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
        }

        .report-title {
            font-size: 18pt;
            font-weight: 800;
            margin: 0;
            letter-spacing: -0.5px;
            text-transform: uppercase;
        }

        .report-date {
            font-size: 10pt;
            color: var(--gray-400);
            margin-top: 4px;
        }

        .container {
            padding: 20px 40px;
        }

        /* Summary Grid */
        .summary-wrapper {
            margin-bottom: 25px;
        }

        .summary-grid {
            display: table;
            width: 100%;
            border-spacing: 10px 0;
            margin-left: -10px;
            margin-right: -10px;
        }

        .summary-item {
            display: table-cell;
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            padding: 12px 15px;
            width: 14.28%; /* 7 items */
        }

        .summary-label {
            font-size: 6.5pt;
            font-weight: 700;
            color: var(--gray-400);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .summary-value {
            font-size: 12pt;
            font-weight: 800;
            color: var(--primary);
        }

        .summary-subtext {
            font-size: 6pt;
            color: var(--gray-600);
            margin-top: 2px;
        }

        /* Sections */
        .section-header {
            margin: 20px 0 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid var(--gray-100);
        }

        .section-title {
            font-size: 10pt;
            font-weight: 800;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Tables */
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 20px;
        }

        th {
            background: var(--gray-100);
            color: var(--gray-600);
            font-size: 7pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 10px 12px;
            text-align: left;
            border-bottom: 2px solid var(--gray-200);
        }

        td {
            padding: 8px 12px;
            border-bottom: 1px solid var(--gray-100);
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .tr-even {
            background: var(--gray-50);
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: 700; }
        
        .val-positive { color: var(--success); }
        .val-negative { color: var(--danger); }
        .val-primary { color: var(--accent); }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 6.5pt;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-paid { background: #dcfce7; color: #166534; }
        .badge-draft { background: #fef9c3; color: #854d0e; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }

        /* Footer */
        .footer {
            margin-top: 30px;
            padding: 20px 40px;
            border-top: 1px solid var(--gray-100);
            text-align: center;
            font-size: 7pt;
            color: var(--gray-400);
        }

        .total-row td {
            background: var(--gray-100);
            font-weight: 800;
            font-size: 8.5pt;
            border-top: 2px solid var(--gray-200);
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="header-left">
                <h1 class="report-title">Daily Sales Report</h1>
                <div class="report-date">{{ \Carbon\Carbon::parse($report['date'])->format('F d, Y') }}</div>
            </div>
            <div class="header-right">
                <div style="font-size: 8pt; color: var(--gray-400);">Generated by Newspaper System</div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="summary-wrapper">
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-label">Total Invoices</div>
                    <div class="summary-value">{{ $report['summary']['total_invoices'] }}</div>
                    <div class="summary-subtext">{{ $report['summary']['paid_count'] }} Paid / {{ $report['summary']['draft_count'] }} Draft</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Revenue</div>
                    <div class="summary-value">Rs. {{ number_format((float)$report['summary']['total_revenue'], 2) }}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Cost</div>
                    <div class="summary-value" style="color: var(--danger);">Rs. {{ number_format((float)$report['summary']['total_cost'], 2) }}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Gross Profit</div>
                    <div class="summary-value {{ $report['summary']['total_profit'] >= 0 ? 'val-positive' : 'val-negative' }}">
                        Rs. {{ number_format((float)$report['summary']['total_profit'], 2) }}
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Profit Margin</div>
                    <div class="summary-value">{{ $report['summary']['profit_margin'] }}%</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Items Sold</div>
                    <div class="summary-value">{{ $report['summary']['total_items'] }}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Total Qty</div>
                    <div class="summary-value">{{ $report['summary']['total_quantity'] }}</div>
                </div>
            </div>
        </div>

        <div class="section-header">
            <div class="section-title">Performance by Shop</div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Shop Name</th>
                    <th class="text-center">Invoices</th>
                    <th class="text-center">Quantity</th>
                    <th class="text-right">Revenue</th>
                    <th class="text-right">Cost</th>
                    <th class="text-right">Profit</th>
                    <th class="text-right">Margin</th>
                </tr>
            </thead>
            <tbody>
                @foreach($report['by_shop'] as $index => $shop)
                <tr class="{{ $index % 2 == 0 ? '' : 'tr-even' }}">
                    <td class="font-bold">{{ $shop['shop_name'] }}</td>
                    <td class="text-center">{{ $shop['invoice_count'] }}</td>
                    <td class="text-center">{{ $shop['quantity'] }}</td>
                    <td class="text-right">Rs. {{ number_format((float)$shop['total_revenue'], 2) }}</td>
                    <td class="text-right val-negative">Rs. {{ number_format((float)$shop['total_cost'], 2) }}</td>
                    <td class="text-right font-bold {{ $shop['total_profit'] >= 0 ? 'val-positive' : 'val-negative' }}">
                        Rs. {{ number_format((float)$shop['total_profit'], 2) }}
                    </td>
                    <td class="text-right">{{ $shop['profit_margin'] }}%</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td>GRAND TOTAL</td>
                    <td class="text-center">{{ $report['summary']['total_invoices'] }}</td>
                    <td class="text-center">{{ $report['summary']['total_quantity'] }}</td>
                    <td class="text-right">Rs. {{ number_format((float)$report['summary']['total_revenue'], 2) }}</td>
                    <td class="text-right" style="color: var(--danger);">Rs. {{ number_format((float)$report['summary']['total_cost'], 2) }}</td>
                    <td class="text-right {{ $report['summary']['total_profit'] >= 0 ? 'val-positive' : 'val-negative' }}">
                        Rs. {{ number_format((float)$report['summary']['total_profit'], 2) }}
                    </td>
                    <td class="text-right">{{ $report['summary']['profit_margin'] }}%</td>
                </tr>
            </tfoot>
        </table>

        <div class="section-header">
            <div class="section-title">Detailed Invoice Log</div>
        </div>
        <table>
            <thead>
                <tr>
                    <th># ID</th>
                    <th>Shop</th>
                    <th>Status</th>
                    <th class="text-center">Items</th>
                    <th class="text-center">Qty</th>
                    <th class="text-right">Revenue</th>
                    <th class="text-right">Cost</th>
                    <th class="text-right">Profit</th>
                </tr>
            </thead>
            <tbody>
                @forelse($report['invoices'] as $index => $inv)
                <tr class="{{ $index % 2 == 0 ? '' : 'tr-even' }}">
                    <td class="font-bold">#{{ $inv['id'] }}</td>
                    <td>{{ $inv['shop_name'] }}</td>
                    <td><span class="badge badge-{{ $inv['status'] }}">{{ $inv['status'] }}</span></td>
                    <td class="text-center">{{ $inv['items_count'] }}</td>
                    <td class="text-center">{{ $inv['quantity'] }}</td>
                    <td class="text-right">Rs. {{ number_format((float)$inv['total_revenue'], 2) }}</td>
                    <td class="text-right val-negative">Rs. {{ number_format((float)$inv['total_cost'], 2) }}</td>
                    <td class="text-right font-bold {{ $inv['total_profit'] >= 0 ? 'val-positive' : 'val-negative' }}">
                        Rs. {{ number_format((float)$inv['total_profit'], 2) }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 40px; color: var(--gray-400);">No transactions recorded for this period.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer">
        Report generated on {{ now()->format('M d, Y') }} at {{ now()->format('h:i A') }} • This is a computer-generated document.
    </div>
</body>
</html>

