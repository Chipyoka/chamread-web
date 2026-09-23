<x-app-layout>
    <div class="p-6 space-y-6" x-data="">
        <x-slot:breadcrumb>
            <x-breadcrumb :items="[
                [
                    'label' => 'Dashboard',
                    'url' => route('dashboard.dashboard.index')
                ],
                [
                    'label' => 'Districts'
                ]
            ]"/>
        </x-slot:breadcrumb>

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-medium text-gray-500">Districts</h1>
                <p class="text-xs text-gray-500">
                    Manage Districts
                </p>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-md p-4 space-y-4 border border-gray-200 overflow-hidden">

            <!-- Filter Section -->
            <form method="GET" action="{{ route('readings.districts.index') }}" class="flex flex-wrap items-center gap-3 pb-4 border-b border-gray-100">
                <!-- Search by Name / Short Code -->
                <div class="flex items-center space-x-2 flex-1 max-w-xs">
                    <div class="relative flex-1">
                        <input
                            type="text"
                            id="search"
                            name="search"
                            placeholder="Search by name..."
                            class="w-full text-xs border-gray-200 focus:ring-primary focus:border-primary text-gray-500 bg-gray-50 px-3 py-1.5 pl-8"
                            value="{{ request('search') }}"
                        >
                        <i data-lucide="search" class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400"></i>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="text-xs bg-primary hover:opacity-95 text-white px-4 py-1.5 rounded-sm transition-colors">
                    Filter
                </button>

                <!-- Clear Filters -->
                @if(request('search'))
                    <a
                        href="{{ route('readings.districts.index') }}"
                        class="text-xs text-gray-400 hover:text-gray-500 transition-colors flex items-center space-x-1"
                    >
                        <i data-lucide="x" class="w-3 h-3"></i>
                        <span>Clear Filters</span>
                    </a>
                @endif

                <!-- Active Filters Count Badge -->
                @php
                    $activeFilters = collect([
                        request('search'),
                    ])->filter()->count();
                @endphp

                @if($activeFilters > 0)
                    <span class="text-[10px] bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">
                        {{ $activeFilters }} filter{{ $activeFilters > 1 ? 's' : '' }} active
                    </span>
                @endif

            </form>

            @if($districts->count() > 0)

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                <th class="px-6 py-3 whitespace-nowrap">Short Code</th>
                                <th class="px-6 py-3 whitespace-nowrap">Name</th>
                                <th class="px-6 py-3 whitespace-nowrap">Team</th>
                                <th class="px-6 py-3 whitespace-nowrap">Accounts</th>
                                <th class="px-6 py-3 whitespace-nowrap">Read</th>
                                <th class="px-6 py-3 whitespace-nowrap">Completion</th>
                                <th class="px-6 py-3 whitespace-nowrap">Status</th>
                                <th class="px-6 py-3 text-right whitespace-nowrap">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="bg-white divide-y divide-gray-100">

                            @foreach($districts as $district)
                                <tr class="hover:bg-gray-50 transition">

                                    <!-- Short Code -->
                                    <td class="px-6 py-3 text-xs text-gray-500 font-medium">
                                        {{ $district->short_code ?? '-' }}
                                    </td>

                                    <!-- Name -->
                                    <td class="px-6 py-3 text-xs text-gray-500 font-medium whitespace-nowrap">
                                        {{ $district->name }}
                                    </td>

                                    <!-- Team Count -->
                                    <td class="px-6 py-3 text-xs text-gray-500 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2 py-0.5 bg-slate-100 text-slate-600 rounded-sm text-[10px] font-medium">
                                            {{ $district->team_count ?? 0 }}
                                        </span>
                                    </td>

                                    <!-- Accounts Total -->
                                    <td class="px-6 py-3 text-xs text-gray-500 whitespace-nowrap">
                                        {{ number_format($district->accounts_total ?? 0) }}
                                    </td>

                                    <!-- Accounts Read -->
                                    <td class="px-6 py-3 text-xs text-gray-500 whitespace-nowrap">
                                        {{ number_format($district->accounts_read ?? 0) }}
                                    </td>

                                    <!-- Completion Rate -->
                                    <td class="px-6 py-3 text-xs text-gray-500 whitespace-nowrap">
                                        @if($currentCycle)
                                            @php $rate = $district->completion_rate ?? 0; @endphp
                                            <span class="font-medium
                                                {{ $rate >= 75 ? 'text-green-600' : ($rate >= 40 ? 'text-amber-500' : 'text-rose-500') }}
                                            ">
                                                {{ $rate }}%
                                            </span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>

                                    <!-- Status -->
                                    <td class="px-6 py-3 text-xs whitespace-nowrap">
                                        @if($district->status === 'active')
                                            <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-700 rounded">
                                                Active
                                            </span>
                                        @else
                                            <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-500 rounded">
                                                Inactive
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-6 py-3 text-right text-xs space-x-2 whitespace-nowrap">
                                        <x-micro-button
                                            href="{{ route('readings.districts.show', $district) }}"
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
                </div>

                <!-- Pagination -->
                <div class="pt-4 border-t border-gray-100">
                    {{ $districts->links() }}
                </div>

            @else

                <!-- Empty State -->
                <div class="p-10 text-center">
                    <div class="flex flex-col items-center space-y-3">
                        <i data-lucide="map-pin" class="w-12 h-12 text-gray-300"></i>
                        <p class="text-gray-500 text-sm font-medium">No districts found</p>
                        <p class="text-gray-400 text-xs">Districts are managed by the IT Department.</p>

                        @if(request('search'))
                            <a
                                href="{{ route('readings.districts.index') }}"
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