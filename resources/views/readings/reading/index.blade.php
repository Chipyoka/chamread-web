<x-app-layout>
    <div class="p-6 space-y-6">

          <x-slot:breadcrumb>
            <x-breadcrumb :items="[
                [
                    'label'=>'Readings'
                ],
                [
                    'label'=>'Meter Readings'
                ]
            ]"/>
        </x-slot:breadcrumb>


        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-medium text-gray-500">Meter Readings</h1>
                <p class="text-xs text-gray-500">View Meter Readings from the current billing cycle</p>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-md p-4 space-y-4 border border-gray-200 overflow-hidden">

            @if(count($readings) > 0)

                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-xs text-gray-500 uppercase tracking-wider">
                            <th class="px-6 py-3">Account #</th>
                            <th class="px-6 py-3">Prev (m3)</th>
                            <th class="px-6 py-3">Current (m3)</th>
                            <th class="px-6 py-3">Consumption (m3)</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Reading Time</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="bg-white divide-y divide-gray-100">

                        @foreach($readings as $reading)
                            <tr class="hover:bg-gray-50 transition">

                                <!-- Account Number -->
                                <td class="px-6 py-4 text-xs text-gray-500 font-medium">
                                    {{ $reading->account->account_number }}
                                </td>

                                <!-- Previous reading -->
                                <td class="px-6 py-4 text-xs text-gray-500">
                                   {{ number_format((float) ($reading->previous_reading ?: 0), 3) }}
                                </td>

                                <!-- current reading -->
                                <td class="px-6 py-4 text-xs text-gray-500 font-medium">
                                    {{ number_format((float) ($reading->current_reading ?: 0), 3) }}
                                </td>

                                <!-- Consumption (hot loading) -->
                                <td class="px-6 py-4 text-xs text-gray-500">
                                   @php
                                        $consumption = (($reading->current_reading ?? 0) - ($reading->previous_reading ?? 0));
                                    @endphp

                                    {{ number_format($consumption, 3, '.', '') }}
                                </td>
                                

                                  <!-- Reading status -->
                                     <td class="px-6 py-4 text-xs text-gray-500">
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


                                    <!-- Reading time -->
                                <td class="px-6 py-4 text-xs text-gray-500">
                                    {{ $reading->reading_time->format('Y-m-d H:i:s') ?? '-' }}
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 text-right text-xs space-x-2">

                                    <!-- View -->
                                    <x-micro-button
                                        href="{{ route('readings.meter-readings.show', $reading) }}"
                                        color="blue"
                                        icon="eye"
                                        size="sm"
                                    >
                                        View
                                    </x-micro-button>

                                </td>
                            </tr>
                        @endforeach

                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="p-4">
                    {{ $readings->links() }}
                </div>

            @else

                <!-- Empty State -->
                <div class="p-10 text-center">
                    <div class="flex flex-col items-center space-y-3">
                        <i data-lucide="list-todo" class="w-10 h-10 text-gray-300"></i>
                        <p class="text-gray-500 text-xs">No readings found.</p>

                    </div>
                </div>

            @endif

        </div>
    </div>
</x-app-layout>