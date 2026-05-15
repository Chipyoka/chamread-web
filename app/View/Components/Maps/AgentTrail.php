<?php

namespace App\View\Components\Maps;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AgentTrail extends Component
{
    public array $points;

    public function __construct($points = [])
    {
        $this->points = $points;
    }

    public function render(): View|Closure|string
    {
        return view('components.maps.agent-trail');
    }
}