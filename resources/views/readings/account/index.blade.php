<x-app-layout>
    <div class="p-6 space-y-6">
        <x-slot:breadcrumb>
            <x-breadcrumb :items="[
                [
                    'label'=>'Readings'
                ],
                [
                    'label'=>'Accounts'
                ]
            ]"/>
        </x-slot:breadcrumb>
        
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-medium text-gray-500">Customer Accounts</h1>
                <p class="text-xs text-gray-500">Manage Customer Accounts</p>
            </div>

            @if(Auth::user()->role === 'ADMIN')
               @if($accounts->total() > 0)
                    <a
                        href="{{ route('readings.accounts.export.excel', request()->query()) }}"
                        class="inline-flex items-center px-3 py-2.5 bg-primary text-white text-xs font-medium rounded-md hover:bg-primary/90 transition"
                    >
                        <i data-lucide="file-up" class="w-4 h-4 mr-2"></i>
                        Export Excel ({{ $accounts->total() }})
                    </a>
                @else
                    <span
                        class="inline-flex items-center px-3 py-2.5 bg-gray-300 text-gray-500 text-xs font-medium rounded-md cursor-not-allowed opacity-60"
                    >
                        <i data-lucide="file-up" class="w-4 h-4 mr-2"></i>
                        Export Excel (0)
                    </span>
                @endif
            @endif
        </div>

        <!-- Table -->
        <div class="bg-white rounded-md p-4 space-y-4 border border-gray-200 overflow-hidden">

            <!-- Filter Section -->
            <form method="GET" action="{{ route('readings.accounts.index') }}" class="flex flex-wrap items-center gap-3 pb-4 border-b border-gray-100">
                <!-- Search by Account Number -->
                <div class="flex items-center space-x-2 flex-1 max-w-xs">
                    <div class="relative flex-1">
                        <input 
                            type="text" 
                            id="search" 
                            name="search"
                            placeholder="Account #..."
                            class="w-full text-xs border-gray-200 focus:ring-primary focus:border-primary text-gray-500 bg-gray-50 px-3 py-1.5 pl-8"
                            value="{{ request('search') }}"
                        >
                        <i data-lucide="search" class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400"></i>
                    </div>
                </div>

                <!-- Zone Filter -->
                <div class="flex items-center space-x-2">
                    <select 
                        id="zone_filter" 
                        name="zone"
                        class="text-xs border-gray-200 w-48 focus:ring-primary focus:border-primary text-gray-500 bg-gray-50 px-3 py-1.5"
                        onchange="this.form.submit()"
                    >
                        <option value="">All Zones</option>
                        @foreach($zones ?? [] as $zone)
                            <option value="{{ $zone->id }}" {{ request('zone') == $zone->id ? 'selected' : '' }}>
                                {{ $zone->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center space-x-2">
                    <select 
                        id="category_filter" 
                        name="category"
                        class="text-xs border-gray-200 w-48 focus:ring-primary focus:border-primary text-gray-500 bg-gray-50 px-3 py-1.5"
                        onchange="this.form.submit()"
                    >
                        <option value="">All Categories</option>
                        <option value="domestic" {{ request('category') == 'domestic'? 'selected' : '' }}>Domestic</option>
                        <option value="commercial" {{ request('category') == 'commercial'? 'selected' : '' }}>Commercial</option>
                    </select>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="text-xs bg-primary hover:opacity-95 text-white px-4 py-1.5 rounded-sm transition-colors">
                    Filter
                </button>

                <!-- Clear Filters -->
                @if(request('zone') || request('search'))
                    <a 
                        href="{{ route('readings.accounts.index') }}"
                        class="text-xs text-gray-400 hover:text-gray-500 transition-colors flex items-center space-x-1"
                    >
                        <i data-lucide="x" class="w-3 h-3"></i>
                        <span>Clear</span>
                    </a>
                @endif

                <!-- Results Count -->
                <div class="ml-auto text-xs text-gray-400">
                    {{ $accounts->total() }} result{{ $accounts->total() !== 1 ? 's' : '' }}
                </div>
            </form>

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

                        @if(request('zone') || request('search'))
                            <a 
                                href="{{ route('readings.accounts.index') }}"
                                class="text-xs text-blue-500 hover:text-blue-700 transition-colors mt-2"
                            >
                                Clear filters to see all accounts
                            </a>
                        @endif
                    </div>
                </div>

            @endif

        </div>
    </div>
</x-app-layout>