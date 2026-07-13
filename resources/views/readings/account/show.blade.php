
<x-app-layout>
    <div class="p-6 space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-600">Customer Account Details</h1>
                <p class="text-sm text-gray-500">View customer account information </p>
            </div>

            <div class="flex items-center space-x-2">
           
            
                 <!-- export account -->
                <x-micro-button
                    color="purple"
                    href="{{ route('readings.accounts.export', $account) }}"
                    icon="upload"
                    size="md"
                >
                    Export
                </x-micro-button>


                 <!-- back to list -->
                <x-micro-button
                    variant="edit"
                    href="{{ route('readings.accounts.index') }}"
                    icon="arrow-left"
                    size="md"
                >
                    Back to accounts
                </x-micro-button>
            </div>
        </div>

        <!-- account Info Card -->
        <div class="bg-white rounded-md p-6 space-y-4 border border-gray-200">
            <div class="flex items-center justify-end mb-2">
                <h2 class="text-lg font-medium text-gray-600">Assigned to <span class="text-secondary">
                    <a href="{{ route('readings.csas.show', $assignedCsa) }}" class="hover:underline">
                        {{ $assignedCsa ? $assignedCsa->name : 'N/A' }}
                    </a>
                </span></h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="px-2 py-1.5 bg-gray-50 rounded-sm">
                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Account #</h2>
                    <p class="text-gray-600 font-semibold">{{ $account->account_number ?? 'N/A' }}</p>
                </div>

                <div class="px-2 py-1.5 bg-gray-50 rounded-sm">
                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Meter</h2>
                    <p class="text-gray-600 font-semibold">{{ $account->meter_number ?? 'N/A' }}</p>
                </div>

                <div class="px-2 py-1.5 bg-gray-50 rounded-sm">
                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Customer Name</h2>
                    <p class="text-gray-600 font-semibold">{{ $account->name ?? '-' }}</p>
                </div>

                <div class="px-2 py-1.5 bg-gray-50 rounded-sm">
                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Billing Area</h2>
                    <p class="text-gray-600 font-semibold">{{ $account->billing_area ?? '-' }}</p>
                </div>

                <div class="px-2 py-1.5 bg-gray-50 rounded-sm">
                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Phone</h2>
                    <p class="text-gray-600 font-semibold">{{ $account->phone ?? '-' }}</p>
                </div>

                <div class="px-2 py-1.5 bg-gray-50 rounded-sm">
                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Address</h2>
                    <p class="text-gray-600 font-semibold">{{ $account->address ?? '-' }}</p>
                </div>

                <div class="px-2 py-1.5 bg-gray-50 rounded-sm">
                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Zone</h2>
                    <p class="text-gray-600 font-semibold">{{ $account->zone?->name ?? '-' }}</p>
                </div>

                <div class="px-2 py-1.5 bg-gray-50 rounded-sm">
                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">DMA</h2>
                    <p class="text-gray-600 font-semibold">{{ $account->dma?->name ?? '-' }}</p>
                </div>

                <div class="px-2 py-1.5 bg-gray-50 rounded-sm">
                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Created On</h2>
                    <p class="text-gray-600 font-semibold">
                        {{ $account->created_at ? $account->created_at->format('d M, Y') : 'N/A' }}
                    </p>
                </div>

             
            </div>
        </div>

    
        <!-- Consumption Trend chart -->
        <div class="bg-white rounded-md p-6 space-y-4 border border-gray-200">
            <h3 class="text-gray-400 text-xs uppercase my-2">Consumption Trend (Last 6 months)</h3>
            @if(count($chartData) > 0)
               <x-charts.consumption-trend :chartData="$chartData" />
            @else
                <div class="flex flex-col gap-4 items-center justify-center  border border-gray-100 rounded-sm bg-gray-50/70 min-h-60">
                    <i data-lucide="chart-no-axes-column" class="w-8 h-8 text-gray-300"></i>
                    <p class="text-gray-400 text-xs">No data available yet</p>
                </div>
            @endif
        </div>

         <!-- Past readings -->
        <div class="bg-white border border-gray-200 rounded-md p-6 space-y-4">
            <h3 class="text-gray-400 text-xs uppercase my-2">Readings History (Last 6 months)</h3>

            @if($readings->count() > 0)
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-6 py-3">Zone</th>
                            <th class="px-6 py-3">Billing Cycle</th>
                            <th class="px-6 py-3">Prev Reading</th>
                            <th class="px-6 py-3">Current Reading</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Reading Time</th>
                        </tr>
                    </thead>

                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($readings as $reading)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $reading->zone->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $reading->billingCycle->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ number_format((float) ($reading->previous_reading ?: 0), 3) }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ number_format((float) ($reading->current_reading ?: 0), 3) }}</td>
                                   <td class="px-6 py-4 text-sm text-gray-600">
                                    <span class="
                                        px-2 py-1 text-xs rounded uppercase
                                        @if($reading->status === 'read')
                                            bg-green-100 text-green-700
                                        @else
                                            bg-red-100 text-red-700
                                        @endif
                                    ">
                                        {{ $reading->status === 'read' ? 'Read' : 'Not read' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $reading->reading_time->format('Y-m-d H:i:s') ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

            @else
                <p class="text-gray-500 text-sm">No readings found for this account.</p>
            @endif
        </div>

       
    </div>
</x-app-layout>