<div class="w-[260px]">
    <div class="card-body">
        <canvas id="readingDonutChart2"></canvas>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const ctx = document.getElementById('readingDonutChart2');

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: [
                '{{ $l1 }}',
                '{{ $l2 }}'
            ],
            datasets: [{
                data: [
                    {{ $completed }},
                    {{ $pending }}
                ],
                backgroundColor: [
                    '{{ $c1 }}',
                    '{{ $c2 }}'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            cutout: '70%',
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

});
</script>