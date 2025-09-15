<?php

namespace SmartCms\Kit\Components;

use Illuminate\View\Component;

class Link extends Component
{
    public string $title;

    public string $url;

    public string $target;

    public string $currentClass;

    public function __construct(?array $options = null, ?string $current = null)
    {
        if (! is_array($options)) {
            $options = [];
        }
        $this->title = $options['title'] ?? '';
        $this->url = $options['url'] ?? '';
        $this->target = $options['target'] ?? '_self';
        $this->currentClass = $current ?? '';
    }

    public function render()
    {
        return view('kit::link');
    }
}
