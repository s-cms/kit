<?php

namespace SmartCms\Kit\Components;

use Illuminate\View\Component;

class Icon extends Component
{
    public string $icon;

    public function __construct(?string $options = null)
    {
        $this->icon = $options;
    }

    public function render()
    {
        return view('kit::icon', ['icon' => $this->icon]);
    }
}
