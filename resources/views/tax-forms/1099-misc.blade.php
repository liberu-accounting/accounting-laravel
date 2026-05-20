

<!DOCTYPE html>
<html>
<head>
    <title>Form 1099-MISC</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .form-header { text-align: center; margin-bottom: 20px; }
        .form-section { margin-bottom: 15px; }
        .field { margin: 5px 0; }
    </style>
</head>
<body>
    <div class="form-header">
        <h1>Form 1099-MISC</h1>
        <h2>Miscellaneous Income</h2>
        <p>Tax Year: {{ $form->tax_year }}</p>
    </div>

    <div class="form-section">
        <h3>Payer Information</h3>
        <div class="field">Company Name: {{ config('app.name') }}</div>
        <!-- Add company address and other details -->
    </div>

    <div class="form-section">
        <h3>Recipient Information</h3>
        <div class="field">Name: {{ $customer->customer_name }}</div>
        <!-- Add customer address and tax ID -->
    </div>

    <div class="form-section">
        <h3>Payment Information</h3>
        <div class="field">Total Payments: ${{ number_format($form->total_payments, 2) }}</div>
        <div class="field">Federal Tax Withheld: ${{ number_format($form->total_tax_withheld, 2) }}</div>
    </div>

    <div class="form-section">
        <h3>Tax Summary</h3>
        @foreach($form->tax_summary as $taxName => $tax)
        <div class="field">
            {{ $taxName }} ({{ $tax['rate'] }}%): ${{ number_format($tax['amount'], 2) }}
        </div>
        @endforeach
        <div class="field">Total Tax Withheld: ${{ number_format($form->total_tax_withheld, 2) }}</div>
    </div>
</body>
</html>