<?php

namespace HMsoft\Tools\Features\Attribute\Actions;

use HMsoft\Tools\Features\Attribute\Models\Attribute;
use HMsoft\Tools\Features\DynamicFilters\Services\AutoFilterAndSortService;
use Illuminate\Database\Eloquent\Builder;

class GetListAction
{
    public function execute(string $scope): array
    {
        return AutoFilterAndSortService::dynamicSearchFromRequest(
            model: Attribute::class,
            extraOperation: function (Builder &$query) use ($scope) {
                $query->where('scope', $scope)->with(Attribute::DEFAULT_INCLUDES);
            },
        );
    }
}
