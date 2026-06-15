<?php

namespace HMsoft\Tools\Features\Media\Traits;

use Illuminate\Support\Str;

trait ExtractsOwnerFromRoute
{
    public static function getOwnerFromRoute(): array
    {
        $request = request();
        $route = $request->route();
        if (!$route) return ['owner_id' => null, 'owner_type' => null];

        $parameters = $route->parameters();
        if (isset($parameters['owner_id'])) {
            $ownerId = $parameters['owner_id'];
            $ownerType = $parameters['owner_type'] ?? null;
            if (is_object($ownerId)) {
                $ownerType = $ownerId->getMorphClass();
                $ownerId = $ownerId->getKey();
            }
            return ['owner_id' => (string) $ownerId, 'owner_type' => $ownerType];
        }

        $paramNames = $route->parameterNames();
        if (empty($paramNames)) return ['owner_id' => null, 'owner_type' => null];

        $uri = $route->uri();
        $lastParamName = end($paramNames);

        if (Str::endsWith($uri, '{' . $lastParamName . '}')) {
            if (count($paramNames) > 1) {
                $ownerParamName = $paramNames[count($paramNames) - 2];
            } else {
                return ['owner_id' => null, 'owner_type' => null];
            }
        } else {
            $ownerParamName = $lastParamName;
        }

        $ownerId = $parameters[$ownerParamName];
        $ownerType = $ownerParamName;

        if (is_object($ownerId)) {
            $ownerType = $ownerId->getMorphClass();
            $ownerId = $ownerId->getKey();
        } else {
            $ownerType = Str::singular($ownerParamName);
        }

        return ['owner_id' => (string) $ownerId, 'owner_type' => $ownerType];
    }

    public static function resolveOwnerModel()
    {
        $data = self::getOwnerFromRoute();

        abort_if(empty($data['owner_id']) || empty($data['owner_type']), 404, 'Owner ID or Type not found in route.');

        $class = \Illuminate\Database\Eloquent\Relations\Relation::getMorphedModel($data['owner_type']);

        // abort_if(!$class, 404, "Unregistered Morph Type: '{$data['owner_type']}'. Please register it in the Morph Map.");
        abort_if(!$class, 404);

        return $class::findOrFail($data['owner_id']);
    }
}
