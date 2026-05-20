<x-filament-panels::page>
    <x-filament::section>
        <h2 class="text-xl font-bold mb-4">Depreciation Schedule for {{ $record->asset_name }}</h2>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3">Year</th>
                        <th scope="col" class="px-6 py-3">Beginning Book Value</th>
                        <th scope="col" class="px-6 py-3">Depreciation Expense</th>
                        <th scope="col" class="px-6 py-3">Accumulated Depreciation</th>
                        <th scope="col" class="px-6 py-3">Ending Book Value</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $schedule = $record->getDepreciationSchedule();
                    @endphp

                    @foreach($schedule as $year => $data)
                        <tr class="bg-white border-b">
                            <td class="px-6 py-4">{{ $year }}</td>
                            <td class="px-6 py-4">{{ number_format($data['beginning_value'], 2) }}</td>
                            <td class="px-6 py-4">{{ number_format($data['depreciation_expense'], 2) }}</td>
                            <td class="px-6 py-4">{{ number_format($data['accumulated_depreciation'], 2) }}</td>
                            <td class="px-6 py-4">{{ number_format($data['ending_value'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>