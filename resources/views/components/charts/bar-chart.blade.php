
{{--
    resources/views/components/charts/bar-chart.blade.php

    Props:
      labels          array   Required - Chart labels
      dataset         array   Required - Chart data values
      datasetLabel    string  Optional - Label for the dataset (default: 'Readings')
      tooltipLabel    string  Optional - Custom tooltip label (default: same as datasetLabel)
      title           string  Optional - Title above the chart
      backgroundColor string  Optional - Base bar color (default: 'rgb(25 139 206 / 0.6)')
      showPercentage  bool    Optional - Display percentage signs on axes and tooltips (default: false)
      monochromatic   bool    Optional - Use monochromatic color scheme (default: false)
--}}

@props([
    'labels' => [],
    'dataset' => [],
    'datasetLabel' => 'Readings',
    'tooltipLabel' => null,
    'title' => null,
    'backgroundColor' => 'rgb(25 139 206 / 0.6)',
    'showPercentage' => false,
    'monochromatic' => false,
])

@php
    /*
     * ------------------------------------------------------------
     * Tooltip label
     * ------------------------------------------------------------
     */

    $tooltipLabel = $tooltipLabel ?? $datasetLabel;


    /*
     * ------------------------------------------------------------
     * Generate bar colors
     * ------------------------------------------------------------
     */

    $barColors = $backgroundColor;

    if ($monochromatic && count($labels) > 0) {

        /*
         * Extract the RGB/RGBA values from the supplied color.
         *
         * Supported formats:
         *
         * rgb(25 139 206 / 0.6)
         * rgb(25 139 206)
         * rgb(25, 139, 206)
         * rgba(25, 139, 206, 0.6)
         */

        $color = trim($backgroundColor);

        $r = null;
        $g = null;
        $b = null;
        $alpha = 1;

        /*
         * Modern CSS RGB syntax:
         *
         * rgb(25 139 206 / 0.6)
         */
        if (preg_match('/rgb\(\s*([\d.]+)\s+([\d.]+)\s+([\d.]+)(?:\s*\/\s*([\d.]+))?\s*\)/i', $color, $matches)) {

            $r = (float) $matches[1];
            $g = (float) $matches[2];
            $b = (float) $matches[3];

            if (isset($matches[4])) {
                $alpha = (float) $matches[4];
            }

        /*
         * Traditional RGB syntax:
         *
         * rgb(25, 139, 206)
         */
        } elseif (preg_match('/rgb\(\s*([\d.]+)\s*,\s*([\d.]+)\s*,\s*([\d.]+)\s*\)/i', $color, $matches)) {

            $r = (float) $matches[1];
            $g = (float) $matches[2];
            $b = (float) $matches[3];

        /*
         * Traditional RGBA syntax:
         *
         * rgba(25, 139, 206, 0.6)
         */
        } elseif (preg_match('/rgba\(\s*([\d.]+)\s*,\s*([\d.]+)\s*,\s*([\d.]+)\s*,\s*([\d.]+)\s*\)/i', $color, $matches)) {

            $r = (float) $matches[1];
            $g = (float) $matches[2];
            $b = (float) $matches[3];
            $alpha = (float) $matches[4];
        }


        /*
         * --------------------------------------------------------
         * Generate monochromatic shades
         * --------------------------------------------------------
         */

        if ($r !== null && $g !== null && $b !== null) {

            $colors = [];
            $count = count($labels);

            /*
             * Maximum amount of lightening.
             *
             * 0.55 means the lightest shade is 55% of the
             * distance between the base color and white.
             */
            $maxLightenRatio = 0.55;

            for ($i = 0; $i < $count; $i++) {

                /*
                 * Calculate the position of this bar within
                 * the color scale.
                 *
                 * First bar = darkest/base color
                 * Last bar  = lightest color
                 */
                $ratio = $count > 1
                    ? $i / ($count - 1)
                    : 0;

                $lightenRatio = $ratio * $maxLightenRatio;

                /*
                 * Interpolate each RGB channel toward white.
                 */
                $newR = round($r + (255 - $r) * $lightenRatio);
                $newG = round($g + (255 - $g) * $lightenRatio);
                $newB = round($b + (255 - $b) * $lightenRatio);

                $colors[] = "rgb({$newR} {$newG} {$newB} / {$alpha})";
            }

            /*
             * Keep the generated order:
             *
             * Darkest → Lightest
             *
             * Therefore:
             *
             * First bar = darkest
             * Last bar  = lightest
             */
            $barColors = $colors;
        }
    }


    /*
     * ------------------------------------------------------------
     * Chart configuration
     * ------------------------------------------------------------
     */

    $chartConfig = [
        'data' => [
            'labels' => $labels,

            'datasets' => [[
                'label' => $datasetLabel,
                'data' => $dataset,
                'borderWidth' => 0,
                'backgroundColor' => $barColors,
            ]],
        ],

        'options' => [
            'responsive' => true,
            'maintainAspectRatio' => false,

            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],

            'scales' => [
                'x' => [
                    'beginAtZero' => true,

                    'grid' => [
                        'display' => false,
                        'drawBorder' => false,
                        'z' => -1,
                    ],

                    'border' => [
                        'display' => false,
                    ],
                ],

                'y' => [
                    'beginAtZero' => true,

                    'grid' => [
                        'display' => false,
                        'drawBorder' => false,
                        'z' => -1,
                    ],

                    'border' => [
                        'display' => false,
                    ],
                ],
            ],
        ],
    ];


    /*
     * ------------------------------------------------------------
     * Percentage formatting
     * ------------------------------------------------------------
     */

    if ($showPercentage) {

        $chartConfig['options']['plugins']['tooltip'] = [
            'callbacks' => [
                'label' => 'function(context) {
                    return context.parsed.y + "%";
                }',
            ],
        ];

        $chartConfig['options']['scales']['y']['ticks'] = [
            'callback' => 'function(value) {
                return value + "%";
            }',
        ];

        $chartConfig['options']['scales']['y']['max'] = 100;
    }
@endphp

<div class="bg-white">
    <div class="relative bg-gray-50/70 rounded-sm h-60 py-2">

        <canvas
            data-chart-type="bar"
            data-chart-tooltip-label="{{ $tooltipLabel }}"
            data-chart-config="{{ json_encode($chartConfig) }}"
        ></canvas>

    </div>
</div>