<?php

namespace SmartCms\Kit\Http\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;

/**
 * Class NoIndex
 */
class NoIndex
{
    /**
     * Handles the request.
     *
     * @param  Request  $request  The request to handle.
     * @param  Closure  $next  The next middleware to handle.
     * @return Response The response.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        $response->headers->set('Cache-Control', 'private, no-cache, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        Config::set('livewire.inject_assets', true);

        return $response;
    }
}
