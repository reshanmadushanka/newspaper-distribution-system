<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $invoice->id }}</title>
    <style>
        @page {
            size: A5 landscape;
            margin: 12mm;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9pt;
            color: #1a1a2e;
            margin: 0;
            padding: 0;
        }
        .invoice-box {
            width: 100%;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .header-left h1 {
            font-size: 22pt;
            font-weight: 900;
            color: #2563eb;
            margin: 0;
            letter-spacing: 2px;
        }
        .header-left .invoice-number {
            font-size: 11pt;
            color: #64748b;
            margin-top: 2px;
        }
        .header-right {
            text-align: right;
        }
        .header-right .status {
            background: #2563eb;
            color: white;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 8pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 16px;
            background: #f8fafc;
            padding: 12px 16px;
            border-radius: 8px;
        }
        .details .label {
            font-size: 7pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #94a3b8;
            margin-bottom: 2px;
        }
        .details .value {
            font-size: 11pt;
            font-weight: 600;
            color: #1e293b;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        thead th {
            background: #2563eb;
            color: white;
            padding: 8px 10px;
            font-size: 7.5pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        thead th:first-child {
            border-radius: 6px 0 0 0;
        }
        thead th:last-child {
            border-radius: 0 6px 0 0;
        }
        tbody td {
            padding: 7px 10px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 9pt;
        }
        tbody tr:nth-child(even) {
            background: #f8fafc;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-muted { color: #64748b; }
        .font-bold { font-weight: 700; }
        .summary {
            margin-left: auto;
            width: 220px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
            font-size: 9pt;
        }
        .summary-row.total {
            border-top: 2px solid #2563eb;
            padding-top: 6px;
            margin-top: 4px;
            font-size: 12pt;
            font-weight: 800;
            color: #2563eb;
        }
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 2px solid #e2e8f0;
            text-align: center;
            font-size: 7pt;
            color: #94a3b8;
        }
        .badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 10px;
            font-size: 7pt;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-paid { background: #dcfce7; color: #166534; }
        .badge-draft { background: #fef9c3; color: #854d0e; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="invoice-box">
        <div class="header">
            <div class="header-left">
                <h1>INVOICE</h1>
                <div class="invoice-number">#{{ $invoice->id }}</div>
            </div>
            <div class="header-right">
                <span class="badge badge-{{ $invoice->status }}">{{ $invoice->status }}</span>
            </div>
        </div>

        <div class="details">
            <div>
                <div class="label">Shop</div>
                <div class="value">{{ $invoice->shop->name }}</div>
            </div>
            <div class="text-right">
                <div class="label">Date</div>
                <div class="value">{{ $invoice->invoice_date }}</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Newspaper</th>
                    <th class="text-center">Qty</th>
                    <th class="text-right">Unit Price</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $index => $item)
                <tr>
                    <td class="text-muted">{{ $index + 1 }}</td>
                    <td class="font-bold">{{ $item->newspaper->name }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">Rs. {{ number_format((float)$item->unit_price, 2) }}</td>
                    <td class="text-right font-bold">Rs. {{ number_format((float)$item->total_price, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="summary">
            <div class="summary-row">
                <span class="text-muted">Total Items</span>
                <span>{{ count($invoice->items) }}</span>
            </div>
            <div class="summary-row">
                <span class="text-muted">Total Quantity</span>
                <span>{{ $invoice->items->sum('quantity') }}</span>
            </div>
            <div class="summary-row total">
                <span>Total Amount</span>
                <span>Rs. {{ number_format((float)$invoice->total_amount, 2) }}</span>
            </div>
        </div>

        @if($invoice->notes)
        <div style="margin-top: 16px; padding-top: 10px; border-top: 2px solid #e2e8f0;">
            <div style="font-size: 7pt; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; margin-bottom: 4px;">Notes</div>
            <div style="font-size: 8pt; color: #475569; background: #f8fafc; padding: 8px 12px; border-radius: 6px;">{{ $invoice->notes }}</div>
        </div>
        @endif

        <div class="footer">
            Generated on {{ now()->format('M d, Y') }} by {{ $invoice->creator->name ?? 'System' }}
        </div>
    </div>
</body>
</html>
