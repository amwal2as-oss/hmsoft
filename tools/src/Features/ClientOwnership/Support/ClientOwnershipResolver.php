<?php

namespace HMsoft\Tools\Features\ClientOwnership\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ClientOwnershipResolver
{
    public static function fromRequest(?Request $request = null): array
    {
        $request ??= request();

        return [
            'client_id' => self::clientIdFromRequest($request),
            'user_id' => auth()->id(),
        ];
    }

    public static function clientIdFromRequest(?Request $request = null): ?string
    {
        $request ??= request();
        $header = config('client_ownership.header', 'X-Client-Id');
        $clientId = $request->header($header);

        return is_string($clientId) && $clientId !== '' ? $clientId : null;
    }

    public static function applyOwnershipScope(
        Builder $query,
        ?int $userId = null,
        ?string $clientId = null,
    ): Builder {
        $userId ??= auth()->id();
        $clientId ??= self::clientIdFromRequest();
        $userIdColumn = config('client_ownership.user_id_column', 'user_id');
        $clientIdColumn = config('client_ownership.client_id_column', 'client_id');

        return $query->where(function (Builder $query) use ($userId, $clientId, $userIdColumn, $clientIdColumn) {
            $hasCondition = false;

            if ($userId) {
                $query->where($userIdColumn, $userId);
                $hasCondition = true;
            }

            if ($clientId) {
                $callback = fn (Builder $q) => $q
                    ->where($clientIdColumn, $clientId)
                    ->whereNull($userIdColumn);

                $hasCondition ? $query->orWhere($callback) : $query->where($callback);
                $hasCondition = true;
            }

            if (! $hasCondition) {
                $query->whereRaw('0 = 1');
            }
        });
    }
}
