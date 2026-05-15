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

            @if($readings->count() > 0)

                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-6 py-3">Account #</th>
                            <th class="px-6 py-3">Meter #</th>
                            <th class="px-6 py-3">Name</th>
                            <th class="px-6 py-3">Phone</th>
                            <th class="px-6 py-3">Zone</th>
                            <th class="px-6 py-3">Billing Area</th>
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
                                    {{ $reading->meter_number ?? '-' }}
                                </td>

                                <!-- Name -->
                                <td class="px-6 py-4 text-sm text-gray-600 font-medium">
                                    {{ $reading->name }}
                                </td>

                                <!-- Phone -->
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $reading->phone ?? '-' }}
                                </td>
                                

                                  <!-- Zone -->
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $reading->zone->name ?? '-' }}
                                </td>


                                    <!-- Billing Area -->
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $reading->billing_area ?? '-' }}
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 text-right text-sm space-x-2">

                                    <!-- View -->
                                    <x-micro-button
                                        href="{{ route('admin.readings.show', $reading) }}"
                                        color="blue"
                                        icon="eye"
                                        size="sm"
                                    >
                                        View Details
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
                        <p class="text-gray-500 text-sm">No readings found.</p>

                    </div>
                </div>

            @endif

        </div>
    </div>
</x-app-layout>