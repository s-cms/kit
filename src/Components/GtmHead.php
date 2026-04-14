<?php

namespace SmartCms\Kit\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Renders the Google Tag Manager bootstrap script.
 *
 * Per Google's install instructions this block must be placed as high
 * in the <head> as possible so the dataLayer is initialized before any
 * page scripts try to push events.
 *
 * Pair with <x-kit-gtm-body /> placed immediately after <body> open
 * for the <noscript> fallback.
 */
class GtmHead extends Component
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
                <script async>
                    (function(w, d, s, l, i) {
                        w[l] = w[l] || [];
                        w[l].push({
                            'gtm.start': new Date().getTime(),
                            event: 'gtm.js',
                        });
                        var f = d.getElementsByTagName(s)[0],
                            j = d.createElement(s),
                            dl = l != 'dataLayer' ? '&l=' + l : '';
                        j.async = true;
                        j.src = 'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
                        f.parentNode.insertBefore(j, f);
                    })(window, document, 'script', 'dataLayer', '{{ $gtm }}');
                </script>
            @endif
        blade;
    }
}
