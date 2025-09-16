<?php

namespace SmartCms\Kit\Http\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class SetAdminLocale
{
    public function handle(Request $request, Closure $next)
    {
        $availableLocales = ['en', 'uk', 'pl', 'de'];
        $user = Auth::guard('admin')->user();
        if (! $user) {
            return $next($request);
        }
        $adminLocale = $user->locale ?? 'en';

        if (in_array($adminLocale, $availableLocales)) {
            App::setLocale($adminLocale);
        } else {
            App::setLocale('en');
        }

        return $next($request);
    }
}
