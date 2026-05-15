<x-app-layout>
    <div class="p-6 space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-medium text-gray-600">Assigned accounts: <span class="text-secondary">{{ $csa->name ?? 'CSA'}} </span></h1>
                <p class="text-sm text-gray-500">{{ $csa->zone?->name ?? "" }} accounts assigned in the current billing cycle</p>
            </div>

           <div class="flex items-center space-x-2">
           
                 <x-micro-button
                    color="purple"
                    href="{{ route('admin.csas.readings', $csa) }}"
                    icon="list-todo"
                    size="md"
                >
                    View Readings
                </x-micro-button>
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

        <!-- Table -->
        <div class="bg-white rounded-md p-4 space-y-4 border border-gray-200 overflow-hidden">

            @if($accounts->count() > 0)

                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-6 py-3">Account #</th>
                            <th class="px-6 py-3">Meter #</th>
                            <th class="px-6 py-3">Customer</th>
                            <th class="px-6 py-3">Phone</th>
                            <th class="px-6 py-3">Billing Area</th>
                            <th class="px-6 py-3">Reading Status</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="bg-white divide-y divide-gray-100">

                        @foreach($accounts as $account)
                            <tr class="hover:bg-gray-50 transition">

                                  <!-- Account Number -->
                                <td class="px-6 py-4 text-sm text-gray-600 font-medium">
                                    {{ $account->account_number }}
                                </td>

                                <!-- Meter Number -->
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $account->meter_number ?? '-' }}
                                </td>

                                <!-- Name -->
                                <td class="px-6 py-4 text-sm text-gray-600 font-medium">
                                    {{ $account->name }}
                                </td>

                                <!-- Phone -->
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $account->phone ?? '-' }}
                                </td>

                                    <!-- Billing Area -->
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $account->billing_area ?? '-' }}
                                </td>

                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <span class="
                                        px-2 py-1 text-xs rounded uppercase
                                        @if($account->read_status === 'READ')
                                            bg-green-100 text-green-700
                                        @else
                                            bg-red-100 text-red-700
                                        @endif
                                    ">
                                        {{ $account->read_status === 'READ' ? 'Read' : 'Not read' }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 text-right text-sm space-x-2">

                                    <!-- View -->
                                    <x-micro-button
                                        href="{{ route('admin.accounts.show', $account) }}"
                                        color="blue"
                                        icon="user"
                                        size="sm"
                                    >
                                        Details
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
                        <i data-lucide="users" class="w-10 h-10 text-gray-300"></i>
                        <p class="text-gray-500 text-sm">No Accounts found.</p>
                    </div>
                </div>

            @endif

        </div>
    </div>
</x-app-layout>