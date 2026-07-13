<?php

namespace App\View\Components\Charts;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ReadingDonutChart extends Component
{
    public int $read;
    public int $pending;

    public function __construct($read, $pending)
    {
        $this->read = $read;
        $this->pending = $pending;
    }

    public function render(): View|Closure|string
    {
        return view('components.charts.reading-donut-chart');
    }
}