<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Payslip</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1a1a1a; }
        h1 { font-size: 18px; margin-bottom: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { text-align: left; padding: 6px 8px; border-bottom: 1px solid #ddd; }
        td.amount { text-align: right; }
        tr.total td { font-weight: bold; border-top: 2px solid #333; }
    </style>
</head>
<body>
    <h1>Payslip</h1>
    <p>
        <strong>{{ $employee?->name }}</strong><br>
        {{ $employee?->position }}<br>
        Pay period: {{ $payroll->pay_period_start?->format('d M Y') }} &ndash; {{ $payroll->pay_period_end?->format('d M Y') }}<br>
        Payment date: {{ $payroll->payment_date?->format('d M Y') }}
    </p>

    <table>
        <tr>
            <th>Earnings</th>
            <th class="amount">Amount</th>
        </tr>
        <tr>
            <td>Base salary</td>
            <td class="amount">{{ number_format((float) $payroll->base_salary, 2) }}</td>
        </tr>
        <tr>
            <td>Overtime ({{ number_format((float) $payroll->overtime_hours, 2) }} hrs @ {{ number_format((float) $payroll->overtime_rate, 2) }})</td>
            <td class="amount">{{ number_format((float) $payroll->overtime_hours * (float) $payroll->overtime_rate, 2) }}</td>
        </tr>
        <tr class="total">
            <td>Gross pay</td>
            <td class="amount">{{ number_format($gross, 2) }}</td>
        </tr>
    </table>

    <table>
        <tr>
            <th>Deductions</th>
            <th class="amount">Amount</th>
        </tr>
        <tr>
            <td>Tax</td>
            <td class="amount">{{ number_format((float) $payroll->tax_deductions, 2) }}</td>
        </tr>
        <tr>
            <td>Other deductions</td>
            <td class="amount">{{ number_format((float) $payroll->other_deductions, 2) }}</td>
        </tr>
        <tr class="total">
            <td>Total deductions</td>
            <td class="amount">{{ number_format($totalDeductions, 2) }}</td>
        </tr>
    </table>

    <table>
        <tr class="total">
            <td>Net pay</td>
            <td class="amount">{{ number_format((float) $payroll->net_salary, 2) }}</td>
        </tr>
    </table>
</body>
</html>
