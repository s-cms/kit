<?php

namespace SmartCms\Kit\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Renders the Google Tag Manager <noscript> fallback iframe.
 *
 * Per Google's install instructions this must be placed immediately
 * after the opening <body> tag so users with JavaScript disabled still
 * register a page view.
 *
 * Pair with <x-kit-gtm-head /> placed inside <head> for the bootstrap
 * script.
 */
class GtmBody extends Component
{
    public ?string $gtm;

    public function __construct()
    {
        $this->gtm = app('s')->get('gtm', null);
    }

    public function render(): View | Closure | string
    {
        return <<<'blade'
            @if ($gtm)
                <noscript>
                    <iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtm }}" height="0" width="0" style="display: none; visibility: hidden"></iframe>
                </noscript>
            @endif
        blade;
    }
}
