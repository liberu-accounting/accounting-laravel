

<div class="space-y-4">
    <div class="flex justify-between">
        <div class="text-sm font-medium text-gray-500">Matched Transactions</div>
        <div class="text-sm font-semibold text-green-600">{{ $matched }}</div>
    </div>
    
    <div class="flex justify-between">
        <div class="text-sm font-medium text-gray-500">Unmatched Transactions</div>
        <div class="text-sm font-semibold text-red-600">{{ $unmatched }}</div>
    </div>

    @if($discrepancies->isNotEmpty())
        <div class="mt-4">
            <div class="text-sm font-medium text-gray-500 mb-2">Discrepancies Found:</div>
            <div class="space-y-2">
                @foreach($discrepancies as $discrepancy)
                    <div class="bg-red-50 p-2 rounded">
                        @if($discrepancy['type'] === 'unmatched_transaction')
                            <div class="text-sm text-red-700">
                                Unmatched: {{ money($discrepancy['amount']) }} on {{ $discrepancy['date']->format('Y-m-d') }}
                            </div>
                        @elseif($discrepancy['type'] === 'balance_mismatch')
                            <div class="text-sm text-red-700">
                                Balance Mismatch: {{ money($discrepancy['amount']) }}
                                <div class="text-xs text-red-600">
                                    Expected: {{ money($discrepancy['expected']) }}
                                    Actual: {{ money($discrepancy['actual']) }}
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if($balance_discrepancy != 0)
        <div class="mt-4 bg-yellow-50 p-3 rounded">
            <div class="text-sm font-medium text-yellow-800">
                Total Balance Discrepancy: {{ money($balance_discrepancy) }}
            </div>
        </div>
    @endif
</div>