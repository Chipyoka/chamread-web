{{-- resources/views/components/charts/consumption-trend.blade.php --}}
@props(['chartData'])  {{-- Change from 'data' to 'chartData' --}}

@php
    $chartId = 'trend_' . uniqid();
@endphp

<div class="w-full h-80">
    <canvas id="{{ $chartId }}"></canvas>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const rawData = @json($chartData);  {{-- Use chartData here --}}

        const labels = rawData.map(item => item.date);
        const values = rawData.map(item => item.consumption);

        const ctx = document.getElementById("{{ $chartId }}");

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Consumption Trend',
                    data: values,
                    borderWidth: 2,
                    tension: 0.4,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: true
                    }
                }
            }
        });
    });
</script>