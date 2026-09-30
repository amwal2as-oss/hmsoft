<?php

namespace HMsoft\Tools\Features\Validation\Traits;

use HMsoft\Tools\Features\Validation\Support\RouteRecordIdResolver;

trait ResolvesUpdateRecordId
{
    protected static function injectRouteRecordId(array $properties, string $routeParameterName): array
    {
        $resolvedId = RouteRecordIdResolver::resolve($properties, $routeParameterName);

        if ($resolvedId !== null) {
            $properties['id'] = $resolvedId;
        }

        return $properties;
    }
}
