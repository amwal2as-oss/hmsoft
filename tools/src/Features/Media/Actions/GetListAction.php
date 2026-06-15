<?php

namespace HMsoft\Tools\Features\Media\Actions;

use HMsoft\Tools\Features\Media\Models\Medium;
use HMsoft\Tools\Features\DynamicFilters\Services\AutoFilterAndSortService;
use Illuminate\Database\Eloquent\Builder;

class GetListAction
{
    public function execute(string $ownerId, string $ownerType): array
    {
        return AutoFilterAndSortService::dynamicSearchFromRequest(
            model: Medium::class,
            extraOperation: function (Builder &$query) use ($ownerId, $ownerType) {
                $query->with(Medium::DEFAULT_INCLUDES);
                $query->where('owner_id', $ownerId)->where('owner_type', $ownerType);
            },
        );
    }
}
