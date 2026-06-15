<?php

namespace HMsoft\Tools\Features\DynamicFilters\Services\Concerns;

use HMsoft\Tools\Features\DynamicFilters\Enums\PaginationFormateEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

trait PaginatesResults
{
    protected function handleCountOnly(Builder $query): array
    {
        $countQuery = clone $query;
        $countQuery->getQuery()->orders = null;
        $countQuery->getQuery()->columns = null;
        $countQuery->select(DB::raw('1'));
        $totalRecords = DB::connection($this->model->getConnectionName())
            ->table(DB::raw("({$countQuery->toSql()}) as sub"))
            ->mergeBindings($countQuery->getQuery())
            ->count();

        return ['data' => $totalRecords, 'pagination' => null];
    }

    public static function handelResultFormate(PaginationFormateEnum $paginationFormate, $page, $perPage, &$query): array
    {
        switch ($paginationFormate) {
            case PaginationFormateEnum::normal:
                return ['data' => $query->paginate(perPage: (int)$perPage, page: (int)$page), 'pagination' => null];
            case PaginationFormateEnum::separated:
                return self::separatedPaginate($query->paginate(perPage: (int)$perPage, page: (int)$page));
            case PaginationFormateEnum::normal_simple:
                return ['data' => $query->simplePaginate(perPage: (int)$perPage, page: (int)$page), 'pagination' => null];
            case PaginationFormateEnum::separated_simple:
                return self::separatedSimplePaginate($query->simplePaginate(perPage: (int)$perPage, page: (int)$page));
            case PaginationFormateEnum::none:
            default:
                return ['data' => $query->get(), 'pagination' => null];
        }
    }

    public static function separatedPaginate($paginate)
    {
        $data = $paginate->getCollection();
        $result = collect($paginate)->toArray();
        unset($result['data']);
        return ['data' => $data, 'pagination' => $result];
    }

    public static function separatedSimplePaginate($paginate)
    {
        $data = $paginate->items();
        $result = $paginate->toArray();
        unset($result['data']);
        return ['data' => $data, 'pagination' => $result];
    }

    public static function handelPageAndPerPage($page, $perPage, $totalCount)
    {
        $result['page'] = $page;
        $result['perPage'] = $perPage;
        if ($perPage == 'all' || $page == 'all') {
            $result['perPage'] = $totalCount > 0 ? $totalCount : 1;
            $result['page'] = 1;
        }
        return $result;
    }
}
