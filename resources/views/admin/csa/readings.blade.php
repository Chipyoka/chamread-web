<x-app-layout>
    <div class="p-6 space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-800">CSA Readings</h1>
                <p class="text-sm text-gray-500">View CSA readings and map</p>
            </div>

            <div class="flex items-center space-x-2">
           
                 <x-micro-button
                    variant="view"
                    href="{{ route('admin.csas.show', $csa) }}"
                    icon="user"
                    size="md"
                >
                    View Profile
                </x-micro-button>

                 <!-- back to list -->
                <x-micro-button
                    variant="edit"
                    href="{{ route('admin.csas.index') }}"
                    icon="arrow-left"
                    size="md"
                >
                    Back to list
                </x-micro-button>
            </div>
        </div>

        <!-- CSA Info Card -->
        <div class="bg-white rounded-md p-6 space-y-4 border border-gray-200">
            <p>We will show map</p>
        </div>

        <!-- CSA Readings -->
        <div class="bg-white border border-gray-200 rounded-md p-6 space-y-4">
            <h3 class="text-lg font-semibold text-gray-800">Readings</h3>

            @if($readings->count() > 0)
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-6 py-3">Account Number</th>
                            <th class="px-6 py-3">Zone</th>
                            <th class="px-6 py-3">DMA</th>
                            <th class="px-6 py-3">Prev</th>
                            <th class="px-6 py-3">Current</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Recorded At</th>
                        </tr>
                    </thead>

                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($readings as $reading)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 text-sm text-gray-800">{{ $reading->account_number ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-800">{{ $reading->zone->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-800">{{ $reading->dma?->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-800">{{ $reading->previous_reading ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-800">{{ $reading->current_reading ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-800 capitalize">{{ $reading->status }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $reading->reading_time?->format('Y-m-d') ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="p-4">
                    {{ $readings->links() }}
                </div>
            @else
                <p class="text-gray-500 text-sm">No readings found for this CSA.</p>
            @endif
        </div>

    </div>
</x-app-layout>