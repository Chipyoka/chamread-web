{{-- resources/views/components/charts/reading-donut-chart.blade.php --}}
<div class="w-[260px]">
    <div class="card-body">
        <canvas
            data-chart-type="doughnut"
            data-chart-config="{{ json_encode([
                'data' => [
                    'labels' => ['Read', 'Pending'],
                    'datasets' => [[
                        'data' => [$read, $pending],
                        'backgroundColor' => ['#22C55E', '#F59E0B'],
                        'borderWidth' => 0,
                    ]],
                ],
                'options' => [
                    'responsive' => true,
                    'cutout' => '70%',
                    'plugins' => ['legend' => ['position' => 'bottom']],
                ],
            ]) }}"
        ></canvas>
    </div>
</div>