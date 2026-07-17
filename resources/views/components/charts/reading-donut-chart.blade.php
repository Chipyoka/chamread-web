<div class="card">
    <div class="card-body">

        <canvas id="readingDonutChart"></canvas>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const ctx = document.getElementById('readingDonutChart');

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: [
                'Read',
                'Pending'
            ],
            datasets: [{
                data: [
                    {{ $read }},
                    {{ $pending }}
                ],
                backgroundColor: [
                    '#22C55E',
                    '#F59E0B'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            cutout: '50%',
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

});
</script>