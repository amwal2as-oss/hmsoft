<?php

namespace HMsoft\Tools\Features\DynamicFilters\Data;

use HMsoft\Tools\Features\DynamicFilters\Enums\FilterFnsEnum;
use Spatie\LaravelData\Data;
use Illuminate\Contracts\Database\Query\Expression;

/**
 * Single column filter rule mapped to a SQL WHERE clause.
 *
 * Filter function names match the frontend Material React Table / TanStack filter fns.
 *
 * @see FilterFnsEnum Supported operators
 */
class ColumnFilterData extends Data
{
    /**
     * @param string|Expression|null $id Column name, dot-path, or raw SQL expression
     * @param string|array|null $value Filter value (array for `in`, `between`, etc.)
     * @param FilterFnsEnum $filterFns Operator
     * @param string|null $columnPrefix Optional table alias prefix
     * @param bool $autoAddPrefixFromCurrentModel Prepend main model table name to bare columns
     */
    public function __construct(
        public string|Expression|null $id,
        public string|array|null   $value,
        public FilterFnsEnum  $filterFns,
        public string |null        $columnPrefix = null,
        public bool        $autoAddPrefixFromCurrentModel = false,
    ) {}

    /**
     * Apply this filter to a query builder using the configured operator.
     */
    function buildQueryWhereStatment(
        \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder &$queryBuilder,
        ColumnFilterData $columnFilterData,
        string|null $columnPrefix = null,
        // The default value of this parameter is now less important
        // because we will pass `false` from the service.
        bool $autoAddPrefixFromCurrentModel = false,
        string $conditionType  = 'AND',
    ): \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder {
        $columnName = $columnFilterData->id;
        $value = $columnFilterData->value;

        // [NEW] Helper function to escape special characters for LIKE queries
        // This ensures that %, _, and \ are treated as literals, not wildcards.
        // It allows searching for "50%" without breaking the query.
        $escapeLike = fn($val) => addcslashes((string)$val, '%_\\');

        $whereMethod = $conditionType === 'OR' ? 'orWhere' : 'where';

        if (!$columnName instanceof Expression) {
            if ($autoAddPrefixFromCurrentModel && is_null($columnPrefix)) {
                $model = $queryBuilder->getModel();
                $tableName = $model->getTable();
                $columnName = $tableName . '.' .  $columnName;
            }
            if (isset($columnPrefix)) {
                $columnName = $columnPrefix .  $columnName;
            }
        }

        switch ($columnFilterData->filterFns) {

            case FilterFnsEnum::dayEquals:
                $date = \Carbon\Carbon::parse($value);

                $queryBuilder->{$whereMethod . 'Date'}(
                    $columnName,
                    '=',
                    $date->toDateString()
                );
                break;

            case FilterFnsEnum::monthNumEquals:
                $month = is_numeric($value)
                    ? (int) $value
                    : \Carbon\Carbon::parse($value)->month;

                $queryBuilder->{$whereMethod . 'Month'}(
                    $columnName,
                    '=',
                    $month
                );
                break;

            case FilterFnsEnum::monthEquals:
                try {
                    $date = \Carbon\Carbon::parse($value);

                    $queryBuilder->{$whereMethod}(function ($subQuery) use ($columnName, $date) {
                        $subQuery->whereYear($columnName, '=', $date->year)
                            ->whereMonth($columnName, '=', $date->month);
                    });
                } catch (\Exception $e) {
                }
                break;

            case FilterFnsEnum::yearEquals:
                $year = is_numeric($value)
                    ? (int) $value
                    : \Carbon\Carbon::parse($value)->year;

                $queryBuilder->{$whereMethod . 'Year'}(
                    $columnName,
                    '=',
                    $year
                );
                break;

            case FilterFnsEnum::equals:
                $queryBuilder->{$whereMethod}($columnName, '=', $value);
                break;
            case FilterFnsEnum::notEquals:
            case FilterFnsEnum::weakEquals:
                $queryBuilder->{$whereMethod}($columnName, '!=', $value);
                break;
            case FilterFnsEnum::equalsString:
                $queryBuilder->{$whereMethod}($columnName, '=', (string) $value);
                break;
            case FilterFnsEnum::isNull:
                $queryBuilder->{$whereMethod . 'Null'}($columnName);
                break;
            case FilterFnsEnum::notIsNull:
                $queryBuilder->{$whereMethod . 'NotNull'}($columnName);
                break;
            case FilterFnsEnum::greaterThan:
                $queryBuilder->{$whereMethod}($columnName, '>', $value);
                break;
            case FilterFnsEnum::greaterThanOrEqualTo:
                $queryBuilder->{$whereMethod}($columnName, '>=', $value);
                break;
            case FilterFnsEnum::lessThan:
                $queryBuilder->{$whereMethod}($columnName, '<', $value);
                break;
            case FilterFnsEnum::lessThanOrEqualTo:
                $queryBuilder->{$whereMethod}($columnName, '<=', $value);
                break;
            case FilterFnsEnum::arrIncludes:
            case FilterFnsEnum::arrIncludesAll:
            case FilterFnsEnum::arrIncludesSome:
            case FilterFnsEnum::in:
                $queryBuilder->{$whereMethod . 'In'}($columnName, (array) $value);
                break;
            case FilterFnsEnum::notIn:
                $queryBuilder->{$whereMethod . 'NotIn'}($columnName, (array) $value);
                break;
            case FilterFnsEnum::between:
                if (is_array($value) && count($value) === 2) {
                    [$from, $to] = $value;
                    $queryBuilder->when(isset($from) && !empty($from), function ($builder) use ($columnName, $from,  $whereMethod) {
                        $builder->{$whereMethod}($columnName, '>', $from);
                    });
                    $queryBuilder->when(isset($to) && !empty($to), function ($builder) use ($columnName, $to,  $whereMethod) {
                        $builder->{$whereMethod}($columnName, '<', $to);
                    });
                }
                break;
            case FilterFnsEnum::betweenInclusive:
            case FilterFnsEnum::inNumberRange:
                if (is_array($value) && count($value) === 2) {
                    [$from, $to] = $value;
                    $queryBuilder->when(isset($from) && !empty($from), function ($builder) use ($columnName, $from,  $whereMethod) {
                        $builder->{$whereMethod}($columnName, '>=', $from);
                    });
                    $queryBuilder->when(isset($to) && !empty($to), function ($builder) use ($columnName, $to,  $whereMethod) {
                        $builder->{$whereMethod}($columnName, '<=', $to);
                    });
                }
                break;

            // --- Escaping Applied Here ---
            case FilterFnsEnum::contains:
            case FilterFnsEnum::fuzzy:
            case FilterFnsEnum::includesString:
                $queryBuilder->{$whereMethod}($columnName, 'LIKE', "%" . $escapeLike($value) . "%");
                break;
            case FilterFnsEnum::notContains:
                $queryBuilder->{$whereMethod}($columnName, 'NOT LIKE', "%" . $escapeLike($value) . "%");
                break;
            case FilterFnsEnum::startsWith:
                $queryBuilder->{$whereMethod}($columnName, 'LIKE', $escapeLike($value) . "%");
                break;
            case FilterFnsEnum::notStartsWith:
                $queryBuilder->{$whereMethod}($columnName, 'NOT LIKE', $escapeLike($value) . "%");
                break;
            case FilterFnsEnum::endsWith:
                $queryBuilder->{$whereMethod}($columnName, 'LIKE', "%" . $escapeLike($value));
                break;
            case FilterFnsEnum::notEndsWith:
                $queryBuilder->{$whereMethod}($columnName, 'NOT LIKE', "%" . $escapeLike($value));
                break;
            // -----------------------------

            case FilterFnsEnum::empty:
                $queryBuilder->{$whereMethod}($columnName, '=', '');
                break;
            case FilterFnsEnum::notEmpty:
                $queryBuilder->{$whereMethod}($columnName, '<>', '');
                break;
            case FilterFnsEnum::includesStringSensitive:
                $queryBuilder->{$whereMethod . 'Raw'}(
                    "UPPER({$columnName}) LIKE ?",
                    ['%' . strtoupper($escapeLike($value)) . '%']
                );
                break;
        }
        return $queryBuilder;
    }

    function buildQuery(\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder &$queryBuilder)
    {
        $queryBuilder = $this->buildQueryWhereStatment(
            $queryBuilder,
            $this,
            $this->columnPrefix,
            $this->autoAddPrefixFromCurrentModel
        );
        return $queryBuilder;
    }

    function toArray(): array
    {
        return [
            "id" => $this->id instanceof Expression ? $this->id->getValue() : $this->id,
            "value" => $this->value,
            "filterFns" => $this->filterFns->name,
        ];
    }
}
