<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>{{ $invoice->number ?: 'Invoice #' . $invoice->id }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
            font-size: 14px;
            line-height: 1.5;
        }

        .header {
            margin-bottom: 32px;
        }

        .title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .muted {
            color: #6b7280;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 24px;
        }

        th,
        td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }

        .summary {
            width: 320px;
            margin-left: auto;
            margin-top: 24px;
        }

        .summary td {
            border: 0;
            padding: 6px 0;
        }

        .total-row td {
            font-weight: bold;
            font-size: 16px;
            padding-top: 12px;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="title">FlipBook Invoice</div>
        <div class="muted">Invoice {{ $invoice->number ?: '#' . $invoice->id }}</div>
        <div class="muted">Paid on
            {{ optional($invoice->paid_at)->format('d M Y') ?: optional($invoice->created_at)->format('d M Y') }}</div>
    </div>

    <table>
        <tbody>
            <tr>
                <td>
                    <strong>Billed To</strong><br>
                    {{ $invoice->user?->name ?? 'Customer' }}<br>
                    {{ $invoice->user?->email }}
                </td>
                <td>
                    <strong>Plan</strong><br>
                    {{ $invoice->subscription?->plan?->name ?? 'Manual plan' }}
                </td>
                <td>
                    <strong>Payment Source</strong><br>
                    {{ strtoupper((string) data_get($invoice->meta, 'gateway', 'manual')) }}
                </td>
            </tr>
        </tbody>
    </table>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th>Billing Period</th>
                <th style="text-align:right;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $invoice->subscription?->plan?->name ?? 'Approved subscription payment' }}</td>
                <td>
                    {{ optional($invoice->period_start)->format('d M Y') ?: '-' }}
                    to
                    {{ optional($invoice->period_end)->format('d M Y') ?: '-' }}
                </td>
                <td style="text-align:right;">{{ strtoupper((string) $invoice->currency) }}
                    {{ number_format($invoice->amount_paid / 100, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="summary">
        <tbody>
            <tr>
                <td class="muted">Subtotal</td>
                <td style="text-align:right;">{{ strtoupper((string) $invoice->currency) }}
                    {{ number_format(($invoice->subtotal ?? $invoice->amount_paid) / 100, 2) }}</td>
            </tr>
            <tr>
                <td class="muted">Tax</td>
                <td style="text-align:right;">{{ strtoupper((string) $invoice->currency) }}
                    {{ number_format(($invoice->tax ?? 0) / 100, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td>Total Paid</td>
                <td style="text-align:right;">{{ strtoupper((string) $invoice->currency) }}
                    {{ number_format($invoice->amount_paid / 100, 2) }}</td>
            </tr>
        </tbody>
    </table>
</body>

</html>
