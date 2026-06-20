<?php

namespace HMsoft\Tools\Features\OptionalAuth\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class OptionalAuthMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$guards): Response
    {

        if (empty($guards)) {
            $guards = array_keys(config('auth.guards'));
        }

        foreach ($guards as $guard) {

            if (Auth::guard($guard)->check()) {
                Auth::shouldUse($guard);
                $request->setUserResolver(function () use ($guard) {
                    return Auth::guard($guard)->user();
                });
                break;
            }
        }

        return $next($request);
    }
}
