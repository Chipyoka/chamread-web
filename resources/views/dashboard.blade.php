<x-app-layout>
    <div class="py-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-4 text-gray-500 ">
       
            <!-- Top card row for CURRENT BILLING CYCLE + TOP 5 CSAs -->
            <div class="grid grid-cols-2 gap-x-4 gap-y-8 mb-6">
            <div class="min-h-60 bg-white border rounded-md border-gray-200  px-6 py-4 ">
                <p class="text-gray-400 text-xs uppercase mt-2">Current billing cycle</p>
                <div class="flex items-center justify-between my-4">
                <h2 class="text-xl font-semibold">{{ $currentCycle->name ?? 'Not Set' }}</h2>


                @if(Auth::user()->role === 'ADMIN' || Auth::user()->role === 'SUPERVISOR')
                    <!-- Edit -->
                    <x-micro-button
                        variant="edit"
                        href="{{ route('readings.csas.index') }}"
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

            <div class=" bg-white border rounded-md border-gray-200  px-6 py-4">
                <div class="text-gray-600 mb-6">
                    <p class="text-gray-400 text-xs uppercase my-2">key performance metrics</p>
                </div>

                <!-- METRIC CARDS -->
                <div class=" grid grid-cols-4 gap-x-4 gap-y-8">
    
                    <!-- card total assigned accounts within current cycle-->
                    <div class="hover-sweep flex items-center justify-between bg-gray-50/70 border-t-8 border-primary rounded-sm px-4 py-4 cursor-default hover:shadow-md transition-all duration-300 ease-in-out ">
                        <div class="">
                            <h2 class="text-3xl font-bold text-primary">{{ $totalAssignedAccounts ?? 0}}</h2>
                            <p class="text-gray-500 text-xs uppercase mt-2">Accounts Assigned</p>
                        </div>
                        <div class="flex items-center justify-center p-4 bg-blue-100/70 rounded-full">
                            <i data-lucide="list" class="w-7 h-7 text-primary"></i>
                        </div>
                    </div>
    
                    <!-- card total read -->
                    <div class="hover-sweep flex items-center justify-between bg-gray-50/70 border-t-8 border-green-500 rounded-sm px-4 py-4 cursor-default hover:shadow-md transition-all duration-300 ease-in-out ">
                        <div class="">
                            <h2 class="text-3xl font-bold text-green-500">{{ $accountsRead ?? 0 }}</h2>
                            <p class="text-gray-500 text-xs uppercase mt-2">Marked Read</p>
                        </div>
                        <div class="flex items-center justify-center p-4 bg-green-100/70 rounded-full">
                            <i data-lucide="circle-check" class="w-7 h-7 text-green-500"></i>
                        </div>
                    </div>
    
                    <!-- card abnormal -->
                    <div class="hover-sweep flex items-center justify-between bg-gray-50/70 border-t-8 border-amber-400 rounded-sm px-4 py-4 cursor-default hover:shadow-md transition-all duration-300 ease-in-out ">
                        <div class="">
                            <h2 class="text-3xl font-bold text-amber-400">{{ $accountsAbnormal ?? 0 }}</h2>
                            <p class="text-gray-500 text-xs uppercase mt-2">Flagged</p>
                        </div>
                        <div class="flex items-center justify-center p-4 bg-amber-100/70 rounded-full">
                            <i data-lucide="alert-triangle" class="w-7 h-7 text-amber-400"></i>
                        </div>
                    </div>
    
                    <!-- card Total CSAs-->
                    <div class="hover-sweep flex items-center justify-between bg-gray-50/70 border-t-8 border-primary rounded-sm px-4 py-4 cursor-default hover:shadow-md transition-all duration-300 ease-in-out ">
                        <div class="">
                            <h2 class="text-3xl font-bold text-primary">{{ $totalCsas ?? 0 }}</h2>
                            <p class="text-gray-500 text-xs uppercase mt-2">Active CSA Accounts</p>
                        </div>
                        <div class="flex items-center justify-center p-4 bg-blue-100/70 rounded-full">
                            <i data-lucide="users" class="w-7 h-7 text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</x-app-layout>
