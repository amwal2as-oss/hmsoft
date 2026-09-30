<?php

namespace HMsoft\Tools\Features\Attribute\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * StoreAttributeData reads entity_type from the route {scope} parameter.
 * Scoped attribute routes use a fixed URL prefix, so inject scope here.
 */
class InjectAttributeRouteScope
{
    public function handle(Request $request, Closure $next, string $scope): Response
    {
        if ($route = $request->route()) {
            $route->setParameter('scope', $scope);
        }

        return $next($request);
    }
}
