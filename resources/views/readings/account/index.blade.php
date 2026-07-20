<x-app-layout>
    <div class="p-6 space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-medium text-gray-500">Customer Accounts</h1>
                <p class="text-xs text-gray-500">Manage Customer Accounts</p>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-md p-4 space-y-4 border border-gray-200 overflow-hidden">

            @if($accounts->count() > 0)

                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-6 py-3">Account #</th>
                            <th class="px-6 py-3">Meter #</th>
                            <th class="px-6 py-3">Name</th>
                            <th class="px-6 py-3">Phone</th>
                            <th class="px-6 py-3">Zone</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="bg-white divide-y divide-gray-100">

                        @foreach($accounts as $account)
                            <tr class="hover:bg-gray-50 transition">

                                <!-- Account Number -->
                                <td class="px-6 py-3 text-xs text-gray-500 font-medium">
                                    {{ $account->account_number }}
                                </td>

                                <!-- Meter Number -->
                                <td class="px-6 py-3 text-xs text-gray-500">
                                    {{ $account->meter_number ?? '-' }}
                                </td>

                                <!-- Name -->
                                <td class="px-6 py-3 text-xs text-gray-500 font-medium">
                                    {{ $account->customer_name }}
                                </td>

                                <!-- Phone -->
                                <td class="px-6 py-3 text-xs text-gray-500">
                                    {{ $account->phone ?? '-' }}
                                </td>
                                

                                  <!-- Zone -->
                                <td class="px-6 py-3 text-xs text-gray-500">
                                    {{ $account->zone->name ?? '-' }}
                                </td>


                                <!-- Actions -->
                                <td class="px-6 py-3 text-right text-xs space-x-2">

                                    <!-- View -->
                                    <x-micro-button
                                        href="{{ route('readings.accounts.show', $account) }}"
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
                    {{ $accounts->links() }}
                </div>

            @else

                <!-- Empty State -->
                <div class="p-10 text-center">
                    <div class="flex flex-col items-center space-y-3">
                        <i data-lucide="file-text" class="w-10 h-10 text-gray-300"></i>
                        <p class="text-gray-500 text-xs">No accounts found.</p>
                        <p class="text-gray-500 text-xs">Contact the IT Department to load customer accounts.</p>

                    </div>
                </div>

            @endif

        </div>
    </div>
</x-app-layout>