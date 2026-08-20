{{-- resources/views/components/charts/consumption-trend.blade.php --}}
@props(['chartData'])

@php
    $labels = collect($chartData)->pluck('date');
    $values = collect($chartData)->pluck('consumption');
@endphp

<div class="w-full h-80">
    <canvas
        data-chart-type="line"
        data-chart-config="{{ json_encode([
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Consumption',
                    'data' => $values,
                    'borderWidth' => 2,
                    'tension' => 0.4,
                    'fill' => false,
                ]],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'scales' => ['y' => ['beginAtZero' => true]],
                'plugins' => ['legend' => ['display' => true]],
            ],
        ]) }}"
    ></canvas>
</div>