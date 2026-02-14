<div class="space-y-4">
    <div class="rounded-lg bg-white p-4 shadow">
        <h3 class="text-lg font-semibold mb-4">Reconciliation Summary</h3>
        
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="rounded bg-green-50 p-3">
                <div class="text-sm text-gray-600">Matched Transactions</div>
                <div class="text-2xl font-bold text-green-600">{{ $result['matched_transactions']->count() }}</div>
            </div>
            
            <div class="rounded bg-yellow-50 p-3">
                <div class="text-sm text-gray-600">Unmatched Transactions</div>
                <div class="text-2xl font-bold text-yellow-600">{{ $result['unmatched_transactions']->count() }}</div>
            </div>
        </div>
        
        <div class="rounded bg-gray-50 p-3">
            <div class="text-sm text-gray-600">Balance Discrepancy</div>
            <div class="text-xl font-bold {{ abs($result['balance_discrepancy']) < 0.01 ? 'text-green-600' : 'text-red-600' }}">
                ${{ number_format(abs($result['balance_discrepancy']), 2) }}
            </div>
        </div>
    </div>
    
    @if($result['unmatched_transactions']->count() > 0)
    <div class="rounded-lg bg-white p-4 shadow">
        <h3 class="text-lg font-semibold mb-4">Unmatched Transactions</h3>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-900">Date</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-900">Description</th>
                        <th class="px-4 py-2 text-right text-sm font-semibold text-gray-900">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($result['unmatched_transactions'] as $transaction)
                    <tr>
                        <td class="px-4 py-2 text-sm text-gray-900">
                            {{ $transaction->transaction_date->format('M d, Y') }}
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-900">
                            {{ Str::limit($transaction->transaction_description ?? $transaction->description, 50) }}
                        </td>
                        <td class="px-4 py-2 text-sm text-right {{ $transaction->amount >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            ${{ number_format(abs($transaction->amount), 2) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
    
    @if($result['discrepancies']->count() > 0)
    <div class="rounded-lg bg-white p-4 shadow">
        <h3 class="text-lg font-semibold mb-4">Discrepancies</h3>
        
        <ul class="space-y-2">
            @foreach($result['discrepancies'] as $discrepancy)
            <li class="rounded bg-red-50 p-3">
                <div class="text-sm font-semibold text-red-800">
                    {{ ucfirst(str_replace('_', ' ', $discrepancy['type'])) }}
                </div>
                <div class="text-sm text-red-600">
                    @if(isset($discrepancy['amount']))
                        Amount: ${{ number_format(abs($discrepancy['amount']), 2) }}
                    @endif
                    @if(isset($discrepancy['date']))
                        Date: {{ $discrepancy['date']->format('M d, Y') }}
                    @endif
                </div>
            </li>
            @endforeach
        </ul>
    </div>
    @endif
</div>
