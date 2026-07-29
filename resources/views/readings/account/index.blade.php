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
                <p class="text-xs text-gray-500">
                    Manage Customer Accounts
                    @if($accounts->total() > 0)
                        <span class="ml-2 text-gray-400">({{ $accounts->total() }} total)</span>
                    @endif
                </p>
            </div>

            @if(Auth::user()->role === 'ADMIN')
                @if($accounts->total() > 0)
                    <a
                        href="{{ route('readings.accounts.export.excel', request()->query()) }}"
                        class="inline-flex items-center px-3 py-2.5 bg-primary text-white text-xs font-medium rounded-md hover:bg-primary/90 transition"
                    >
                        <i data-lucide="file-up" class="w-4 h-4 mr-2"></i>
                        Export Excel
                        <span class="ml-1.5 px-1.5 py-0.5 bg-white/20 rounded-sm text-[10px]">
                            {{ $accounts->total() }}
                        </span>
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
                            placeholder="Search by Account #..."
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
                        class="text-xs border-gray-200 w-48 focus:ring-primary focus:border-primary text-gray-500 bg-gray-50 px-3 py-1.5 rounded-sm"
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

                <!-- Category Filter -->
                <div class="flex items-center space-x-2">
                    <select 
                        id="category_filter" 
                        name="category"
                        class="text-xs border-gray-200 w-48 focus:ring-primary focus:border-primary text-gray-500 bg-gray-50 px-3 py-1.5 rounded-sm"
                        onchange="this.form.submit()"
                    >
                        <option value="">All Categories</option>
                        <option value="domestic" {{ request('category') == 'domestic' ? 'selected' : '' }}>Domestic</option>
                        <option value="commercial" {{ request('category') == 'commercial' ? 'selected' : '' }}>Commercial</option>
                    </select>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="text-xs bg-primary hover:opacity-95 text-white px-4 py-1.5 rounded-sm transition-colors">
                    Filter
                </button>

                <!-- Clear Filters -->
                @if(request('zone') || request('search') || request('category'))
                    <a 
                        href="{{ route('readings.accounts.index') }}"
                        class="text-xs text-gray-400 hover:text-gray-500 transition-colors flex items-center space-x-1"
                    >
                        <i data-lucide="x" class="w-3 h-3"></i>
                        <span>Clear Filters</span>
                    </a>
                @endif

                <!-- Active Filters Count Badge -->
                @php
                    $activeFilters = collect([
                        request('zone'),
                        request('search'),
                        request('category')
                    ])->filter()->count();
                @endphp
                
                @if($activeFilters > 0)
                    <span class="text-[10px] bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">
                        {{ $activeFilters }} filter{{ $activeFilters > 1 ? 's' : '' }} active
                    </span>
                @endif

                <!-- Results Count -->
                <div class="ml-auto text-xs text-gray-400">
                    Showing {{ $accounts->firstItem() ?? 0 }} - {{ $accounts->lastItem() ?? 0 }} 
                    of {{ $accounts->total() }} result{{ $accounts->total() !== 1 ? 's' : '' }}
                </div>
            </form>

            @if($accounts->count() > 0)

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                <th class="px-6 py-3 whitespace-nowrap">Account #</th>
                                <th class="px-6 py-3 whitespace-nowrap">Meter #</th>
                                <th class="px-6 py-3 whitespace-nowrap">Customer Name</th>
                                <th class="px-6 py-3 whitespace-nowrap">Phone</th>
                                <th class="px-6 py-3 whitespace-nowrap">Zone</th>
                                <th class="px-6 py-3 text-right whitespace-nowrap">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="bg-white divide-y divide-gray-100">

                            @foreach($accounts as $account)
                                <tr class="hover:bg-gray-50 transition">

                                    <!-- Account Number -->
                                    <td class="px-6 py-3 text-xs text-gray-500 font-medium whitespace-nowrap">
                                        {{ $account->account_number }}
                                    </td>

                                    <!-- Meter Number -->
                                    <td class="px-6 py-3 text-xs text-gray-500 whitespace-nowrap">
                                        {{ $account->meter_number ?? '-' }}
                                    </td>

                                    <!-- Customer Name -->
                                    <td class="px-6 py-3 text-xs text-gray-500 font-medium whitespace-nowrap">
                                        {{ $account->customer_name }}
                                    </td>

                                    <!-- Phone -->
                                    <td class="px-6 py-3 text-xs text-gray-500 whitespace-nowrap">
                                        {{ $account->phone ?? '-' }}
                                    </td>
                                    
                                    <!-- Zone -->
                                    <td class="px-6 py-3 text-xs text-gray-500 whitespace-nowrap">
                                        {{ $account->zone->name ?? '-' }}
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-6 py-3 text-right text-xs space-x-2 whitespace-nowrap">
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
                </div>

                <!-- Pagination -->
                <div class="pt-4 border-t border-gray-100">
                    {{ $accounts->links() }}
                </div>

            @else

                <!-- Empty State -->
                <div class="p-10 text-center">
                    <div class="flex flex-col items-center space-y-3">
                        <i data-lucide="file-text" class="w-12 h-12 text-gray-300"></i>
                        <p class="text-gray-500 text-sm font-medium">No accounts found</p>
                        <p class="text-gray-400 text-xs">Contact the IT Department to load customer accounts.</p>

                        @if(request('zone') || request('search') || request('category'))
                            <a 
                                href="{{ route('readings.accounts.index') }}"
                                class="inline-flex items-center text-xs text-blue-500 hover:text-blue-700 transition-colors mt-2"
                            >
                                <i data-lucide="x" class="w-3 h-3 mr-1"></i>
                                Clear all filters
                            </a>
                        @endif
                    </div>
                </div>

            @endif

        </div>
    </div>
</x-app-layout>