{{--
    resources/views/components/charts/activity-line-chart.blade.php

    Smooth line chart with a top-to-bottom blue gradient fill, for
    "readings uploaded per day" style trends. Same canvas-manager
    pattern as bar-chart / grouped-bar-chart - all config lives in
    data-chart-config, no inline script. The gradient itself is
    resolved by the chart-manager's __gradient__ marker since a real
    CanvasGradient can't be expressed as plain JSON.

    Props:
      title   string  (optional, rendered above the canvas)
      labels  array   x-axis labels, e.g. ['20 Aug', '21 Aug', ...]
      dataset array   values per label, e.g. [12, 30, 8, ...]
      tooltipLabel string (optional, defaults to "Readings")
--}}
@props([
    'title' => null,
    'labels' => [],
    'dataset' => [],
    'tooltipLabel' => 'Readings',
])

<div class="bg-white">
    <div class="relative bg-gray-50/70 rounded-sm h-60 py-2">
        <canvas
            data-chart-type="line"
            data-chart-tooltip-label="{{ $tooltipLabel }}"
            data-chart-config="{{ json_encode([
                'data' => [
                    'labels' => $labels,
                    'datasets' => [[
                        'label' => $tooltipLabel,
                        'data' => $dataset,
                        'borderColor' => 'rgb(37 99 235)',
                        'borderWidth' => 2,
                        'tension' => 0.4,
                        'fill' => true,
                        'backgroundColor' => [
                            '__gradient__' => [
                                'from' => 'rgba(37, 99, 235, 0.35)',
                                'to' => 'rgba(37, 99, 235, 0)',
                                'direction' => 'vertical',
                            ],
                        ],
                        'pointRadius' => 0,
                        'pointHoverRadius' => 4,
                        'pointBackgroundColor' => 'rgb(37 99 235)',
                    ]],
                ],
                'options' => [
                    'responsive' => true,
                    'maintainAspectRatio' => false,
                    'plugins' => [
                        'legend' => ['display' => false],
                    ],
                    'scales' => [
                        'x' => [
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