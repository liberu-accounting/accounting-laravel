

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .header { text-align: center; margin-bottom: 30px; }
        .invoice-info { margin-bottom: 20px; }
        .customer-info { margin-bottom: 20px; }
        .amounts { margin-top: 20px; }
        .total { font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>INVOICE</h1>
    </div>

    <div class="invoice-info">
        <p><strong>Invoice Number:</strong> {{ $invoice->invoice_number }}</p>
        <p><strong>Date:</strong> {{ $invoice->invoice_date->format('Y-m-d') }}</p>
        <p><strong>Due Date:</strong> {{ $invoice->due_date ? $invoice->due_date->format('Y-m-d') : 'N/A' }}</p>
    </div>

    <div class="customer-info">
        <h3>Bill To:</h3>
        <p>{{ $customer->customer_name }}</p>
        <p>{{ $customer->address ?? '' }}</p>
        <p>{{ $customer->email }}</p>
    </div>

    <div class="amounts">
        <p><strong>Subtotal:</strong> ${{ number_format($invoice->total_amount, 2) }}</p>
        <p><strong>Tax Rate:</strong> {{ $tax_rate->rate ?? 0 }}%</p>
        <p><strong>Tax Amount:</strong> ${{ number_format($invoice->tax_amount, 2) }}</p>
        <p class="total"><strong>Total Amount:</strong> ${{ number_format($invoice->getTotalWithTax(), 2) }}</p>
    </div>

    @if($invoice->notes)
    <div class="notes">
        <h3>Notes:</h3>
        <p>{{ $invoice->notes }}</p>
    </div>
    @endif
</body>
</html>