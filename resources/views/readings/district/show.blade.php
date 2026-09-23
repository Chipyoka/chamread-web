<x-app-layout>
    <div class="p-4">
        <x-slot:breadcrumb>
            <x-breadcrumb :items="[
                ['label' => 'Dashboard', 'url' => route('dashboard.dashboard.index')],
                ['label' => 'Districts', 'url' => route('readings.districts.index')],
                ['label' => $district->name],
            ]"/>
        </x-slot:breadcrumb>

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-4 text-gray-500 space-y-6">

            <!-- {{-- ---------------------------------------------------------- --}}
            {{-- Header --}}
            {{-- ---------------------------------------------------------- --}} -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-500">{{ $district->name }}</h1>
                    <p class="text-sm text-gray-500">
                        District profile, team and performance
                       
                    </p>
                </div>

                <div class="flex items-center space-x-2">
                    <x-micro-button
                        color="amber"
                        icon="file-up"
                        size="md"
                        href="{{ route('readings.districts.export.pending', $district) }}"
                    >
                        Pending
                    </x-micro-button>

                    <x-micro-button
                        color="gray"
                        icon="file-up"
                        size="md"
                        href="{{ route('readings.districts.export.field-issues', $district) }}"
                    >
                        Field Issues
                    </x-micro-button>

                    <x-micro-button
                        color="gray"
                        icon="file-up"
                        size="md"
                        href="{{ route('readings.districts.export.flagged-accounts', $district) }}"
                    >
                        Flagged Accounts
                    </x-micro-button>

                    <x-micro-button
                        color="gray"
                        icon="file-up"
                        size="md"
                        href="{{ route('readings.districts.export.flagged-readings', $district) }}"
                    >
                        Flagged Readings
                    </x-micro-button>

                    <x-micro-button
                        variant="edit"
                        icon="arrow-left"
                        size="md"
                        href="{{ route('readings.districts.index') }}"
                    >
                        Back to Districts
                    </x-micro-button>
                </div>
            </div>

            {{-- ---------------------------------------------------------- --}}
            {{-- No active cycle banner (soft) --}}
            {{-- ---------------------------------------------------------- --}}
            @if(!$currentCycle)
                <div class="bg-amber-50 border border-amber-200 text-amber-700 text-xs rounded-sm px-4 py-3">
                    No active billing cycle.
                </div>
            @endif

            {{-- ---------------------------------------------------------- --}}
            {{-- District Info Card --}}
            {{-- ---------------------------------------------------------- --}}
            <div class="bg-white rounded-md p-6 space-y-4 border border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="px-2 py-1.5 bg-gray-50 rounded-sm">
                        <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Name</h2>
                        <p class="text-gray-500 font-semibold">{{ $district->name }}</p>
                    </div>

                    <div class="px-2 py-1.5 bg-gray-50 rounded-sm">
                        <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Short Code</h2>
                        <p class="text-gray-500 font-semibold">{{ $district->short_code ?? '-' }}</p>
                    </div>

                    <div class="px-2 py-1.5 bg-gray-50 rounded-sm">
                        <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Status</h2>
                        @if($district->status === 'active')
                            <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-700 rounded">
                                Active
                            </span>
                        @else
                            <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-500 rounded">
                                Inactive
                            </span>
                        @endif
                    </div>

                </div>
            </div>

            {{-- ---------------------------------------------------------- --}}
            {{-- Metric Cards --}}
            {{-- ---------------------------------------------------------- --}}
            <div class="bg-white rounded-md p-6 space-y-4 border border-gray-200">
                <div class="my-3 hover-sweep flex items-center justify-between bg-gray-50/70 border-t-8 border-slate-400 rounded-sm px-4 py-4 hover:shadow-md transition-all duration-300 ease-in-out">
                    <div>
                        <h2 class="text-3xl font-bold text-slate-400">{{ $completionRate }}%</h2>
                        <p class="text-gray-500 text-xs uppercase mt-2">Completion Rate</p>
                    </div>
                    <div class="flex items-center justify-center p-4 bg-slate-100/70 rounded-full">
                        <i data-lucide="circle-percent" class="w-7 h-7 text-slate-400"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-md p-6 space-y-4 border border-gray-200">

                <div>
                    <h3 class="text-gray-400 text-xs uppercase my-2">Overview</h3>

            
    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-x-4 gap-y-8">
                        {{-- Accounts Assigned / Total --}}
                        <div class="hover-sweep flex items-center justify-between bg-gray-50/70 border-t-8 border-primary rounded-sm px-4 py-4 hover:shadow-md transition-all duration-300 ease-in-out">
                            <div>
                                <h2 class="text-3xl font-bold text-primary">{{ number_format($accountsTotal) }}</h2>
                                <p class="text-gray-500 text-xs uppercase mt-2">Accounts Total</p>
                            </div>
                            <div class="flex items-center justify-center p-4 bg-blue-100/70 rounded-full">
                                <i data-lucide="list" class="w-7 h-7 text-primary"></i>
                            </div>
                        </div>
    
                        {{-- Read --}}
                        <div class="hover-sweep flex items-center justify-between bg-gray-50/70 border-t-8 border-green-500 rounded-sm px-4 py-4 hover:shadow-md transition-all duration-300 ease-in-out">
                            <div>
                                <h2 class="text-3xl font-bold text-green-500">{{ number_format($accountsRead) }}</h2>
                                <p class="text-gray-500 text-xs uppercase mt-2">Accounts Read</p>
                            </div>
                            <div class="flex items-center justify-center p-4 bg-green-100/70 rounded-full">
                                <i data-lucide="circle-check" class="w-7 h-7 text-green-500"></i>
                            </div>
                        </div>
    
                        {{-- Pending --}}
                        <div class="hover-sweep flex items-center justify-between bg-gray-50/70 border-t-8 border-amber-400 rounded-sm px-4 py-4 hover:shadow-md transition-all duration-300 ease-in-out">
                            <div>
                                <h2 class="text-3xl font-bold text-amber-400">{{ number_format($accountsPending) }}</h2>
                                <p class="text-gray-500 text-xs uppercase mt-2">Pending</p>
                            </div>
                            <div class="flex items-center justify-center p-4 bg-amber-100/70 rounded-full">
                                <i data-lucide="clock" class="w-7 h-7 text-amber-400"></i>
                            </div>
                        </div>
    
                        {{-- Completion Rate --}}
                 
    
                        {{-- Technical Cases --}}
                        <!-- <div class="hover-sweep flex items-center justify-between bg-gray-50/70 border-t-8 border-rose-500 rounded-sm px-4 py-4 hover:shadow-md transition-all duration-300 ease-in-out">
                            <div>
                                <h2 class="text-3xl font-bold text-rose-500">{{ number_format($technicalCases) }}</h2>
                                <p class="text-gray-500 text-xs uppercase mt-2">Technical Cases</p>
                            </div>
                            <div class="flex items-center justify-center p-4 bg-rose-100/70 rounded-full">
                                <i data-lucide="wrench" class="w-7 h-7 text-rose-500"></i>
                            </div>
                        </div> -->
    
                        {{-- Flagged Accounts --}}
                        <div class="hover-sweep flex items-center justify-between bg-gray-50/70 border-t-8 border-orange-500 rounded-sm px-4 py-4 hover:shadow-md transition-all duration-300 ease-in-out">
                            <div>
                                <h2 class="text-3xl font-bold text-orange-500">{{ number_format($flaggedAccounts) }}</h2>
                                <p class="text-gray-500 text-xs uppercase mt-2">Flagged Accounts</p>
                            </div>
                            <div class="flex items-center justify-center p-4 bg-orange-100/70 rounded-full">
                                <i data-lucide="triangle-alert" class="w-7 h-7 text-orange-500"></i>
                            </div>
                        </div>
    
                        {{-- Flagged Readings --}}
                        <div class="hover-sweep flex items-center justify-between bg-gray-50/70 border-t-8 border-fuchsia-500 rounded-sm px-4 py-4 hover:shadow-md transition-all duration-300 ease-in-out">
                            <div>
                                <h2 class="text-3xl font-bold text-fuchsia-500">{{ number_format($flaggedReadings) }}</h2>
                                <p class="text-gray-500 text-xs uppercase mt-2">Flagged Readings</p>
                            </div>
                            <div class="flex items-center justify-center p-4 bg-fuchsia-100/70 rounded-full">
                                <i data-lucide="triangle-alert" class="w-7 h-7 text-fuchsia-500"></i>
                            </div>
                        </div>
    
                        {{-- Field Issues --}}
                        <div class="hover-sweep flex items-center justify-between bg-gray-50/70 border-t-8 border-cyan-500 rounded-sm px-4 py-4 hover:shadow-md transition-all duration-300 ease-in-out">
                            <div>
                                <h2 class="text-3xl font-bold text-cyan-500">{{ number_format($fieldIssues) }}</h2>
                                <p class="text-gray-500 text-xs uppercase mt-2">Field Issues</p>
                            </div>
                            <div class="flex items-center justify-center p-4 bg-cyan-100/70 rounded-full">
                                <i data-lucide="triangle-alert" class="w-7 h-7 text-cyan-500"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ---------------------------------------------------------- --}}
            {{-- Team --}}
            {{-- ---------------------------------------------------------- --}}
            <div class="bg-white border border-gray-200 rounded-md p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-gray-400 text-xs uppercase my-2">Team</h3>
                    <span class="text-xs text-gray-400">{{ $team->count() }} active member{{ $team->count() === 1 ? '' : 's' }}</span>
                </div>

                @if($team->isEmpty())
                    <div class="flex flex-col gap-4 items-center justify-center border border-gray-100 rounded-sm bg-gray-50/70 min-h-40">
                        <i data-lucide="users" class="w-8 h-8 text-gray-300"></i>
                        <p class="text-gray-400 text-xs">No active team members in this district</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    <th class="px-6 py-3">Name</th>
                                    <th class="px-6 py-3">Role</th>
                                    <th class="px-6 py-3">Email</th>
                                    <th class="px-6 py-3">Phone</th>
                                    <th class="px-6 py-3">Assigned</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                @foreach($team as $member)
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-4 text-sm text-gray-500 font-medium">{{ $member->name }}</td>
                                        <td class="px-6 py-4 text-sm">
                                            <span class="px-2 py-1 text-xs font-medium bg-slate-100 text-slate-600 rounded uppercase">
                                                {{ $member->pivot->role }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">{{ $member->email ?? '-' }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-500">{{ $member->phone ?? '-' }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            {{ $member->pivot->assigned_at ? \Carbon\Carbon::parse($member->pivot->assigned_at)->format('Y-m-d') : '-' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

            </div>

        </div>
    </div>
</x-app-layout>