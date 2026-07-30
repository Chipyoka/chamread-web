<x-app-layout>
    <div x-data="fieldIssuesManagement()" class="p-6 space-y-6">

        <x-slot:breadcrumb>
            <x-breadcrumb :items="[
                [
                    'label'=>'Readings'
                ],
                [
                    'label'=>'Customer Account Issues'
                ]
            ]"/>
        </x-slot:breadcrumb>

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-medium text-gray-500">Field Issues</h1>
                <p class="text-xs text-gray-500">
                    View and manage customer account issues reported
                </p>
            </div>

            @if(Auth::user()->role === 'ADMIN')
                @if($issues->total() > 0)
                    <a
                        href="{{ route('readings.issues.export', request()->query()) }}"
                        class="inline-flex items-center px-3 py-2.5 bg-primary text-white text-xs font-medium rounded-md hover:bg-primary/90 transition"
                    >
                        <i data-lucide="file-up" class="w-4 h-4 mr-2"></i>
                        Export Excel
                        <span class="ml-1.5 px-1.5 py-0.5 bg-white/20 rounded-sm text-[10px]">
                            {{ $issues->total() }}
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
            <form method="GET" action="{{ route('readings.issues.index') }}" class="flex flex-wrap items-center gap-3 pb-4 border-b border-gray-100">
                
                <!-- Status Filter -->
                <div class="flex items-center space-x-2">
                    <select 
                        id="status_filter" 
                        name="status"
                        class="text-xs border-gray-200 w-36 focus:ring-primary focus:border-primary text-gray-500 bg-gray-50 px-3 py-1.5 rounded-sm"
                        onchange="this.form.submit()"
                    >
                        <option value="">All Statuses</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
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

                <!-- Search -->
                <div class="flex items-center space-x-2 flex-1 max-w-xs">
                    <div class="relative flex-1">
                        <input 
                            type="text" 
                            id="search" 
                            name="search"
                            placeholder="Search by Account, Name, Meter #..."
                            class="w-full text-xs border-gray-200 focus:ring-primary focus:border-primary text-gray-500 bg-gray-50 px-3 py-1.5 pl-8 rounded-sm"
                            value="{{ request('search') }}"
                        >
                        <i data-lucide="search" class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400"></i>
                    </div>
                </div>

                <!-- Date Range -->
                <div class="flex items-center space-x-2">
                    <input 
                        type="date" 
                        id="from" 
                        name="from"
                        class="text-xs border-gray-200 w-36 focus:ring-primary focus:border-primary text-gray-500 bg-gray-50 px-3 py-1.5 rounded-sm"
                        value="{{ request('from') }}"
                        placeholder="From"
                    >
                    <span class="text-gray-400 text-xs">to</span>
                    <input 
                        type="date" 
                        id="to" 
                        name="to"
                        class="text-xs border-gray-200 w-36 focus:ring-primary focus:border-primary text-gray-500 bg-gray-50 px-3 py-1.5 rounded-sm"
                        value="{{ request('to') }}"
                        placeholder="To"
                    >
                </div>

                <!-- Submit Button -->
                <button type="submit" class="text-xs bg-primary hover:opacity-95 text-white px-4 py-1.5 rounded-sm transition-colors">
                    Filter
                </button>

                <!-- Clear Filters -->
                @if(request('search') || request('status') || request('zone') || request('from') || request('to'))
                    <a 
                        href="{{ route('readings.issues.index') }}"
                        class="text-xs text-gray-400 hover:text-gray-500 transition-colors flex items-center space-x-1"
                    >
                        <i data-lucide="x" class="w-3 h-3"></i>
                        <span>Clear Filters</span>
                    </a>
                @endif

                <!-- Active Filters Count -->
                @php
                    $activeFilters = collect([
                        request('search'),
                        request('status'),
                        request('zone'),
                        request('from'),
                        request('to')
                    ])->filter()->count();
                @endphp
                
            </form>

            @if($issues->count() > 0)

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs text-gray-500 uppercase tracking-wider">
                                <th class="px-6 py-3 whitespace-nowrap">Account #</th>
                                <th class="px-6 py-3 whitespace-nowrap">Zone</th>
                                <th class="px-6 py-3 whitespace-nowrap">Status</th>
                                <th class="px-6 py-3 whitespace-nowrap">Reported By</th>
                                <th class="px-6 py-3 whitespace-nowrap">Reported At</th>
                                <th class="px-6 py-3 text-right whitespace-nowrap">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="bg-white divide-y divide-gray-100">

                            @foreach($issues as $issue)
                                <tr class="hover:bg-gray-50 transition">

                                    <!-- Account Number -->
                                    <td class="px-6 py-3 text-xs text-gray-500 font-medium whitespace-nowrap">
                                        {{ $issue->account_number ?? '-' }}
                                    </td>

                                    <!-- Zone -->
                                    <td class="px-6 py-3 text-xs text-gray-500 whitespace-nowrap">
                                        {{ $issue->zone->name ?? '-' }}
                                    </td>

                                    <!-- Status -->
                                    <td class="px-6 py-3 text-xs text-gray-500 whitespace-nowrap">
                                        <span class="
                                            px-2 py-1 text-xs rounded uppercase
                                            @if($issue->status === 'pending')
                                                bg-yellow-100 text-yellow-700
                                            @elseif($issue->status === 'completed')
                                                bg-green-100 text-green-700
                                            @elseif($issue->status === 'cancelled')
                                                bg-red-100 text-red-700
                                            @endif
                                        ">
                                            {{ $issue->status }}
                                        </span>
                                    </td>

                                    <!-- Reported By -->
                                    <td class="px-6 py-3 text-xs text-gray-500 whitespace-nowrap">
                                        {{ $issue->reporter->name ?? '-' }}
                                    </td>

                                    <!-- Reported At -->
                                    <td class="px-6 py-3 text-xs text-gray-500 whitespace-nowrap">
                                        {{ $issue->created_at->format('Y-m-d H:i') }}
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-6 py-3 text-right text-xs space-x-2 whitespace-nowrap">
                                        <!-- View Button -->
                                        <button 
                                            type="button"
                                            x-on:click="$dispatch('open-modal', 'view-issue'); selectIssueForView(@js(array_merge($issue->toArray(), [
                                                'created_at' => $issue->created_at->format('Y-m-d H:i:s'),
                                                'updated_at' => $issue->updated_at->format('Y-m-d H:i:s'),
                                                'resolved_at' => $issue->resolved_at ? $issue->resolved_at->format('Y-m-d H:i:s') : null,
                                                'reporter_name' => $issue->reporter->name ?? null,
                                                'zone_name' => $issue->zone->name ?? null,
                                                'account_name' => $issue->customer_name ?? null,
                                                'meter_number' => $issue->meter_number ?? null,
                                                'issue_description' => $issue->comment ?? null,
                                                'issue_name' => $issue->issue ?? null,
                                                'phone' => $issue->phone ?? null,
                                            ])))"
                                            class="inline-flex items-center px-2.5 py-2 bg-blue-50 text-blue-600 text-xs rounded hover:bg-blue-100 transition-colors"
                                        >
                                            <i data-lucide="eye" class="w-3.5 h-3.5 mr-1"></i>
                                            View
                                        </button>
                                    </td>
                                </tr>
                            @endforeach

                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="pt-4 border-t border-gray-100">
                    {{ $issues->links() }}
                </div>

            @else

                <!-- Empty State -->
                <div class="p-10 text-center">
                    <div class="flex flex-col items-center space-y-3">
                        <i data-lucide="alert-circle" class="w-12 h-12 text-gray-300"></i>
                        <p class="text-gray-500 text-sm font-medium">No issues found</p>
                        <p class="text-gray-400 text-xs">No customer account issues match your current filters.</p>

                        @if(request('search') || request('status') || request('zone') || request('from') || request('to'))
                            <a 
                                href="{{ route('readings.issues.index') }}"
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

        <!-- 
        ==================================================================
        MODALS
        -->

        <!-- View Issue Modal -->
        <x-modal name="view-issue" max-width="2xl" :closable="false">
            <div class="p-6">
                <div class="flex items-start justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Issue Details</h2>
                </div>

                <template x-if="viewIssue">
                    <div class="mt-6 space-y-6">
                        <!-- Issue Header -->
                        <div class="pb-6 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900" x-text="'Account #' + viewIssue.account_number"></h3>
                                    <div class="flex items-center space-x-2 mt-1">
                                        <span class="text-xs text-gray-500" x-text="viewIssue.account_name || 'No account name'"></span>
                                        <span class="text-gray-300">|</span>
                                        <span class="text-xs text-gray-500" x-text="'Meter: ' + (viewIssue.meter_number || 'N/A')"></span>
                                        <span class="text-gray-300">|</span>
                                        <span class="text-xs text-gray-500" x-text="viewIssue.zone_name || 'No zone'"></span>
                                        <span class="text-gray-300">|</span>
                                        <span class="text-xs text-gray-500" x-text="viewIssue.phone || 'No Phone'"></span>
                                    </div>
                                </div>
                                <div>
                                    <span class="px-3 py-1 text-xs rounded-full uppercase font-medium"
                                        :class="{
                                            'bg-yellow-100 text-yellow-700': viewIssue.status === 'pending',
                                            'bg-green-100 text-green-700': viewIssue.status === 'completed',
                                            'bg-red-100 text-red-700': viewIssue.status === 'cancelled'
                                        }"
                                        x-text="viewIssue.status"
                                    ></span>
                                </div>
                            </div>
                        </div>

                        <!-- Issue Information Grid -->
                        <div class="grid grid-cols-2 gap-6 mt-2 max-h-[46dvh] overflow-y-auto thin-scrollbar">
                            <!-- Name -->
                            <div class="col-span-2 bg-gray-50 px-4 py-2 border border-gray-100">
                                <label class="block text-xxs font-medium text-gray-400 uppercase tracking-wide">Issue</label>
                                <div class="mt-1">
                                    <span class="text-sm text-gray-900" x-text="viewIssue.issue_name || 'No issue name provided'"></span>
                                </div>
                            </div>
                            <!-- Description -->
                            <div class="col-span-2 bg-gray-50 px-4 py-2 border border-gray-100">
                                <label class="block text-xxs font-medium text-gray-400 uppercase tracking-wide">Issue Description | Comment</label>
                                <div class="mt-1">
                                    <span class="text-sm text-gray-900" x-text="viewIssue.issue_description || 'No description provided'"></span>
                                </div>
                            </div>

                            <!-- Reported By -->
                            <div class="bg-gray-50 px-4 py-2 border border-gray-100">
                                <label class="block text-xxs font-medium text-gray-400 uppercase tracking-wide">Reported By</label>
                                <div class="mt-1">
                                    <span class="text-sm text-gray-900" x-text="viewIssue.reporter_name || 'Unknown'"></span>
                                </div>
                            </div>

                            <!-- Reported At -->
                            <div class="bg-gray-50 px-4 py-2 border border-gray-100">
                                <label class="block text-xxs font-medium text-gray-400 uppercase tracking-wide">Reported At</label>
                                <div class="mt-1">
                                    <span class="text-sm text-gray-900" x-text="viewIssue.created_at ? new Date(viewIssue.created_at).toLocaleString() : 'N/A'"></span>
                                </div>
                            </div>

                            <!-- Resolved At (if completed) -->
                            <div class="bg-gray-50 px-4 py-2 border border-gray-100" x-show="viewIssue.status === 'completed'">
                                <label class="block text-xxs font-medium text-gray-400 uppercase tracking-wide">Resolved At</label>
                                <div class="mt-1">
                                    <span class="text-sm text-gray-900" x-text="viewIssue.resolved_at ? new Date(viewIssue.resolved_at).toLocaleString() : 'N/A'"></span>
                                </div>
                            </div>

                            <!-- Last Updated -->
                            <div class="bg-gray-50 px-4 py-2 border border-gray-100" x-show="viewIssue.status === 'completed' || viewIssue.status === 'cancelled'">
                                <label class="block text-xxs font-medium text-gray-400 uppercase tracking-wide">Last Updated</label>
                                <div class="mt-1">
                                    <span class="text-sm text-gray-900" x-text="viewIssue.updated_at ? new Date(viewIssue.updated_at).toLocaleString() : 'N/A'"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="pt-4 border-t border-gray-100 flex justify-between items-center">
                            <div class="flex items-center space-x-3 gap-4">
                                @if(Auth::user()->role === 'ADMIN')
                                    <!-- Mark Complete Button -->
                                    <template x-if="viewIssue.status === 'pending'">
                                        <form method="POST" 
                                            :action="issueStatusUrl(viewIssue.id)" 
                                            class="inline-block"
                                            x-on:submit.prevent="confirmComplete($event, viewIssue.account_number)"
                                        >
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="completed">
                                            <button type="submit" 
                                                class="inline-flex items-center px-3 py-2 rounded-sm text-sm font-medium transition
                                                bg-green-100 text-green-700 hover:bg-green-200">
                                                Mark as Completed
                                            </button>
                                        </form>
                                    </template>

                                    <!-- Cancel Button -->
                                    <template x-if="viewIssue.status === 'pending'">
                                        <form method="POST" 
                                            :action="issueStatusUrl(viewIssue.id)" 
                                            class="inline-block"
                                            x-on:submit.prevent="confirmCancel($event, viewIssue.account_number)"
                                        >
                                            @csrf
                                             @method('PATCH')
                                            <input type="hidden" name="status" value="cancelled">
                                            <button type="submit" 
                                                class="inline-flex items-center px-3 py-2 rounded-sm text-sm font-medium transition
                                                bg-red-100 text-red-700 hover:bg-red-200">
                                                Cancel Issue
                                            </button>
                                        </form>
                                    </template>

                                    <!-- Status Message for resolved issues -->
                                    <template x-if="viewIssue.status === 'completed'">
                                        <span class="text-xs text-green-600 flex items-center space-x-1">
                                            <span>This issue has been resolved</span>
                                        </span>
                                    </template>

                                    <template x-if="viewIssue.status === 'cancelled'">
                                        <span class="text-xs text-red-600 flex items-center space-x-1">
                                            <span>This issue has been cancelled</span>
                                        </span>
                                    </template>
                                @endif
                            </div>

                            <!-- Close Modal Button -->
                            <button type="button" 
                                x-on:click="$dispatch('close-modal', 'view-issue')"
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-sm hover:bg-gray-200 transition">
                                Close
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </x-modal>

    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('fieldIssuesManagement', () => ({
                    // ----- View issue state -----
                    viewIssue: null,

                    // ----- URL template for the update-status route -----
                    // A real (non-empty) dummy ID is passed to route() so Laravel
                    // can successfully generate the URL, then we swap the
                    // placeholder for the real issue ID at runtime.
                    issueStatusUrlTemplate: @js(route('readings.issues.update-status', ['customerAccountIssue' => '__ISSUE_ID__'])),

                    // ----- Build the real action URL for a given issue id -----
                    issueStatusUrl(id) {
                        return this.issueStatusUrlTemplate.replace('__ISSUE_ID__', id);
                    },

                    // ----- Select issue for viewing -----
                    selectIssueForView(issue) {
                        this.viewIssue = issue;
                        // Re-initialize Lucide icons after view update
                        this.$nextTick(() => {
                            if (typeof lucide !== 'undefined') {
                                lucide.createIcons();
                            }
                        });
                    },

                    // ----- Confirm complete -----
                    confirmComplete(event, accountNumber) {
                        if (confirm(`Mark this issue for account #${accountNumber} as completed?`)) {
                            event.target.closest('form').submit();
                        } else {
                            event.preventDefault();
                        }
                    },

                    // ----- Confirm cancel -----
                    confirmCancel(event, accountNumber) {
                        if (confirm(`Cancel this issue for account #${accountNumber}?`)) {
                            event.target.closest('form').submit();
                        } else {
                            event.preventDefault();
                        }
                    }
                }));
            });
        </script>
    @endpush
</x-app-layout>