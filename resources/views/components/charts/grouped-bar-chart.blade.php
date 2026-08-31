{{--
    resources/views/components/charts/grouped-bar-chart.blade.php

    Two-series bar chart (e.g. flagged accounts vs flagged readings,
    per district/CSA). Same canvas-manager pattern as bar-chart.blade.php
    - no inline script, config lives entirely in data-chart-config.

    Props:
      title           string  (optional, rendered above the canvas)
      labels          array   district or CSA names
      seriesOneLabel  string  e.g. "Flagged Accounts"
      seriesOneData   array
      seriesTwoLabel  string  e.g. "Flagged Readings"
      seriesTwoData   array
--}}
@props([
    'title' => null,
    'labels' => [],
    'seriesOneLabel' => 'Series 1',
    'seriesOneData' => [],
    'seriesTwoLabel' => 'Series 2',
    'seriesTwoData' => [],
])

<div class="bg-white">
    @if($title)
        <p class="text-gray-400 text-xs uppercase mb-2">{{ $title }}</p>
    @endif

    <div class="relative bg-gray-50/70 rounded-sm h-60 py-2">
        <canvas
            data-chart-type="bar"
            data-chart-config="{{ json_encode([
                'data' => [
                    'labels' => $labels,
                    'datasets' => [
                        [
                            'label' => $seriesOneLabel,
                            'data' => $seriesOneData,
                            'borderWidth' => 0,
                            'backgroundColor' => 'rgb(245 158 11 / 0.7)',
                        ],
                        [
                            'label' => $seriesTwoLabel,
                            'data' => $seriesTwoData,
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
</div>