<div class="bg-white">
    <div class="relative bg-gray-50/70 rounded-sm h-60 py-2">
        <canvas
            data-chart-type="bar"
            data-chart-tooltip-label="Readings"
            data-chart-config="{{ json_encode([
                'data' => [
                    'labels' => $labels,
                    'datasets' => [[
                        'label' => $datasetLabel,
                        'data' => $dataset,
                        'borderWidth' => 0,
                        'backgroundColor' => 'rgb(25 139 206 / 0.6)',
                    ]],
                ],
                'options' => [
                    'responsive' => true,
                    'maintainAspectRatio' => false,
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
</div>