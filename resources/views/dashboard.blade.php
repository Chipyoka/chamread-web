<x-app-layout>
    <div class="py-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-4 text-gray-500 ">
       
            <!-- Top card row for CURRENT BILLING CYCLE + TOP 5 CSAs -->
            <div class="grid grid-cols-2 gap-x-4 gap-y-8 mb-6">
            <div class="min-h-60 bg-white border rounded-md border-gray-200  px-6 py-4 ">
                <p class="text-gray-400 text-xs uppercase mt-2">Current billing cycle</p>
                <div class="flex items-center justify-between my-4">
                <h2 class="text-xl font-semibold">{{ $currentCycle->name }}</h2>


                @if(Auth::user()->role === 'ADMIN' || Auth::user()->role === 'SUPERVISOR')
                    <!-- Edit -->
                    <x-micro-button
                        variant="edit"
                        href="{{ route('admin.csas.index') }}"
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
                        <p class="text-lg font-medium">{{ \Carbon\Carbon::parse($currentCycle->start_date)->format('d M, Y') }}</p>
                        <p class="text-gray-500 text-xs uppercase">Start date</p>
                    </div>
                    <div class="bg-blue-50/70 p-4 rounded-sm border border-blue-100 hover:shadow-sm hover:border-blue-200 transition-all duration-300 ease-in-out">
                        <p class="text-lg font-medium">{{ \Carbon\Carbon::parse($currentCycle->end_date)->format('d M, Y') }}</p>
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
                        <div class="flex items-center justify-center  border border-gray-100 rounded-sm bg-gray-50/70 min-h-60">
                            <p class="text-gray-500 text-xs">No CSA data available.</p>
                        </div>
                    @else
                        <ul class="mt-4 space-y-2">
                            @foreach($topCsas as $csa)
                                <li class="flex items-center justify-between bg-gray-50 rounded-sm px-4 py-2 hover:bg-gray-100 transition-colors duration-200">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-7 h-7 bg-blue-100 text-blue-500 rounded-full flex items-center justify-center">
                                            <i data-lucide="user" class="w-5 h-5"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-700">{{ $csa->name }}</p>
                                            <p class="text-xs text-gray-500">Readings: {{ $csa->readings_count }}</p>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
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
    
                    <!-- card total assigned-->
                    <div class="hover-sweep flex items-center justify-between bg-gray-50/70 border-t-8 border-primary rounded-sm px-4 py-4 cursor-default hover:shadow-md transition-all duration-300 ease-in-out ">
                        <div class="">
                            <h2 class="text-3xl font-bold text-primary">{{ $assignedCsas ?? 0}}</h2>
                            <p class="text-gray-500 text-xs uppercase mt-2">Total Assigned</p>
                        </div>
                        <div class="flex items-center justify-center p-4 bg-blue-100/70 rounded-full">
                            <i data-lucide="list" class="w-7 h-7 text-primary"></i>
                        </div>
                    </div>
    
                    <!-- card total read -->
                    <div class="hover-sweep flex items-center justify-between bg-gray-50/70 border-t-8 border-green-500 rounded-sm px-4 py-4 cursor-default hover:shadow-md transition-all duration-300 ease-in-out ">
                        <div class="">
                            <h2 class="text-3xl font-bold text-green-500">{{ $accountsRead ?? 0 }}</h2>
                            <p class="text-gray-500 text-xs uppercase mt-2">Accounts Read</p>
                        </div>
                        <div class="flex items-center justify-center p-4 bg-green-100/70 rounded-full">
                            <i data-lucide="circle-check" class="w-7 h-7 text-green-500"></i>
                        </div>
                    </div>
    
                    <!-- card total not read-->
                    <div class="hover-sweep flex items-center justify-between bg-gray-50/70 border-t-8 border-red-400 rounded-sm px-4 py-4 cursor-default hover:shadow-md transition-all duration-300 ease-in-out ">
                        <div class="">
                            <h2 class="text-3xl font-bold text-red-400">{{$accountsNotRead ?? 0}}</h2>
                            <p class="text-gray-500 text-xs uppercase mt-2">Accounts Not Read</p>
                        </div>
                        <div class="flex items-center justify-center p-4 bg-red-100/70 rounded-full">
                            <i data-lucide="circle-x" class="w-7 h-7 text-red-400"></i>
                        </div>
                    </div>
    
                    <!-- card abnormal -->
                    <div class="hover-sweep flex items-center justify-between bg-gray-50/70 border-t-8 border-amber-400 rounded-sm px-4 py-4 cursor-default hover:shadow-md transition-all duration-300 ease-in-out ">
                        <div class="">
                            <h2 class="text-3xl font-bold text-amber-400">{{ $accountsAbnormal ?? 0 }}</h2>
                            <p class="text-gray-500 text-xs uppercase mt-2">Abnormal readings</p>
                        </div>
                        <div class="flex items-center justify-center p-4 bg-amber-100/70 rounded-full">
                            <i data-lucide="alert-triangle" class="w-7 h-7 text-amber-400"></i>
                        </div>
                    </div>
    
                    <!-- card zero consumption-->
                    <div class="hover-sweep flex items-center justify-between bg-gray-50/70 border-t-8 border-slate-400 rounded-sm px-4 py-4 cursor-default hover:shadow-md transition-all duration-300 ease-in-out ">
                        <div class="">
                            <h2 class="text-3xl font-bold text-slate-400">{{ $accountsZeroConsumption ?? 0 }}</h2>
                            <p class="text-gray-500 text-xs uppercase mt-2">zero consumption Accounts</p>
                        </div>
                        <div class="flex items-center justify-center p-4 bg-slate-100/70 rounded-full">
                            <i data-lucide="circle-minus" class="w-7 h-7 text-slate-400"></i>
                        </div>
                    </div>
    
                    <!-- card Billing Area Edits-->
                    <div class="hover-sweep flex items-center justify-between bg-gray-50/70 border-t-8 border-primary rounded-sm px-4 py-4 cursor-default hover:shadow-md transition-all duration-300 ease-in-out ">
                        <div class="">
                            <h2 class="text-3xl font-bold text-primary">{{ $billingAreaEdits ?? 0 }}</h2>
                            <p class="text-gray-500 text-xs uppercase mt-2">Billing Area Edits</p>
                        </div>
                        <div class="flex items-center justify-center p-4 bg-blue-100/70 rounded-full">
                            <i data-lucide="file-edit" class="w-7 h-7 text-primary"></i>
                        </div>
                    </div>
    
                    <!-- card GPS Mismatch Alerts-->
                    <div class="hover-sweep flex items-center justify-between bg-gray-50/70 border-t-8 border-orange-400 rounded-sm px-4 py-4 cursor-default hover:shadow-md transition-all duration-300 ease-in-out ">
                        <div class="">
                            <h2 class="text-3xl font-bold text-orange-400">0 000 000</h2>
                            <p class="text-gray-500 text-xs uppercase mt-2">GPS Mismatch Alerts</p>
                        </div>
                        <div class="flex items-center justify-center p-4 bg-orange-100/70 rounded-full">
                            <i data-lucide="map-pin-off" class="w-7 h-7 text-orange-400"></i>
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
