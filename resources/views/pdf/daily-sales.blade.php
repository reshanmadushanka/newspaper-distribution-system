<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    @php
        $titles = [
            'by-shop' => 'Sales Report by Shop',
            'by-newspaper' => 'Sales Report by Newspaper',
            'by-invoice' => 'Invoice Sales Report',
        ];
        $title = $titles[$reportType] ?? 'Sales Report';
        $dateFrom = \Carbon\Carbon::parse($report['date_from'])->format('M d, Y');
        $dateTo = \Carbon\Carbon::parse($report['date_to'])->format('M d, Y');
        $showProfit = $showProfit ?? true;
    @endphp
    <title>{{ $title }} - {{ $dateFrom }} to {{ $dateTo }}</title>
    <style>
        @page {
            size: A5 landscape;
            margin: 10px 12px;
        }

        :root {
            --primary: #0f172a;
            --accent: #2563eb;
            --success: #059669;
            --danger: #dc2626;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-500: #64748b;
            --gray-700: #334155;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8pt;
            line-height: 1.25;
            color: var(--gray-700);
            margin: 0;
            background: #fff;
        }

        .header {
            padding: 0 0 5px;
            border-bottom: 1px solid var(--gray-200);
            margin-bottom: 6px;
        }

        .header-content,
        .meta-grid,
        .summary-grid {
            display: table;
            width: 100%;
        }

        .header-left,
        .header-right,
        .meta-item,
        .summary-item {
            display: table-cell;
            vertical-align: top;
        }

        .header-right {
            text-align: right;
            color: var(--gray-500);
            font-size: 6.5pt;
        }

        .report-title {
            color: var(--primary);
            font-size: 12pt;
            font-weight: 800;
            margin: 0;
            text-transform: uppercase;
        }

        .report-date {
            color: var(--gray-500);
            font-size: 7.5pt;
            margin-top: 1px;
        }

        .meta-grid {
            border-spacing: 3px 0;
            margin: 0 0 5px -3px;
        }

        .meta-item {
            border-bottom: 1px solid var(--gray-200);
            padding: 2px 4px;
            width: 25%;
            font-size: 7.5pt;
        }

        .summary-grid {
            border-spacing: 3px 0;
            margin: 0 0 6px -3px;
        }

        .summary-item {
            border: 1px solid var(--gray-200);
            padding: 3px 5px;
        }

        .label {
            color: var(--gray-500);
            font-size: 6pt;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 1px;
        }

        .value {
            color: var(--primary);
            font-size: 9.5pt;
            font-weight: 800;
        }

        .section-title {
            color: var(--primary);
            font-size: 8pt;
            font-weight: 800;
            text-transform: uppercase;
            margin: 5px 0 3px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
            table-layout: fixed;
        }

        th {
            background: var(--gray-100);
            color: var(--gray-500);
            font-size: 8.5pt;
            font-weight: 800;
            text-transform: uppercase;
            padding: 4px 3px;
            text-align: left;
            border: 1px solid var(--gray-200);
            white-space: nowrap;
        }

        td {
            font-size: 10pt;
            padding: 4px 3px;
            border: 1px solid var(--gray-200);
            white-space: nowrap;
        }

        .invoice-report-table th:nth-child(1),
        .invoice-report-table td:nth-child(1) { width: 8%; }
        .invoice-report-table th:nth-child(2),
        .invoice-report-table td:nth-child(2) { width: 12%; }
        .invoice-report-table th:nth-child(3),
        .invoice-report-table td:nth-child(3) { width: 30%; }
        .invoice-report-table th:nth-child(4),
        .invoice-report-table td:nth-child(4) { width: 10%; }
        .invoice-report-table th:nth-child(5),
        .invoice-report-table td:nth-child(5) { width: 7%; }
        .invoice-report-table th:nth-child(6),
        .invoice-report-table td:nth-child(6),
        .invoice-report-table th:nth-child(7),
        .invoice-report-table td:nth-child(7) { width: 16.5%; }

        .breakdown-table th:nth-child(1),
        .breakdown-table td:nth-child(1) { width: 34%; }
        .breakdown-table th:nth-child(2),
        .breakdown-table td:nth-child(2),
        .breakdown-table th:nth-child(3),
        .breakdown-table td:nth-child(3) { width: 8%; }
        .breakdown-table th:nth-child(4),
        .breakdown-table td:nth-child(4),
        .breakdown-table th:nth-child(5),
        .breakdown-table td:nth-child(5),
        .breakdown-table th:nth-child(6),
        .breakdown-table td:nth-child(6) { width: 14%; }
        .breakdown-table th:nth-child(7),
        .breakdown-table td:nth-child(7) { width: 8%; }

        .tr-even { background: var(--gray-50); }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: 700; }
        .val-positive { color: var(--success); }
        .val-negative { color: var(--danger); }
        .val-primary { color: var(--accent); }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 6.5pt;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-paid { background: #dcfce7; color: #166534; }
        .badge-draft { background: #fef9c3; color: #854d0e; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }

        .total-row td {
            background: var(--gray-100);
            color: var(--primary);
            font-size: 10pt;
            font-weight: 700;
        }

        .empty {
            text-align: center;
            color: var(--gray-500);
            padding: 10px;
        }

        .footer {
            display: none;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="header-left">
                <h1 class="report-title">{{ $title }}</h1>
                <div class="report-date">{{ $dateFrom }} to {{ $dateTo }}</div>
            </div>
            <div class="header-right">
                Generated by NewsFlow System<br>
                {{ now()->format('M d, Y') }}
            </div>
        </div>
    </div>

    <div class="meta-grid">
        <div class="meta-item">
            <div class="label">Report Type</div>
            <div class="font-bold">{{ $title }}</div>
        </div>
        <div class="meta-item">
            <div class="label">Date Range</div>
            <div class="font-bold">{{ $dateFrom }} to {{ $dateTo }}</div>
        </div>
        <div class="meta-item">
            <div class="label">Shop</div>
            <div class="font-bold">{{ $filters['shop'] }}</div>
        </div>
        <div class="meta-item">
            <div class="label">Newspaper</div>
            <div class="font-bold">{{ $filters['newspaper'] }}</div>
        </div>
    </div>

    @if($reportType === 'by-invoice')
        <div class="summary-grid">
            <div class="summary-item">
                <div class="label">Total Invoices</div>
                <div class="value">{{ $report['summary']['total_invoices'] }}</div>
            </div>
            <div class="summary-item">
                <div class="label">Total Bill</div>
                <div class="value">Rs. {{ number_format((float) $report['summary']['total_revenue'], 2) }}</div>
            </div>
            @if($showProfit)
            <div class="summary-item">
                <div class="label">Total Profit 12%</div>
                <div class="value val-primary">Rs. {{ number_format((float) $report['summary']['total_profit'], 2) }}</div>
            </div>
            @endif
        </div>

        <div class="section-title">Invoices</div>
        <table class="invoice-report-table">
            <thead>
                <tr>
                    <th># ID</th>
                    <th>Date</th>
                    <th>Shop</th>
                    <th>Status</th>
                    <th class="text-center">Qty</th>
                    <th class="text-right">Amount</th>
                    @if($showProfit)
                        <th class="text-right">Profit 12%</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($report['invoices'] as $index => $inv)
                    <tr class="{{ $index % 2 === 0 ? '' : 'tr-even' }}">
                        <td class="font-bold">#{{ $inv['id'] }}</td>
                        <td>{{ $inv['invoice_date'] }}</td>
                        <td>{{ $inv['shop_name'] }}</td>
                        <td><span class="badge badge-{{ $inv['status'] }}">{{ $inv['status'] }}</span></td>
                        <td class="text-center">{{ $inv['items_count'] }}</td>
                        <td class="text-right">Rs. {{ number_format((float) $inv['total_amount'], 2) }}</td>
                        @if($showProfit)
                            <td class="text-right font-bold val-primary">Rs. {{ number_format((float) $inv['profit'], 2) }}</td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ $showProfit ? 7 : 6 }}" class="empty">No invoices found for this selected report.</td></tr>
                @endforelse
            </tbody>
            @if(count($report['invoices']) > 0)
                @php
                    $invoiceTotalQuantity = $report['summary']['total_quantity'] ?? collect($report['invoices'])->sum('items_count');
                @endphp
                <tfoot>
                    <tr class="total-row">
                        <td colspan="4">Total</td>
                        <td class="text-center">{{ $invoiceTotalQuantity }}</td>
                        <td class="text-right">Rs. {{ number_format((float) $report['summary']['total_revenue'], 2) }}</td>
                        @if($showProfit)
                            <td class="text-right">Rs. {{ number_format((float) $report['summary']['total_profit'], 2) }}</td>
                        @endif
                    </tr>
                </tfoot>
            @endif
        </table>
    @elseif($reportType === 'by-newspaper')
        <div class="summary-grid">
            <div class="summary-item">
                <div class="label">Newspapers</div>
                <div class="value">{{ $report['summary']['total_newspapers'] }}</div>
            </div>
            <div class="summary-item">
                <div class="label">Quantity</div>
                <div class="value">{{ $report['summary']['total_quantity'] }}</div>
            </div>
            <div class="summary-item">
                <div class="label">Revenue</div>
                <div class="value">Rs. {{ number_format((float) $report['summary']['total_revenue'], 2) }}</div>
            </div>
            @if($showProfit)
            <div class="summary-item">
                <div class="label">Profit</div>
                <div class="value {{ $report['summary']['total_profit'] >= 0 ? 'val-positive' : 'val-negative' }}">
                    Rs. {{ number_format((float) $report['summary']['total_profit'], 2) }}
                </div>
            </div>
            @endif
        </div>

        <div class="section-title">Breakdown by Newspaper</div>
        <table class="breakdown-table">
            <thead>
                <tr>
                    <th>Newspaper</th>
                    <th class="text-center">Invoices</th>
                    <th class="text-center">Qty</th>
                    <th class="text-right">Revenue</th>
                    <th class="text-right">Cost</th>
                    @if($showProfit)
                        <th class="text-right">Profit</th>
                        <th class="text-right">Margin</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($report['by_newspaper'] as $index => $newspaper)
                    <tr class="{{ $index % 2 === 0 ? '' : 'tr-even' }}">
                        <td class="font-bold">{{ $newspaper['newspaper_name'] }}</td>
                        <td class="text-center">{{ $newspaper['invoice_count'] }}</td>
                        <td class="text-center">{{ $newspaper['quantity'] }}</td>
                        <td class="text-right">Rs. {{ number_format((float) $newspaper['total_revenue'], 2) }}</td>
                        <td class="text-right val-negative">Rs. {{ number_format((float) $newspaper['total_cost'], 2) }}</td>
                        @if($showProfit)
                            <td class="text-right font-bold {{ $newspaper['total_profit'] >= 0 ? 'val-positive' : 'val-negative' }}">
                                Rs. {{ number_format((float) $newspaper['total_profit'], 2) }}
                            </td>
                            <td class="text-right">{{ $newspaper['profit_margin'] }}%</td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ $showProfit ? 7 : 5 }}" class="empty">No newspapers found for this selected report.</td></tr>
                @endforelse
            </tbody>
        </table>
    @else
        <div class="summary-grid">
            <div class="summary-item">
                <div class="label">Total Invoices</div>
                <div class="value">{{ $report['summary']['total_invoices'] }}</div>
            </div>
            <div class="summary-item">
                <div class="label">Quantity</div>
                <div class="value">{{ $report['summary']['total_quantity'] }}</div>
            </div>
            <div class="summary-item">
                <div class="label">Revenue</div>
                <div class="value">Rs. {{ number_format((float) $report['summary']['total_revenue'], 2) }}</div>
            </div>
            @if($showProfit)
            <div class="summary-item">
                <div class="label">Profit</div>
                <div class="value {{ $report['summary']['total_profit'] >= 0 ? 'val-positive' : 'val-negative' }}">
                    Rs. {{ number_format((float) $report['summary']['total_profit'], 2) }}
                </div>
            </div>
            @endif
        </div>

        <div class="section-title">Breakdown by Shop</div>
        <table class="breakdown-table">
            <thead>
                <tr>
                    <th>Shop</th>
                    <th class="text-center">Invoices</th>
                    <th class="text-center">Qty</th>
                    <th class="text-right">Revenue</th>
                    <th class="text-right">Cost</th>
                    @if($showProfit)
                        <th class="text-right">Profit</th>
                        <th class="text-right">Margin</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($report['by_shop'] as $index => $shop)
                    <tr class="{{ $index % 2 === 0 ? '' : 'tr-even' }}">
                        <td class="font-bold">{{ $shop['shop_name'] }}</td>
                        <td class="text-center">{{ $shop['invoice_count'] }}</td>
                        <td class="text-center">{{ $shop['quantity'] }}</td>
                        <td class="text-right">Rs. {{ number_format((float) $shop['total_revenue'], 2) }}</td>
                        <td class="text-right val-negative">Rs. {{ number_format((float) $shop['total_cost'], 2) }}</td>
                        @if($showProfit)
                            <td class="text-right font-bold {{ $shop['total_profit'] >= 0 ? 'val-positive' : 'val-negative' }}">
                                Rs. {{ number_format((float) $shop['total_profit'], 2) }}
                            </td>
                            <td class="text-right">{{ $shop['profit_margin'] }}%</td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ $showProfit ? 7 : 5 }}" class="empty">No shops found for this selected report.</td></tr>
                @endforelse
            </tbody>
            @if(count($report['by_shop']) > 0)
                <tfoot>
                    <tr class="total-row">
                        <td>Total</td>
                        <td class="text-center">{{ $report['summary']['total_invoices'] }}</td>
                        <td class="text-center">{{ $report['summary']['total_quantity'] }}</td>
                        <td class="text-right">Rs. {{ number_format((float) $report['summary']['total_revenue'], 2) }}</td>
                        <td class="text-right">Rs. {{ number_format((float) $report['summary']['total_cost'], 2) }}</td>
                        @if($showProfit)
                            <td class="text-right">Rs. {{ number_format((float) $report['summary']['total_profit'], 2) }}</td>
                            <td class="text-right">{{ $report['summary']['profit_margin'] }}%</td>
                        @endif
                    </tr>
                </tfoot>
            @endif
        </table>
    @endif

    <div class="footer">
        This is a computer-generated document.
    </div>
</body>
</html>
