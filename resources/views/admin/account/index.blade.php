<x-app-layout>
    <div class="p-6 space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-medium text-gray-600">Customer Accounts</h1>
                <p class="text-sm text-gray-500">Manage Customer Accounts</p>
            </div>

            <a href="{{ route('admin.accounts.create') }}"
               class="inline-flex items-center px-4 py-2 bg-primary text-white text-sm font-medium rounded-md hover:bg-primary/90 transition">
                <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                Add Account
            </a>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-md p-4 space-y-4 border border-gray-200 overflow-hidden">

            @if($accounts->count() > 0)

                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-6 py-3">Name</th>
                            <th class="px-6 py-3">Email</th>
                            <th class="px-6 py-3">Zone</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Last Login</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="bg-white divide-y divide-gray-100">

                        @foreach($accounts as $account)
                            <tr class="hover:bg-gray-50 transition">

                                <!-- Name -->
                                <td class="px-6 py-4 text-sm text-gray-800 font-medium">
                                    {{ $account->name }}
                                </td>

                                <!-- Email -->
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $account->email }}
                                </td>

                                  <!-- Zone -->
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $account->zone->name ?? '-' }}
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-4 uppercase">
                                    @if($account->status === 'ACTIVE')
                                    
                                        <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-700 rounded">
                                            Active
                                        </span>
                                    @elseif ($account->status === 'SUSPENDED')
                                        <span class="px-2 py-1 text-xs font-medium bg-amber-50 text-amber-600 rounded">
                                            suspended
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-600 rounded">
                                            Inactive
                                        </span>
                                    @endif
                                </td>

                               
                                 <!-- Last Login -->
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ $account->last_login_at 
                                        ? $account->last_login_at->diffForHumans() 
                                        : 'Never' }}
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
                                        Profile
                                    </x-micro-button>


                                    <!-- Edit -->
                                    <x-micro-button
                                        color="purple"
                                        href="{{ route('admin.accounts.readings', $account) }}"
                                        icon="list-todo"
                                        size="sm"
                                    >
                                        Readings
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
                        <p class="text-gray-500 text-sm">No accounts found.</p>
                        <p class="text-gray-500 text-xs">Contact the IT Department to load customer accounts.</p>

                    </div>
                </div>

            @endif

        </div>
    </div>
</x-app-layout>