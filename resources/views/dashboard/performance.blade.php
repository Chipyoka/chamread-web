<x-app-layout>
    <div class="p-4" >
        <x-slot:breadcrumb>
            <x-breadcrumb :items="[
                [
                    'label'=>'Dashboard',
                    'url'=>route('dashboard.dashboard.index')
                ],
                [
                    'label'=>'Performance'
                ]
            ]"/>
        </x-slot:breadcrumb>

           @php
                $data = $performanceData['data'];
                $view = $performanceData['view'];
                $currentCycle = $performanceData['currentCycle'];;
            @endphp

        @if(!$currentCycle)
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-4 text-gray-500 space-y-6">
                <div class="flex flex-col gap-4 items-center justify-center border border-gray-100 rounded-sm bg-white min-h-96">
                    <i data-lucide="chart-no-axes-column" class="w-8 h-8 text-gray-300"></i>
                    <p class="text-gray-400 text-xs">No active cycle available yet</p>
                </div>
            </div>
        @else
            <!-- content -->
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-4 text-gray-500 space-y-6">
                <!-- View Toggle / Filter -->
                <div class="bg-white border rounded-md border-gray-200 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <p class="text-gray-400 text-xs uppercase">Currently showing metrics
                                <span class="font-semibold text-primary uppercase">
                                    by {{ $performanceData['view'] }}
                                </span>
                                for the
                                <span class="font-semibold text-primary uppercase">
                                    {{ $currentCycle?->name ?? '-' }}
                                </span>
                                cycle.
                            </p>
                        </div>
                        <form method="GET" action="{{ route('dashboard.performance.index') }}" id="viewFilterForm">
                            <div class="flex items-center gap-3">
                                <label for="viewFilter" class="text-xs text-gray-500">Change view:</label>
                                <select 
                                    id="viewFilter" 
                                    name="view"
                                    onchange="document.getElementById('viewFilterForm').submit()"
                                    class="rounded-sm border-gray-300 text-sm focus:border-primary focus:ring-primary"
                                >
                                    <option value="district" {{ request('view') == 'district' ? 'selected' : '' }}>District</option>
                                    <option value="csa" {{ request('view') == 'csa' ? 'selected' : '' }}>CSA</option>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- ------------------------------------- -->
                <!-- Top 5 by Readings -->
                <p class="text-gray-400 text-xs uppercase my-2">
                    Readings
                </p>
                <div class="bg-white border rounded-md border-gray-200 px-6 py-4">
                    <p class="text-gray-400 text-xs uppercase my-2">
                        Top 5 {{ ucfirst($view) }}{{ $view === 'district' ? 's' : '' }} by Readings
                    </p>
                    <div>
                        @if($data['topByReadings']->isEmpty())
                            <div class="flex flex-col gap-4 items-center justify-center border border-gray-100 rounded-sm bg-gray-50/70 min-h-60">
                                <i data-lucide="chart-no-axes-column" class="w-8 h-8 text-gray-300"></i>
                                <p class="text-gray-400 text-xs">No data available yet</p>
                            </div>
                        @else
                            @php
                                $labels = $data['topByReadings']->map(function($item) use ($view) {
                                    return $view === 'district' ? $item->district : $item->csa_name;
                                })->values();
                                $counts = $data['topByReadings']->map(function($item) {
                                    return $item->total_readings;
                                })->values();
                            @endphp
                            <x-charts.bar-chart
                                title="Top 5 by Readings"
                                dataset-label="Readings"
                                :labels="$labels->toArray()"
                                :dataset="$counts->toArray()"
                                monochromatic="true"
                                background-color="rgb(25 139 206)"
                            />
                        @endif
                    </div>

                    <!-- Near Completion -->
                    <div class="mt-6">
                        <p class="text-gray-400 text-xs uppercase my-2">
                            Near Completion - {{ ucfirst($view) }}{{ $view === 'district' ? 's' : '' }}
                        </p>
                        <div>
                            @if($data['nearCompletion']->isEmpty())
                                <div class="flex flex-col gap-4 items-center justify-center border border-gray-100 rounded-sm bg-gray-50/70 min-h-60">
                                    <i data-lucide="chart-no-axes-column" class="w-8 h-8 text-gray-300"></i>
                                    <p class="text-gray-400 text-xs">No data available yet</p>
                                </div>
                            @else
                                @php
                                    $labels = $data['nearCompletion']->map(function($item) use ($view) {
                                        return $view === 'district' ? $item->district : $item->csa_name;
                                    })->values();
                                    $percentages = $data['nearCompletion']->map(function($item) {
                                        return $item->completion_rate;
                                    })->values();
                                @endphp
                                <x-charts.bar-chart
                                    :labels="$labels->toArray()"
                                    :dataset="$percentages->toArray()"
                                    dataset-label="Completion Rate"
                                    tooltip-label="Completion Rate (%)"
                                    show-percentage="true"
                                    tooltip-label="Completion Rate (%)"
                                    backgroundColor="rgb(74 222 128)"
                                    monochromatic="true"
                                />
                            @endif
                        </div>
                    </div>

                    <!-- Below Average -->
                    <div class="mt-6">
                        <p class="text-gray-400 text-xs uppercase my-2">
                            Below Average - {{ ucfirst($view) }}{{ $view === 'district' ? 's' : '' }}
                        </p>
                       <x-charts.underperformers-list
                            title="Below Average By Readings"
                            :rows="$performanceData['data']['belowAverage']"
                            :averageReadings="$performanceData['data']['averageReadings']"
                        />
                    </div>
                </div>

                <!-- ------------------------------------- -->
            
                <p class="text-gray-400 text-xs uppercase my-2">
                    Cases and Issues
                </p>
                <!-- Most Technical Issues -->
                <div class="bg-white border rounded-md border-gray-200 px-6 py-4">
                    <p class="text-gray-400 text-xs uppercase my-2">
                        Most Technical Issues
                    </p>
                    <div>
                        @if($data['mostTechnical']->isEmpty())
                            <div class="flex flex-col gap-4 items-center justify-center border border-gray-100 rounded-sm bg-gray-50/70 min-h-60">
                                <i data-lucide="chart-no-axes-column" class="w-8 h-8 text-gray-300"></i>
                                <p class="text-gray-400 text-xs">No data available yet</p>
                            </div>
                        @else
                            @php
                                $labels = $data['mostTechnical']->map(function($item) use ($view) {
                                    return $view === 'district' ? $item->district : $item->csa_name;
                                })->values();
                                $counts = $data['mostTechnical']->map(function($item) {
                                    return $item->total_technical;
                                })->values();
                            @endphp
                            <x-charts.bar-chart
                                title="Most Technical Issues"
                                dataset-label="Technical Issues"
                                :labels="$labels->toArray()"
                                :dataset="$counts->toArray()"
                            />
                        @endif
                    </div>
                    <!-- Most Flagged (Grouped Bar Chart) -->
                    <div class="mt-6">
                        <p class="text-gray-400 text-xs uppercase my-2">
                            Most Flagged
                        </p>
                        <div>
                            @if($data['mostFlagged']->isEmpty())
                                <div class="flex flex-col gap-4 items-center justify-center border border-gray-100 rounded-sm bg-gray-50/70 min-h-60">
                                    <i data-lucide="chart-no-axes-column" class="w-8 h-8 text-gray-300"></i>
                                    <p class="text-gray-400 text-xs">No data available yet</p>
                                </div>
                            @else
                                @php
                                    $labels = $data['mostFlagged']->map(function($item) use ($view) {
                                        return $view === 'district' ? $item->district : $item->csa_name;
                                    })->values();
                                    $flaggedAccounts = $data['mostFlagged']->map(function($item) {
                                        return $item->flagged_accounts;
                                    })->values();
                                    $flaggedReadings = $data['mostFlagged']->map(function($item) {
                                        return $item->flagged_readings;
                                    })->values();
                                @endphp
                                <div class="relative bg-gray-50/70 rounded-sm h-60 py-2">
                                    <canvas
                                        data-chart-type="bar"
                                        data-chart-config="{{ json_encode([
                                            'data' => [
                                                'labels' => $labels->toArray(),
                                                'datasets' => [
                                                    [
                                                        'label' => 'Flagged Accounts',
                                                        'data' => $flaggedAccounts->toArray(),
                                                        'borderWidth' => 0,
                                                        'backgroundColor' => 'rgb(245 158 11 / 0.7)',
                                                    ],
                                                    [
                                                        'label' => 'Flagged Readings',
                                                        'data' => $flaggedReadings->toArray(),
                                                        'borderWidth' => 0,
                                                        'backgroundColor' => 'rgb(25 139 206 / 0.6)',
                                                    ],
                                                ],
                                            ],
                                            'options' => [
                                                'responsive' => true,
                                                'maintainAspectRatio' => false,
                                                'plugins' => [
                                                    'legend' => ['position' => 'bottom'],
                                                ],
                                                'scales' => [
                                                    'x' => [
                                                        'beginAtZero' => true,
                                                        'grid' => ['display' => false, 'drawBorder' => false, 'z' => -1],
                                                        'border' => ['display' => false],
                                                    ],
                                                    'y' => [
                                                        'beginAtZero' => true,
                                                        'grid' => ['display' => false, 'drawBorder' => false, 'z' => -1],
                                                        'border' => ['display' => false],
                                                    ],
                                                ],
                                            ],
                                        ]) }}"
                                    ></canvas>
                                </div>
                            @endif
                        </div>
                    </div>
    
                    <!-- Field Issues -->
                    <div class="mt-6">
                        <p class="text-gray-400 text-xs uppercase my-2">
                            Field Issues Reported
                        </p>
                        <div>
                            @if($data['fieldIssues']->isEmpty())
                                <div class="flex flex-col gap-4 items-center justify-center border border-gray-100 rounded-sm bg-gray-50/70 min-h-60">
                                    <i data-lucide="chart-no-axes-column" class="w-8 h-8 text-gray-300"></i>
                                    <p class="text-gray-400 text-xs">No data available yet</p>
                                </div>
                            @else
                                @php
                                    $labels = $data['fieldIssues']->map(function($item) use ($view) {
                                        return $view === 'district' ? $item->district : $item->csa_name;
                                    })->values();
                                    $counts = $data['fieldIssues']->map(function($item) {
                                        return $item->total_issues;
                                    })->values();
                                @endphp
                                <x-charts.bar-chart
                                    title="Field Issues"
                                    dataset-label="Issues Reported"
                                    :labels="$labels->toArray()"
                                    :dataset="$counts->toArray()"
                                    tooltip-label="Total Issues"
                                />
                            @endif
                        </div>
                    </div>
                </div>

                <!-- ------------------------------------- -->
                <!-- Recent Uploads Table -->
                <p class="text-gray-400 text-xs uppercase my-2">upload Activity</p>
                <div class="bg-white border rounded-md border-gray-200 px-6 py-4">

                  <!-- Upload activity -->
                    <div class="my-6">
                        <p class="text-gray-400 text-xs uppercase my-2">
                            Last 14 Days
                        </p>
                        <div>
                     
                               <x-charts.activity-line-chart
                                    title="Readings Uploaded (Last 14 Days)"
                                    :labels="$performanceData['activity']['labels']"
                                    :dataset="$performanceData['activity']['data']"
                                />
                        </div>
                    </div>

                    <p class="text-gray-400 text-xs uppercase my-2">Recent Uploads</p>
                    <div>
                        @if($data['recentUploads']->isEmpty())
                            <div class="flex flex-col gap-4 items-center justify-center border border-gray-100 rounded-sm bg-gray-50/70 min-h-40">
                                <i data-lucide="list-todo" class="w-8 h-8 text-gray-300"></i>
                                <p class="text-gray-400 text-xs">No recent uploads found</p>
                            </div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-100">
                                    <thead class="bg-gray-50">
                                        <tr class="text-left text-xxs text-gray-500 uppercase tracking-wider">
                                            <th class="px-6 py-3">Account Number</th>
                                            <th class="px-6 py-3">{{ $view === 'district' ? 'District' : 'CSA' }}</th>
                                            <th class="px-6 py-3">Synced At</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-100">
                                        @foreach($data['recentUploads'] as $upload)
                                            <tr class="hover:bg-gray-50 transition">
                                                <td class="px-6 py-4 text-xs text-gray-500 font-medium">
                                                    {{ $upload['account_number'] ?? '-' }}
                                                </td>
                                                <td class="px-6 py-4 text-xs text-gray-500">
                                                    @if($view === 'district')
                                                        {{ $upload['district'] ?? 'Unknown' }}
                                                    @else
                                                        {{ $upload['csa_name'] ?? 'Unknown' }}
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 text-xs text-gray-500">
                                                    {{ $upload['synced_at'] ? \Carbon\Carbon::parse($upload['synced_at'])->format('Y-m-d H:i:s') : '-' }}
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
        @endif
    </div>
</x-app-layout>

