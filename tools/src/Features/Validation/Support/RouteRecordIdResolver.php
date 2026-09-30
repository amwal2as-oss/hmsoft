<?php

namespace HMsoft\Tools\Features\Validation\Support;

use Illuminate\Support\Facades\Request;

final class RouteRecordIdResolver
{
    /**
     * Resolve record id for update validation/actions.
     * Priority: route parameter first (single update), then body id (bulk/nested).
     */
    public static function resolve(array $payload, string $routeParameterName): int|null
    {
        $routeParam = Request::route($routeParameterName);

        if ($routeParam !== null) {
            if (is_object($routeParam)) {
                return (int) ($routeParam->getKey() ?? $routeParam->id);
            }

            return (int) $routeParam;
        }

        if (! array_key_exists('id', $payload) || $payload['id'] === null || $payload['id'] === '') {
            return null;
        }

        return (int) $payload['id'];
    }

    public static function isNestedCreate(array $payload, string $routeParameterName): bool
    {
        return self::resolve($payload, $routeParameterName) === null;
    }

    /**
     * @return array<int, string>
     */
    public static function updateLocalesRules(bool $isNestedCreate): array
    {
        return $isNestedCreate
            ? ['required', 'array', 'min:1']
            : ['sometimes', 'array', 'min:1'];
    }
}
