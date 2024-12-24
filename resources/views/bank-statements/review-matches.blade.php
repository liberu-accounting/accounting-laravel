

<div class="p-4">
    <div class="mb-4">
        <h2 class="text-lg font-medium">Reconciliation Review</h2>
        
        <div class="mt-4 grid grid-cols-2 gap-4">
            <div class="bg-white p-4 rounded-lg shadow">
                <h3 class="font-medium mb-2">Matched Transactions</h3>
                <div class="space-y-2">
                    @foreach($reconciliation['matched_transactions'] as $transaction)
                        <div class="flex justify-between p-2 bg-gray-50 rounded">
                            <span>{{ $transaction->transaction_date->format('Y-m-d') }}</span>
                            <span>{{ money($transaction->amount) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
            
            <div class="bg-white p-4 rounded-lg shadow">
                <h3 class="font-medium mb-2">Unmatched Transactions</h3>
                <div class="space-y-2">
                    @foreach($reconciliation['unmatched_transactions'] as $transaction)
                        <div class="flex justify-between p-2 bg-red-50 rounded">
                            <span>{{ $transaction->transaction_date->format('Y-m-d') }}</span>
                            <span>{{ money($transaction->amount) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        @if($reconciliation['discrepancies']->isNotEmpty())
            <div class="mt-4 bg-yellow-50 p-4 rounded-lg">
                <h3 class="font-medium mb-2">Discrepancies</h3>
                @foreach($reconciliation['discrepancies'] as $discrepancy)
                    <div class="text-sm text-yellow-800">
                        @if($discrepancy['type'] === 'unmatched_transaction')
                            Unmatched transaction on {{ $discrepancy['date']->format('Y-m-d') }}
                            for {{ money($discrepancy['amount']) }}
                        @else
                            Balance mismatch of {{ money($discrepancy['amount']) }}
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>