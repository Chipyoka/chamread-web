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
                <p class="text-xs text-gray-500">
                    View Meter Readings from the current billing cycle
                </p>
            </div>

            @if(Auth::user()->role === 'ADMIN')
                @if($readings->total() > 0)
                    <a
                        href="{{ route('readings.meter-readings.export.excel', request()->query()) }}"
                        class="inline-flex items-center px-3 py-2.5 bg-primary text-white text-xs font-medium rounded-md hover:bg-primary/90 transition"
                    >
                        <i data-lucide="file-up" class="w-4 h-4 mr-2"></i>
                        Export Excel
                        <span class="ml-1.5 px-1.5 py-0.5 bg-white/20 rounded-sm text-[10px]">
                            {{ $readings->total() }}
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
            <form method="GET" action="{{ route('readings.meter-readings.index') }}" class="flex flex-wrap items-center gap-3 pb-4 border-b border-gray-100">
                
                <!-- Duration Filter -->
                <div class="flex items-center space-x-2">
                    <select 
                        id="duration_filter" 
                        name="duration"
                        class="text-xs border-gray-200 w-36 focus:ring-primary focus:border-primary text-gray-500 bg-gray-50 px-3 py-1.5 rounded-sm"
                        onchange="this.form.submit()"
                    >
                        <option value="">Duration</option>
                        <option value="today" {{ request('duration') == 'today' ? 'selected' : '' }}>Today</option>
                        <option value="this_week" {{ request('duration') == 'this_week' ? 'selected' : '' }}>This Week</option>
                    </select>
                </div>

                <!-- Search by Account Number -->
                <div class="flex items-center space-x-2 flex-1 max-w-xs">
                    <div class="relative flex-1">
                        <input 
                            type="text" 
                            id="search" 
                            name="search"
                            placeholder="Search by Account #..."
                            class="w-full text-xs border-gray-200 focus:ring-primary focus:border-primary text-gray-500 bg-gray-50 px-3 py-1.5 pl-8 rounded-sm"
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

                <!-- District Filter -->
                <div class="flex items-center space-x-2">
                    <select 
                        id="district_filter" 
                        name="district"
                        class="text-xs border-gray-200 w-48 focus:ring-primary focus:border-primary text-gray-500 bg-gray-50 px-3 py-1.5 rounded-sm"
                        onchange="this.form.submit()"
                    >
                        <option value="">All Districts</option>
                        @foreach($districts ?? [] as $district)
                            <option value="{{ $district }}" {{ request('district') == $district ? 'selected' : '' }}>
                                {{ $district }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="text-xs bg-primary hover:opacity-95 text-white px-4 py-1.5 rounded-sm transition-colors">
                    Filter
                </button>

                <!-- Clear Filters -->
                @if(request('duration') || request('search') || request('zone') || request('district'))
                    <a 
                        href="{{ route('readings.meter-readings.index') }}"
                        class="text-xs text-gray-400 hover:text-gray-500 transition-colors flex items-center space-x-1"
                    >
                        <i data-lucide="x" class="w-3 h-3"></i>
                        <span>Clear Filters</span>
                    </a>
                @endif

                <!-- Active Filters Count Badge -->
                @php
                    $activeFilters = collect([
                        request('duration') && request('duration') != 'today' ? request('duration') : null,
                        request('search'),
                        request('zone'),
                        request('district')
                    ])->filter()->count();
                @endphp
                
             

                <!-- Results Count -->
                <div class="ml-auto text-xs text-gray-400"> {{ $readings->total() }} result{{ $readings->total() !== 1 ? 's' : '' }}
                </div>
            </form>

            @if($readings->count() > 0)

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs text-gray-500 uppercase tracking-wider">
                                <th class="px-6 py-3 whitespace-nowrap">Account #</th>
                                <th class="px-6 py-3 whitespace-nowrap">Zone</th>
                                <th class="px-6 py-3 whitespace-nowrap">Prev (m3)</th>
                                <th class="px-6 py-3 whitespace-nowrap">Current (m3)</th>
                                <th class="px-6 py-3 whitespace-nowrap">Consumption (m3)</th>
                                <th class="px-6 py-3 whitespace-nowrap">Status</th>
                                <th class="px-6 py-3 whitespace-nowrap">Reading Time</th>
                                <th class="px-6 py-3 text-right whitespace-nowrap">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="bg-white divide-y divide-gray-100">

                            @foreach($readings as $reading)
                                <tr class="hover:bg-gray-50 transition">

                                    <!-- Account Number -->
                                    <td class="px-6 py-3 text-xs text-gray-500 font-medium whitespace-nowrap">
                                        {{ $reading->account->account_number }}
                                    </td>

                                    <!-- Zone -->
                                    <td class="px-6 py-3 text-xs text-gray-500 whitespace-nowrap">
                                        {{ $reading->account->zone->name ?? '-' }}
                                    </td>

                                    <!-- Previous reading -->
                                    <td class="px-6 py-3 text-xs text-gray-500 whitespace-nowrap">
                                        {{ number_format((float) ($reading->previous_reading ?: 0), 3) }}
                                    </td>

                                    <!-- current reading -->
                                    <td class="px-6 py-3 text-xs text-gray-500 font-medium whitespace-nowrap">
                                        {{ number_format((float) ($reading->current_reading ?: 0), 3) }}
                                    </td>

                                    <!-- Consumption -->
                                    <td class="px-6 py-3 text-xs text-gray-500 whitespace-nowrap">
                                        @php
                                            $consumption = (($reading->current_reading ?? 0) - ($reading->previous_reading ?? 0));
                                        @endphp
                                        {{ number_format($consumption, 3, '.', '') }}
                                    </td>

                                    <!-- Reading status -->
                                    <td class="px-6 py-3 text-xs text-gray-500 whitespace-nowrap">
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
                                    <td class="px-6 py-3 text-xs text-gray-500 whitespace-nowrap">
                                        {{ $reading->reading_time ? $reading->reading_time->format('Y-m-d H:i:s') : '-' }}
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-6 py-3 text-right text-xs space-x-2 whitespace-nowrap">

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
                </div>

                <!-- Pagination -->
                <div class="pt-4 border-t border-gray-100">
                    {{ $readings->links() }}
                </div>

            @else

                <!-- Empty State -->
                <div class="p-10 text-center">
                    <div class="flex flex-col items-center space-y-3">
                        <i data-lucide="list-todo" class="w-12 h-12 text-gray-300"></i>
                        <p class="text-gray-500 text-sm font-medium">No readings found</p>
                        <p class="text-gray-400 text-xs">No meter readings match your current filters.</p>

                        @if(request('duration') || request('search') || request('zone') || request('district'))
                            <a 
                                href="{{ route('readings.meter-readings.index') }}"
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