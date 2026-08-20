<x-app-layout>
    <div class="p-4" x-data="">
        <x-slot:breadcrumb>
            <x-breadcrumb :items="[
                [
                    'label'=>'Dashboard',
                    'url'=>route('dashboard.dashboard.index')
                ],
                [
                    'label'=>'Overview'
                ]
            ]"/>
        </x-slot:breadcrumb>

        <!-- content -->
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-4 text-gray-500 space-y-6">
			@if($currentCycle)
				@php
					$endDate = \Carbon\Carbon::parse($currentCycle->end_date);
					$today = \Carbon\Carbon::today();
				@endphp

				@if($today->gte($endDate))
					<div class="mb-4 rounded-md border border-amber-200 bg-amber-50/50 p-4 text-amber-500">
						<div class="font-semibold flex gap-2 items-center animate-pulse">
							<i data-lucide="alert-triangle" class="w-5 h-5 "></i>
							Billing Cycle Warning
						</div>

						@if($today->isSameDay($endDate))
							<p class="mt-1 text-xs">
								This billing cycle ends today. Ensure all outstanding activities are completed before the day ends.
							</p>
						@else
							<p class="mt-1 text-xs">
								<strong>{{ $endDate->diffInDays($today) }}</strong>
								{{ Str::plural('day', $endDate->diffInDays($today)) }}
								past the end date for this billing cycle. Please take the necessary action.
							</p>
						@endif
					</div>
				@endif
			@endif
       
            <!-- warning of technical cases -->
             <div>
                @if($totalTechnicalCases > 0)
                    <div onClick="window.location.href='{{ route('dashboard.technical.index') }}'" class="cursor-pointer mb-4 rounded-md border border-amber-200 bg-amber-50/50 p-4 text-amber-500">
                        <div class="font-semibold flex gap-2 items-center animate-pulse">
                            <i data-lucide="alert-triangle" class="w-5 h-5 "></i>
                            Technical Cases Warning
                        </div>

                        <p class="mt-1 text-xs">
                            <strong>{{ $totalTechnicalCases }}</strong> readings found with technical issues. Click to review.
                        </p>
                    </div>
                @endif
             </div>
            <!-- Total accounts loaded -->
            <div class="hover-sweep flex items-center justify-between bg-white border rounded-md border-gray-200  px-4 py-4 cursor-default">
                <p class="text-gray-500 text-xs font-medium uppercase mt-2">Total Accounts Loaded</p>
                <h2 class="text-3xl font-bold text-primary">{{ $totalAccountsLoaded ?? '00' }}</h2>
            </div>

            <!-- Top card row for CURRENT BILLING CYCLE + TOP 5 CSAs -->
            <div class="grid grid-cols-2 gap-x-4 gap-y-8">
                <div class="min-h-60 bg-white border rounded-md border-gray-200  px-6 py-4 ">
                    <p class="text-gray-400 text-xs uppercase mt-2">Current billing cycle</p>
                    <div class="flex items-center justify-between my-4">
                    <h2 class="text-xl font-semibold">{{ $currentCycle->name ?? 'Not Set' }}</h2>


                    @if(in_array(Auth::user()->role, ['ADMIN', 'IT']) || Auth::user()->role === 'SUPERVISOR')
                        <!-- Edit -->
                        <x-micro-button
                            variant="edit"
                            href="{{ route('management.cycles.index') }}"
                            icon="edit"
                            size="sm"
                        >
                            Update
                        </x-micro-button>
                    @endif
                    
                    </div>
                    <!-- Date cards -->
                    <div class="cursor-default grid grid-cols-2 gap-x-4 gap-y-8 my-2">
                        <div class="bg-blue-50/70 p-4 rounded-sm border border-blue-100 hover:shadow-sm hover:border-blue-200 transition-all duration-300 ease-in-out">
                            <p class="text-lg font-medium">{{ $currentCycle?->start_date ? \Carbon\Carbon::parse($currentCycle->start_date)->format('d M, Y') : '-' }}</p>
                            <p class="text-gray-500 text-xs uppercase">Start date</p>
                        </div>
                        <div class="bg-blue-50/70 p-4 rounded-sm border border-blue-100 hover:shadow-sm hover:border-blue-200 transition-all duration-300 ease-in-out">
                            <p class="text-lg font-medium">{{ $currentCycle?->end_date ? \Carbon\Carbon::parse($currentCycle->end_date)->format('d M, Y') : '-' }}</p>
                            <p class="text-gray-500 text-xs uppercase">End date</p>
                        </div>
                    </div>
                    <!-- card completetion-->
                    <div class="flex items-center justify-between bg-blue-50/40 border-t-8 border-blue-100/70 rounded-sm px-4 py-4 cursor-default hover:shadow-md transition-all duration-300 ease-in-out ">
                        <div class="">
                            <h2 class="text-3xl font-bold text-primary">{{ $completionRate ?? '00'}}%</h2>
                            <p class="text-gray-500 text-xs uppercase mt-2">Completion rate (%)</p>
                        </div>
                        <div class="flex items-center justify-center p-4 bg-blue-50 rounded-full">
                            <i data-lucide="pie-chart" class="w-10 h-10 text-primary"></i>
                        </div>
                    </div>
                </div>

                <!-- Top 5 CSAs -->
                <div class="min-h-60 bg-white border rounded-md border-gray-200  px-6 py-4">
                    <p class="text-gray-400 text-xs uppercase my-2">Reading progress</p>
                    <div>
                        @if($read < 1 && $totalAssignedAccounts < 1)
                            <div class="flex flex-col gap-4 items-center justify-center w-full border border-gray-100 rounded-sm bg-gray-50/70 min-h-60">
                                <i data-lucide="chart-no-axes-column" class="w-8 h-8 text-gray-300"></i>
                                <p class="text-gray-400 text-xs">No data available yet</p>
                            </div>

                        @else
                            <div class="flex justify-between items-start gap-2">
                               
                                <x-charts.reading-donut-chart
                                    :read="$read"
                                    :pending="$totalAssignedAccounts"
                                />

                                <div class="w-1/3">
                                    <div class="mb-2 bg-white/40 border-gray-200 border rounded-md flex items-center justify-between py-1.5 px-3  text-sm">
                                        <p class="text-xxs text-gray-500">Readings</p>
                                        <p class="">{{$read ?? 0}}</p>
                                    </div>
                                    <div class=" bg-white/40 border-gray-200 border rounded-md flex items-center justify-between py-1.5 px-3  text-sm">
                                        <p class=" text-xxs text-gray-500">Pending</p>
                                        <p class="">{{$pending ?? 0}}</p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class=" bg-white border rounded-md border-gray-200  px-6 py-4">
                <div class="text-gray-500 mb-6">
                    <p class="text-gray-400 text-xs uppercase my-2">key performance metrics</p>
                </div>

                <!-- METRIC CARDS -->
                <div class=" grid grid-cols-3 gap-x-4 gap-y-8">
    
                    <!-- card total assigned accounts within current cycle-->
                    <div class="hover-sweep flex items-center justify-between bg-gray-50/70 border-t-8 border-primary rounded-sm px-4 py-4 cursor-default hover:shadow-md transition-all duration-300 ease-in-out ">
                        <div class="">
                            <h2 class="text-3xl font-bold text-primary">{{ $totalAssignedAccounts ?? 0}}</h2>
                            <!-- also calculate assignment percentage -->
                            <p class="text-gray-500 text-xs uppercase mt-2">
                                @php
                                    $assignmentPercentage = $totalAccountsLoaded > 0 ? round(($totalAssignedAccounts / $totalAccountsLoaded) * 100) : 0
                                @endphp

                                <!-- color coded span for percentage -->
                                 <span class="
                                 font-semibold @if ($assignmentPercentage >= 80) text-green-500 @elseif($assignmentPercentage >= 50) text-yellow-500 @else text-red-500 @endif
                                 "
                                 >{{ $assignmentPercentage }}%</span>
                                Accounts Assigned
                            </p>
                        </div>
                        <div class="flex items-center justify-center p-4 bg-blue-100/70 rounded-full">
                            <i data-lucide="list" class="w-5 h-5 text-primary"></i>
                        </div>
                    </div>
    
                    <!-- card total read -->
                    <div class="hover-sweep flex items-center justify-between bg-gray-50/70 border-t-8 border-green-500 rounded-sm px-4 py-4 cursor-default hover:shadow-md transition-all duration-300 ease-in-out ">
                        <div class="">
                            <h2 class="text-3xl font-bold text-green-500">{{ $accountsRead ?? 0 }}</h2>
                            <p class="text-gray-500 text-xs uppercase mt-2">Accounts Read</p>
                        </div>
                        <div class="flex items-center justify-center p-4 bg-green-100/70 rounded-full">
                            <i data-lucide="circle-check" class="w-5 h-5 text-green-500"></i>
                        </div>
                    </div>

                    
                    <!-- card Total CSAs-->
                    <div class="hover-sweep flex items-center justify-between bg-gray-50/70 border-t-8 border-slate-400 rounded-sm px-4 py-4 cursor-default hover:shadow-md transition-all duration-300 ease-in-out ">
                        <div class="">
                            <h2 class="text-3xl font-bold text-slate-400">{{ $totalCsas ?? 0 }}</h2>
                            <p class="text-gray-500 text-xs uppercase mt-2">CSA Accounts</p>
                        </div>
                        <div class="flex items-center justify-center p-4 bg-slate-100/70 rounded-full">
                            <i data-lucide="users" class="w-5 h-5 text-slate-400"></i>
                        </div>
                    </div>
    
                    <!-- card accounts flagged -->
                    <div x-on:click="$dispatch('open-modal', 'accounts-flagged')" class="hover-sweep flex items-center justify-between bg-gray-50/70 border-t-8 border-amber-400 rounded-sm px-4 py-4 cursor-pointer hover:shadow-md transition-all duration-300 ease-in-out ">
                        <div class="">
                            <h2 class="text-3xl font-bold text-amber-400">{{ $flaggedAccounts->count() ?? 0 }}</h2>
                            <p class="text-gray-500 text-xs uppercase mt-2">Flagged Accounts</p>
                        </div>
                        <div class="flex items-center justify-center p-4 bg-amber-100/70 rounded-full">
                            <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-400"></i>
                        </div>
                    </div>

                    <!-- card readings flagged -->
                    <div x-on:click="$dispatch('open-modal', 'readings-flagged')" class="hover-sweep flex items-center justify-between bg-gray-50/70 border-t-8 border-amber-400 rounded-sm px-4 py-4 cursor-pointer hover:shadow-md transition-all duration-300 ease-in-out ">
                        <div class="">
                            <h2 class="text-3xl font-bold text-amber-400">{{ $flaggedReadings->count() ?? 0 }}</h2>
                            <p class="text-gray-500 text-xs uppercase mt-2">Flagged Readings</p>
                        </div>
                        <div class="flex items-center justify-center p-4 bg-amber-100/70 rounded-full">
                            <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-400"></i>
                        </div>
                    </div>

                    <!-- card issues -->
                    <div  
                    onclick="window.location.href='{{ route('readings.issues.index')}}'"
                    class="hover-sweep flex items-center justify-between bg-gray-50/70 border-t-8 border-amber-400 rounded-sm px-4 py-4 cursor-pointer hover:shadow-md transition-all duration-300 ease-in-out ">
                        <div class="">
                            <h2 class="text-3xl font-bold text-amber-400">{{ $reportedIssues ?? 0 }}</h2>
                            <p class="text-gray-500 text-xs uppercase mt-2">Account Issues Reported</p>
                        </div>
                        <div class="flex items-center justify-center p-4 bg-amber-100/70 rounded-full">
                            <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-400"></i>
                        </div>
                    </div>
    
                </div>
            </div>

            <div class="min-h-60 bg-white border rounded-md border-gray-200  px-6 py-4">
                <p class="text-gray-400 text-xs uppercase my-2">Top performers</p>
                <div>
                    @if($topCsas->isEmpty())
                        <div class="flex flex-col gap-4 items-center justify-center  border border-gray-100 rounded-sm bg-gray-50/70 min-h-60">
                            <i data-lucide="chart-no-axes-column" class="w-8 h-8 text-gray-300"></i>
                            <p class="text-gray-400 text-xs">No data available yet</p>
                        </div>
                    @else
                        @php
                            $labels = $topCsas->map(fn($u) => $u->csa_name)->values();
                            $counts = $topCsas->map(fn($u) => $u->total_readings)->values();
                        @endphp
                       <x-charts.bar-chart
                            title="Top 5 Users"
                            dataset-label="Reading Count"
                            :labels="$labels->toArray()"
                            :dataset="$counts->toArray()"
                        />
                    @endif
                </div>
            </div>
            
        </div>
    </div>

    
    <!-- =============================================== -->
     <!-- MODAL SECTION -->
    <!-- =============================================== -->


    <!-- Flagged Accounts modal -->
    <x-modal name="accounts-flagged" max-width="4xl" :closable="false">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-2">Showing Accounts Flagged</h2>
                @if(count($flaggedAccounts) > 0)

                    <div class="max-h-[65dvh] overflow-y-auto thin-scrollbar">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-xxs text-gray-500 uppercase tracking-wider">
                                    <th class="px-6 py-3">Account</th>
                                    <th class="px-6 py-3">Zone</th>
                                    <th class="px-6 py-3">CSA</th>
                                    <th class="px-6 py-3">Reason</th>
                                    <th class="px-6 py-3 text-right">Actions</th>
                                </tr>
                            </thead>

                            <tbody class="bg-white divide-y divide-gray-100">

                                @foreach($flaggedAccounts as $account)
                                    <tr class="hover:bg-gray-50 transition">

                                        <!-- Account Number -->
                                        <td class="px-6 py-4 text-xs text-gray-500 font-medium">
                                            {{ $account->account_number ?? "-" }}
                                        </td>

                                        <!-- zone -->
                                        <td class="px-6 py-4 text-xs text-gray-500">
                                        {{ $account->zone->name ?? "-" }}
                                        </td>


                                        <!-- Reading time -->
                                        <td class="px-6 py-4 text-xs text-gray-500">
                                            {{$account->assignedCsa?->name ?? '-' }}
                                        </td>

                                          <!-- Reason Code (flag) -->
                                        <td class="px-6 py-4 text-xs text-gray-500  gap-2">
                                             @foreach($account->flags as $flag)
                                                <p class="text-gray-500 px-2 py-1.5 rounded-sm uppercase text-xxs" >
                                                    {{ $flag->name }}
                                                </p>
                                            @endforeach
                                        </td>

                                        <!-- Actions -->
                                        <td class="px-6 py-4 text-right text-xs space-x-2 flex items-center justify-end ">
                                       

                                            <!-- View -->
                                            <x-micro-button
                                                href="{{ route('readings.accounts.show', $account) }}"
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

                @else

                    <!-- Empty State -->
                    <div class="p-10 text-center">
                        <div class="flex flex-col items-center space-y-3">
                            <i data-lucide="list-todo" class="w-10 h-10 text-gray-300"></i>
                            <p class="text-gray-500 text-xs">No accounts found.</p>
                        </div>
                    </div>

                @endif
        </div>
    </x-modal>

    <!-- Flagged Readings modal -->
    <x-modal name="readings-flagged" max-width="4xl" :closable="false">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-2">Showing Readings Flagged</h2>
                @if(count($flaggedReadings) > 0)

                    <div class="max-h-[65dvh] overflow-y-auto thin-scrollbar">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-xxs text-gray-500 uppercase tracking-wider">
                                    <th class="px-6 py-3">Account</th>
                                    <th class="px-6 py-3">Time</th>
                                    <th class="px-6 py-3">CSA</th>
                                    <th class="px-6 py-3">Reason</th>
                                    <th class="px-6 py-3 text-right">Actions</th>
                                </tr>
                            </thead>

                            <tbody class="bg-white divide-y divide-gray-100">

                                @foreach($flaggedReadings as $reading)
                                    <tr class="hover:bg-gray-50 transition">

                                        <!-- Account Number -->
                                        <td class="px-6 py-4 text-xs text-gray-500 font-medium">
                                            {{ $reading->account->account_number ?? "-" }}
                                        </td>

                                        <!-- Reading time -->
                                        <td class="px-6 py-4 text-xs text-gray-500">
                                          {{ $reading->reading_time?->format('Y-m-d H:i:s') ?? '-' }}
                                        </td>

                                        <!-- Meter Reader (CSA) -->
                                        <td class="px-6 py-4 text-xs text-gray-500">
                                            {{$reading->csa?->name ?? '-' }}
                                        </td>

                                        <!-- Reason Code (flag) -->
                                        <td class="px-6 py-4 text-xs text-gray-500  gap-2">
                                             @foreach($reading->flags as $flag)
                                                <p class="text-gray-500 px-2 py-1.5 rounded-sm uppercase text-xxs" >
                                                    {{ $flag->name }}
                                                </p>
                                            @endforeach
                                        </td>

                                        <!-- Actions -->
                                        <td class="px-6 py-4 text-right text-xs space-x-2 flex items-center justify-end ">

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

                @else

                    <!-- Empty State -->
                    <div class="p-10 text-center">
                        <div class="flex flex-col items-center space-y-3">
                            <i data-lucide="list-todo" class="w-10 h-10 text-gray-300"></i>
                            <p class="text-gray-500 text-xs">No accounts found.</p>
                        </div>
                    </div>

                @endif
        </div>
    </x-modal>

</x-app-layout>
