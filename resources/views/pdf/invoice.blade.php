<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $invoice->id }}</title>
    <style>
        @page {
            size: A5 landscape;
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
            font-size: 9px;
            line-height: 1.4;
            color: #1a1a1a;
            background: #fff;
            padding: 12px 15px;
        }

        /* Modern Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 12px;
            border-bottom: 3px solid #2563eb;
            margin-bottom: 12px;
        }

        .brand {
            flex: 1;
        }

        .brand-name {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -0.5px;
        }

        .brand-tagline {
            font-size: 7px;
            color: #64748b;
            margin-top: 2px;
        }

        .invoice-badge {
            text-align: right;
        }

        .invoice-label {
            font-size: 20px;
            font-weight: 700;
            color: #2563eb;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .invoice-number {
            font-size: 10px;
            color: #64748b;
            margin-top: 2px;
        }

        /* Info Cards */
        .info-grid {
            display: flex;
            gap: 10px;
            margin-bottom: 12px;
        }

        .info-card {
            flex: 1;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 10px;
        }

        .info-label {
            font-size: 7px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }

        .info-value {
            font-size: 10px;
            font-weight: 600;
            color: #0f172a;
        }

        .info-value.small {
            font-size: 9px;
        }

        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 7px;
            font-weight: 600;
            text-transform: uppercase;
            margin-top: 4px;
        }

        .status-draft {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fbbf24;
        }

        .status-paid {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #34d399;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #f87171;
        }

        /* Modern Table */
        .table-container {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f1f5f9;
        }

        th {
            padding: 6px 8px;
            font-size: 7px;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: left;
            border-bottom: 2px solid #e2e8f0;
        }

        td {
            padding: 6px 8px;
            font-size: 9px;
            border-bottom: 1px solid #f1f5f9;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        tbody tr:nth-child(even) {
            background: #fafafa;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: 600; }

        /* Summary Section */
        .summary-section {
            display: flex;
            gap: 10px;
        }

        .notes-area {
            flex: 1;
        }

        .notes-label {
            font-size: 7px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .notes-content {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px;
            font-size: 8px;
            color: #475569;
            line-height: 1.5;
        }

        .totals-area {
            width: 200px;
        }

        .totals-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 10px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
            font-size: 8px;
        }

        .total-row.label {
            color: #64748b;
        }

        .total-row.value {
            font-weight: 600;
            color: #0f172a;
        }

        .total-row.return {
            color: #dc2626;
        }

        .total-row.grand {
            border-top: 2px solid #2563eb;
            margin-top: 6px;
            padding-top: 6px;
            font-size: 11px;
            font-weight: 700;
            color: #0f172a;
        }

        .total-row.grand .value {
            color: #2563eb;
        }

        /* Footer */
        .footer {
            margin-top: 12px;
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 7px;
            color: #94a3b8;
        }

        /* Print Optimization */
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            thead {
                background: #f1f5f9 !important;
            }

            tbody tr:nth-child(even) {
                background: #fafafa !important;
            }

            .info-card,
            .notes-content,
            .totals-box {
                background: #f8fafc !important;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
        <div class="header">
            <div class="brand">
                <div class="brand-name">NEWSPAPER DISTRIBUTION</div>
                <div class="brand-tagline">Reliable Daily Supply Service</div>
            </div>
            <div class="invoice-badge">
                <div class="invoice-label">INVOICE</div>
                <div class="invoice-number">#{{ $invoice->id }}</div>
                <div class="status-badge status-{{ $invoice->invoice_type === 'monthly' ? 'cancelled' : 'paid' }}"
                     style="margin-top: 4px; display: inline-block; text-transform: uppercase; letter-spacing: 0.5px;">
                    {{ strtoupper($invoice->invoice_type ?? 'daily') }}
                </div>
            </div>
        </div>

    <!-- Info Cards -->
        <div class="info-grid">
            <div class="info-card">
                <div class="info-label">Billed To</div>
                <div class="info-value">{{ $invoice->shop->name }}</div>
                @if($invoice->shop->address)
                <div class="info-value small" style="color: #64748b; margin-top: 2px;">{{ $invoice->shop->address }}</div>
                @endif
            </div>
            <div class="info-card">
                <div class="info-label">Invoice Type</div>
                <div class="info-value">{{ strtoupper($invoice->invoice_type ?? 'daily') }}</div>
            </div>
            <div class="info-card">
                <div class="info-label">Invoice Date</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('l, M d, Y') }}</div>
            </div>
            <div class="info-card">
                <div class="info-label">Status</div>
                <div class="info-value">
                    <span class="status-badge status-{{ $invoice->status }}">{{ $invoice->status }}</span>
                </div>
            </div>
        </div>

    <!-- Items Table -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th width="20">#</th>
                    <th>Newspaper</th>
                    <th class="text-center" width="35">Qty</th>
                    @if($invoice->items->sum('return_quantity') > 0)
                    <th class="text-center" width="35">Return</th>
                    @endif
                    <th class="text-right" width="60">Unit Price</th>
                    <th class="text-right" width="65">Amount</th>
                    @if($invoice->items->sum('return_quantity') > 0)
                    <th class="text-right" width="60">Return</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $index => $item)
                <tr>
                    <td style="color: #94a3b8;">{{ $index + 1 }}</td>
                    <td class="font-bold">{{ $item->newspaper->name }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    @if($invoice->items->sum('return_quantity') > 0)
                    <td class="text-center">{{ $item->return_quantity ?? 0 }}</td>
                    @endif
                    <td class="text-right">Rs. {{ number_format((float)$item->unit_price, 2) }}</td>
                    <td class="text-right font-bold">Rs. {{ number_format((float)$item->total_price, 2) }}</td>
                    @if($invoice->items->sum('return_quantity') > 0)
                    <td class="text-right font-bold" style="color: #dc2626;">Rs. {{ number_format((float)($item->return_total_price ?? 0), 2) }}</td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Summary -->
    <div class="summary-section">
        <div class="notes-area">
            @if($invoice->notes)
            <div class="notes-label">Notes</div>
            <div class="notes-content">{{ $invoice->notes }}</div>
            @endif
        </div>
        <div class="totals-area">
            <div class="totals-box">
                <div class="total-row label">
                    <span>Items</span>
                    <span class="value">{{ count($invoice->items) }}</span>
                </div>
                <div class="total-row label">
                    <span>Quantity</span>
                    <span class="value">{{ $invoice->items->sum('quantity') }}</span>
                </div>
                @if($invoice->items->sum('return_quantity') > 0)
                <div class="total-row label" style="margin-top: 4px; padding-top: 4px; border-top: 1px solid #e2e8f0;">
                    <span>Total Amount</span>
                    <span class="value">Rs. {{ number_format((float)$invoice->items->sum('total_price'), 2) }}</span>
                </div>
                <div class="total-row return">
                    <span>Return Total</span>
                    <span class="value">- Rs. {{ number_format((float)$invoice->items->sum('return_total_price'), 2) }}</span>
                </div>
                <div class="total-row grand">
                    <span>NET AMOUNT</span>
                    <span class="value">Rs. {{ number_format((float)$invoice->total_net_amount, 2) }}</span>
                </div>
                @else
                <div class="total-row grand" style="margin-top: 4px; padding-top: 4px; border-top: 2px solid #2563eb;">
                    <span>TOTAL</span>
                    <span class="value">Rs. {{ number_format((float)$invoice->total_amount, 2) }}</span>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        Thank you for your business! • Generated on {{ now()->format('M d, Y h:i A') }}
    </div>
</body>
</html>
