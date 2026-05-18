<x-app-layout>
    <div class="p-6 space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-medium text-gray-600">Meter Readings</h1>
                <p class="text-sm text-gray-500">View Meter Readings from the current billing cycle</p>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-md p-4 space-y-4 border border-gray-200 overflow-hidden">

            @if(count($readings) > 0)

                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-6 py-3">Account #</th>
                            <th class="px-6 py-3">Prev (m3)</th>
                            <th class="px-6 py-3">Current (m3)</th>
                            <th class="px-6 py-3">Consumption (m3)</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Reason</th>
                            <th class="px-6 py-3">Reading Time</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="bg-white divide-y divide-gray-100">

                        @foreach($readings as $reading)
                            <tr class="hover:bg-gray-50 transition">

                                <!-- reading Number -->
                                <td class="px-6 py-4 text-sm text-gray-600 font-medium">
                                    {{ $reading->account_number }}
                                </td>

                                <!-- Meter Number -->
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $reading->previous_reading ?? '0' }}
                                </td>

                                <!-- Name -->
                                <td class="px-6 py-4 text-sm text-gray-600 font-medium">
                                    {{ $reading->current_reading ?? '0' }}
                                </td>

                                <!-- Phone -->
                                <td class="px-6 py-4 text-sm text-gray-600">
                                   @php
                                        $consumption = (($reading->current_reading ?? 0) - ($reading->previous_reading ?? 0));
                                    @endphp

                                    {{ number_format($consumption, 3, '.', '') }}
                                </td>
                                

                                  <!-- Zone -->
                                <td class="px-6 py-4 text-sm text-gray-600 uppercase">
                                    {{ $reading->status ?? '-' }}
                                </td>

                                <td class="px-6 py-4 text-sm text-gray-600 uppercase">
                                    {{ $reading->reason->name ?? '-' }}
                                </td>


                                    <!-- Billing Area -->
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $reading->reading_time->format('Y-m-d H:i:s') ?? '-' }}
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 text-right text-sm space-x-2">

                                    <!-- View -->
                               

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
                        <p class="text-gray-500 text-sm">No readings found.</p>

                    </div>
                </div>

            @endif

        </div>
    </div>
</x-app-layout>