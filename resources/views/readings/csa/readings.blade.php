<x-app-layout>
    <div class="p-6 space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-600"> Readings for <span class="text-secondary">{{ $csa->name ?? "CSA" }}</span></h1>
                <p class="text-sm text-gray-500">Scroll to the bottom to view map</p>
            </div>

            <div class="flex items-center space-x-2">
           
                 <x-micro-button
                    color="slate"
                    href="{{ route('readings.csas.accounts', $csa) }}"
                    icon="file-text"
                    size="md"
                >
                    View Accounts
                </x-micro-button>

                 <x-micro-button
                    variant="view"
                    href="{{ route('readings.csas.show', $csa) }}"
                    icon="user"
                    size="md"
                >
                    View Profile
                </x-micro-button>

                 <!-- back to list -->
                <x-micro-button
                    variant="edit"
                    href="{{ route('readings.csas.index') }}"
                    icon="arrow-left"
                    size="md"
                >
                    Back to list
                </x-micro-button>
            </div>
        </div>

        <!-- CSA Readings -->
        <div class="bg-white border border-gray-200 rounded-md p-6 space-y-4">
            <h3 class="text-gray-400 text-xs uppercase my-2">Readings</h3>

            @if($readings->count() > 0)
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-6 py-3">Account</th>
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
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $reading->account?->account_number ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $reading->zone->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $reading->dma?->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $reading->previous_reading ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $reading->current_reading ?? '-' }}</td>
                                          <!-- Reading status -->
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
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $reading->reading_time?->format('Y-m-d H:i:s') ?? '-' }}</td>
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
            <!-- CSA Info Card -->
        <div class="bg-white rounded-md p-2 space-y-4 border border-gray-200">
            <x-maps.agent-trail :points="$points" />
        </div>
        @php
            $pointsCount = count($points);
        @endphp

        @if($pointsCount > 0)
            <p class="text-xs text-gray-400 uppercase my-4">Showing mapping for the current billing cycle.</p>
        @else
            <p class="w-fit py-1.5 px-3 bg-amber-50/70 text-xs text-amber-500 uppercase my-4">No mapping data available for this CSA in the current billing cycle.</p>
        @endif

    </div>
</x-app-layout>