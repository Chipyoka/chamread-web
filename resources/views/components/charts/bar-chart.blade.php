<div
    x-data="barChartComponent({
        labels: @js($labels),
        dataset: @js($dataset),
        datasetLabel: '{{ $datasetLabel }}'
    })"
    x-init="init()"
    class="bg-white"
>

    <div class="relative  bg-gray-50/70 rounded-sm h-60 py-2">
        <canvas x-ref="canvas"></canvas>
    </div>
</div>

@once
    @push('scripts')
        <script>
            function barChartComponent(chartData) {
                return {
                    chart: null,

                    init() {
                        // safety guard: destroy existing chart instance
                        if (this.chart) {
                            this.chart.destroy();
                        }

                        this.chart = new Chart(this.$refs.canvas, {
                            type: 'bar',

                            data: {
                                labels: chartData.labels,

                                datasets: [{
                                    label: chartData.datasetLabel,
                                    data: chartData.dataset,
                                    borderWidth: 0,
                                    backgroundColor: 'rgb(25 139 206 / 0.6)'

                                }]
                            },

                         options: {
                            responsive: true,
                            maintainAspectRatio: false,

                            scales: {
                                

                                x: {
                                    beginAtZero: true,

                                    grid: {
                                        display: false,
                                        drawBorder: false,
                                            z: -1
                                    },

                                    border: {
                                        display: false
                                    }
                                },
                                y: {
                                    beginAtZero: true,

                                    grid: {
                                        display: false,
                                        drawBorder: false,
                                            z: -1
                                    },

                                    border: {
                                        display: false
                                    }
                                }
                            },
                            plugins: {
                                tooltip: {
                                    enabled: true,

                                    callbacks: {
                                        label: function(context) {
                                            return `Readings: ${context.raw}`;
                                        }
                                    }
                                }
                            }
                            }
                        });
                    }
                }
            }
        </script>
    @endpush
@endonce