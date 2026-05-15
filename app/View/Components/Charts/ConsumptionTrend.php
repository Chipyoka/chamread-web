<?php

namespace App\View\Components\Charts;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ConsumptionTrend extends Component
{
    public $data;

    public function __construct($data = [])
    {
        $this->data = $data;
    }

    public function render(): View|Closure|string
    {
        return view('components.charts.consumption-trend');
    }
}