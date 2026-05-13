<?php

namespace App\View\Components\Charts;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class BarChart extends Component
{
    public array $labels;
    public array $dataset;
    public string $title;
    public string $datasetLabel;

    public function __construct(
        array $labels = [],
        array $dataset = [],
        string $title = 'Chart',
        string $datasetLabel = 'Dataset'
    ) {
        $this->labels = $labels;
        $this->dataset = $dataset;
        $this->title = $title;
        $this->datasetLabel = $datasetLabel;
    }

    public function render(): View|Closure|string
    {
        return view('components.charts.bar-chart');
    }
}