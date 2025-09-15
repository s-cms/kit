<?php

namespace SmartCms\Kit\Http\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;

class SetAdminLocale
{
    public function handle(Request $request, Closure $next)
    {
        $availableLocales = ['en', 'uk', 'pl', 'de'];
        if (!Auth::guard('admin')->check()) {
            abort(403);
            return $next($request);
        }
        $user = Auth::guard('admin')->user();
        $adminLocale = $user->locale ?? 'en';

        if (in_array($adminLocale, $availableLocales)) {
            App::setLocale($adminLocale);
        } else {
            App::setLocale('en');
        }

        return $next($request);
    }
}
