<div x-data="reReadForm()" class="max-w-7xl mx-auto sm:px-6 lg:px-4 text-gray-500 space-y-6">

    <!-- Top card row  -->
    <div class=" bg-white border rounded-md border-gray-200  px-6 py-4">
        <div class="text-gray-500 mb-6">
            <p class="text-gray-400 text-xs uppercase my-2">key performance metrics</p>
        </div>

        <!-- METRIC CARDS -->
        <div class=" grid grid-cols-3 gap-x-4 gap-y-8">

            <!-- card total cases -->
            <div class="hover-sweep flex items-center justify-between bg-gray-50/70 border-t-8 border-primary rounded-sm px-4 py-4 cursor-default hover:shadow-md transition-all duration-300 ease-in-out ">
                <div class="">
                    <h2 class="text-3xl font-bold text-primary">{{ $totalTechnicalCases ?? 0 }}</h2>
                    <p class="text-gray-500 text-xs uppercase mt-2">Technical Cases</p>
                </div>
                <div class="flex items-center justify-center p-4 bg-blue-100/70 rounded-full">
                    <i data-lucide="wrench" class="w-7 h-7 text-primary"></i>
                </div>
            </div>

            <!-- card pending -->
            <div class="hover-sweep flex items-center justify-between bg-gray-50/70 border-t-8 border-amber-400 rounded-sm px-4 py-4 cursor-default hover:shadow-md transition-all duration-300 ease-in-out ">
                <div class="">
                    <h2 class="text-3xl font-bold text-amber-400">{{ $pendingResolves ?? 0 }}</h2>
                    <p class="text-gray-500 text-xs uppercase mt-2">Pending Resolve</p>
                </div>
                <div class="flex items-center justify-center p-4 bg-amber-100/70 rounded-full">
                    <i data-lucide="clock-arrow-down" class="w-7 h-7 text-amber-400"></i>
                </div>
            </div>

            <!-- card resolved -->
            <div class="hover-sweep flex items-center justify-between bg-gray-50/70 border-t-8 border-green-400 rounded-sm px-4 py-4 cursor-default hover:shadow-md transition-all duration-300 ease-in-out ">
                <div class="">
                    <h2 class="text-3xl font-bold text-green-400">{{ $resolvedCases ?? 0 }}</h2>
                    <p class="text-gray-500 text-xs uppercase mt-2">Cases Resolved</p>
                </div>
                <div class="flex items-center justify-center p-4 bg-green-100/70 rounded-full">
                    <i data-lucide="check" class="w-7 h-7 text-green-400"></i>
                </div>
            </div>

        </div>
    </div>

    <div class="grid grid-cols-2 gap-x-4 gap-y-8">

        <!-- Technical issues distribution -->
        <div class="min-h-60 bg-white border rounded-md border-gray-200  px-6 py-4">
            <p class="text-gray-400 text-xs uppercase my-2">Re-Reading Overview </p>
             <div>
                    @if($barChartData->isEmpty())
                        <div class="flex flex-col gap-4 items-center justify-center  border border-gray-100 rounded-sm bg-gray-50/70 min-h-60">
                            <i data-lucide="chart-no-axes-column" class="w-8 h-8 text-gray-300"></i>
                            <p class="text-gray-400 text-xs">No data available yet</p>
                        </div>
                    @else
                        @php
                            $labels = $barChartData
                                ->map(fn($item) => $item->issue_name)
                                ->values();

                            $counts = $barChartData
                                ->map(fn($item) => $item->total_cases)
                                ->values();
                        @endphp

                        <x-charts.bar-chart
                            title="Technical Issues Distribution"
                            dataset-label="Technical Issues Distribution"
                            :labels="$labels->toArray()"
                            :dataset="$counts->toArray()"
                        />
                    @endif
                </div>
        </div>

        <!-- Pending issues for resolve -->
        <div class="min-h-60 bg-white border rounded-md border-gray-200  px-6 py-4">
            <p class="text-gray-400 text-xs uppercase my-2">Technical Cases Resolution </p>
            <div>
                @if($piePending < 1 && $pieResolved < 1)
                    <div class="flex flex-col gap-4 items-center justify-center w-full border border-gray-100 rounded-sm bg-gray-50/70 min-h-60">
                        <i data-lucide="chart-no-axes-column" class="w-8 h-8 text-gray-300"></i>
                        <p class="text-gray-400 text-xs">No data available yet</p>
                    </div>

                @else
                    <div class="flex justify-between items-start gap-2">
                        
                        @include('components.charts.reading-donut-chart2', [
                            'l1' => "Resolved",
                            'l2' => "Pending",
                            'c1' => "#4ade80",
                            'c2' => "#fbbf24",
                            'completed' => $pieResolved,
                            'pending' => $piePending
                        ])

                        <div class="w-1/3">
                            <div class="mb-2 bg-white/40 border-gray-200 border rounded-md flex items-center justify-between py-1.5 px-3  text-sm">
                                <p class="text-xxs text-gray-500">Resolved</p>
                                <p class="">{{$pieResolved ?? 0}}</p>
                            </div>
                            <div class=" bg-white/40 border-gray-200 border rounded-md flex items-center justify-between py-1.5 px-3  text-sm">
                                <p class=" text-xxs text-gray-500">Pending</p>
                                <p class="">{{$piePending ?? 0}}</p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

  


</div>

