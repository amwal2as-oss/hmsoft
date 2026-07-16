<?php

namespace HMsoft\Tools\Features\Localization\Middleware;

use Closure;
use HMsoft\Tools\Features\Localization\Support\LocaleResolver;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleMiddleware
{
    /**
     * Handle an incoming request to set the application's locale.
     */
    public function handle(Request $request, Closure $next): Response
    {
        LocaleResolver::apply($request);

        return $next($request);
    }
}
