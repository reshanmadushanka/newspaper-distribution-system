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
        
        :root {
            --primary: #0f172a;
            --accent: #2563eb;
            --success: #059669;
            --danger: #dc2626;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-400: #94a3b8;
            --gray-600: #475569;
            --gray-800: #1e293b;
        }

        body {
            font-family: 'Inter', 'DejaVu Sans', sans-serif;
            font-size: 8.5pt;
            line-height: 1.4;
            color: var(--gray-800);
            margin: 0;
            padding: 0;
            background: #fff;
        }

        .header {
            background: var(--primary);
            color: white;
            padding: 20px 30px;
        }

        .header-table {
            display: table;
            width: 100%;
        }

        .header-cell {
            display: table-cell;
            vertical-align: middle;
        }

        .brand-name {
            font-size: 14pt;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin: 0;
        }

        .invoice-label {
            font-size: 18pt;
            font-weight: 800;
            text-align: right;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .container {
            padding: 20px 30px;
        }

        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .info-block {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .label {
            font-size: 6.5pt;
            font-weight: 700;
            color: var(--gray-400);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .value {
            font-size: 10pt;
            font-weight: 700;
            color: var(--primary);
        }

        /* Table Style */
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 15px;
        }

        th {
            background: var(--gray-100);
            color: var(--gray-600);
            font-size: 7pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 8px 10px;
            text-align: left;
            border-bottom: 2px solid var(--gray-200);
        }

        td {
            padding: 6px 10px;
            border-bottom: 1px solid var(--gray-100);
            vertical-align: middle;
        }

        .tr-even {
            background: var(--gray-50);
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: 700; }

        /* Summary */
        .summary-wrapper {
            display: table;
            width: 100%;
        }

        .notes-cell {
            display: table-cell;
            width: 60%;
            vertical-align: top;
            padding-right: 20px;
        }

        .summary-cell {
            display: table-cell;
            width: 40%;
            vertical-align: top;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-table td {
            padding: 4px 0;
            border: none;
        }

        .total-row td {
            border-top: 2px solid var(--primary);
            padding-top: 8px;
            font-size: 11pt;
            font-weight: 800;
            color: var(--accent);
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 6.5pt;
            font-weight: 700;
            text-transform: uppercase;
            margin-top: 4px;
        }

        .badge-paid { background: #dcfce7; color: #166534; }
        .badge-draft { background: #fef9c3; color: #854d0e; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }

        .footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            padding: 15px 30px;
            border-top: 1px solid var(--gray-100);
            text-align: center;
            font-size: 7pt;
            color: var(--gray-400);
        }

        .notes-box {
            background: var(--gray-50);
            border-left: 3px solid var(--gray-200);
            padding: 8px 12px;
            font-size: 7.5pt;
            color: var(--gray-600);
            border-radius: 0 4px 4px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-table">
            <div class="header-cell">
                <div class="brand-name">NEWSPAPER DISTRIBUTION</div>
                <div style="font-size: 8pt; color: var(--gray-400);">Reliable Daily Supply Service</div>
            </div>
            <div class="header-cell">
                <div class="invoice-label">Invoice</div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="info-section">
            <div class="info-block">
                <div class="label">Billed To</div>
                <div class="value">{{ $invoice->shop->name }}</div>
                <div style="font-size: 7.5pt; color: var(--gray-600); margin-top: 2px;">
                    {{ $invoice->shop->address ?? 'Customer' }}
                </div>
            </div>
            <div class="info-block text-right">
                <div style="display: inline-block; text-align: left; margin-left: 30px;">
                    <div class="label">Invoice Number</div>
                    <div class="value" style="color: var(--accent);">#{{ $invoice->id }}</div>
                </div>
                <div style="display: inline-block; text-align: left; margin-left: 30px;">
                    <div class="label">Issue Date</div>
                    <div class="value">{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('M d, Y') }}</div>
                </div>
                <div>
                    <span class="badge badge-{{ $invoice->status }}">{{ $invoice->status }}</span>
                </div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th width="40">#</th>
                    <th>Description / Newspaper</th>
                    <th class="text-center" width="60">Qty</th>
                    <th class="text-right" width="100">Unit Price</th>
                    <th class="text-right" width="100">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $index => $item)
                <tr class="{{ $index % 2 == 0 ? '' : 'tr-even' }}">
                    <td class="text-center" style="color: var(--gray-400);">{{ $index + 1 }}</td>
                    <td class="font-bold">{{ $item->newspaper->name }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">Rs. {{ number_format((float)$item->unit_price, 2) }}</td>
                    <td class="text-right font-bold">Rs. {{ number_format((float)$item->total_price, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="summary-wrapper">
            <div class="notes-cell">
                @if($invoice->notes)
                <div class="label">Additional Notes</div>
                <div class="notes-box">{{ $invoice->notes }}</div>
                @endif
            </div>
            <div class="summary-cell">
                <table class="summary-table">
                    <tr>
                        <td class="label">Total Items</td>
                        <td class="text-right font-bold">{{ count($invoice->items) }}</td>
                    </tr>
                    <tr>
                        <td class="label">Total Quantity</td>
                        <td class="text-right font-bold">{{ $invoice->items->sum('quantity') }}</td>
                    </tr>
                    <tr class="total-row">
                        <td style="text-transform: uppercase;">Amount Due</td>
                        <td class="text-right">Rs. {{ number_format((float)$invoice->total_amount, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="footer">
        Thank you for your business! • Generated on {{ now()->format('M d, Y h:i A') }} • Powered by Newspaper System
    </div>
</body>
</html>

