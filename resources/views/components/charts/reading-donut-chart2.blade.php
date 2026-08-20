<div class="w-[260px]">
    <div class="card-body">
        <canvas
            data-chart-type="doughnut"
            data-chart-config="{{ json_encode([
                'data' => [
                    'labels' => [$l1, $l2],
                    'datasets' => [[
                        'data' => [$completed, $pending],
                        'backgroundColor' => [$c1, $c2],
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