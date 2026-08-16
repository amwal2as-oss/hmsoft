<?php

namespace HMsoft\Tools\Features\Attribute\Services;

use HMsoft\Tools\Features\Attribute\Enums\InputTypeEnum;
use HMsoft\Tools\Features\Attribute\Enums\ValueTypeEnum;
use HMsoft\Tools\Features\Attribute\Models\Attribute;
use HMsoft\Tools\Features\Attribute\Support\EavConfig;
use HMsoft\Tools\Features\DynamicFilters\Data\ColumnFilterData;
use HMsoft\Tools\Features\DynamicFilters\Enums\FilterFnsEnum;
use Illuminate\Database\Eloquent\Builder;

class EavAttributeQuery
{
    public function applyFromFilter(Builder $query, ColumnFilterData $filter, ?Attribute $attribute = null): Builder
    {
        $attribute ??= $this->resolveAttributeFromFilterId($query, (string) $filter->id);

        if (! $attribute) {
            return $query;
        }

        return $this->applyFilterFn($query, $attribute, $filter->filterFns, $filter->value);
    }

    public function tryApplyFromFilter(Builder $query, ColumnFilterData $filter): bool
    {
        if (! $this->supports($query->getModel())) {
            return false;
        }

        $attribute = $this->resolveAttributeFromFilterId($query, (string) $filter->id);

        if (! $attribute) {
            return false;
        }

        $this->applyFilterFn($query, $attribute, $filter->filterFns, $filter->value);

        return true;
    }

    public function tryApplySort(Builder $query, string $columnId, string $direction = 'asc'): bool
    {
        if (! $this->supports($query->getModel())) {
            return false;
        }

        $attribute = $this->resolveAttributeFromFilterId($query, $columnId);

        if (! $attribute) {
            return false;
        }

        $this->applySort($query, $attribute, $direction);

        return true;
    }

    public function supports($model): bool
    {
        return EavConfig::isEnabled() && is_object($model) && method_exists($model, 'eavValues');
    }

    public function applyComparison(Builder $query, Attribute $attribute, string $operator, mixed $value, string $boolean = 'and'): Builder
    {
        $operator = $this->normalizeOperator($operator);

        if (in_array($operator, ['IS NULL', 'IS NOT NULL'], true)) {
            return $this->applyNull($query, $attribute, $boolean, $operator === 'IS NOT NULL');
        }

        $method = $boolean === 'or' ? 'orWhereHas' : 'whereHas';
        $not = in_array($operator, ['!=', '<>', 'NOT LIKE'], true);

        if ($not) {
            $innerOperator = $operator === 'NOT LIKE' ? 'LIKE' : '=';
            $doesnt = $boolean === 'or' ? 'orWhereDoesntHave' : 'whereDoesntHave';

            return $query->{$doesnt}('eavValues', function (Builder $valueQuery) use ($attribute, $innerOperator, $value) {
                $this->constrainValue($valueQuery, $attribute, $innerOperator, $value);
            });
        }

        return $query->{$method}('eavValues', function (Builder $valueQuery) use ($attribute, $operator, $value) {
            $this->constrainValue($valueQuery, $attribute, $operator, $value);
        });
    }

    public function applyIn(Builder $query, Attribute $attribute, array $values, string $boolean = 'and', bool $not = false, bool $requireAll = false): Builder
    {
        $values = $this->flattenFilterValues($values);

        if ($values === []) {
            return $not ? $query : $query->whereRaw('0 = 1');
        }

        if ($requireAll && ! $not) {
            foreach ($values as $value) {
                $this->applyComparison($query, $attribute, '=', $value, $boolean);
            }

            return $query;
        }

        $method = $not
            ? ($boolean === 'or' ? 'orWhereDoesntHave' : 'whereDoesntHave')
            : ($boolean === 'or' ? 'orWhereHas' : 'whereHas');

        return $query->{$method}('eavValues', function (Builder $valueQuery) use ($attribute, $values) {
            $this->constrainValueIn($valueQuery, $attribute, $values);
        });
    }

    public function applyNull(Builder $query, Attribute $attribute, string $boolean = 'and', bool $not = false): Builder
    {
        $method = $not
            ? ($boolean === 'or' ? 'orWhereHas' : 'whereHas')
            : ($boolean === 'or' ? 'orWhereDoesntHave' : 'whereDoesntHave');

        return $query->{$method}('eavValues', function (Builder $valueQuery) use ($attribute) {
            $valueQuery->where('attribute_id', $attribute->id);
            $this->constrainHasStoredValue($valueQuery, $attribute);
        });
    }

    public function applyDatePart(Builder $query, Attribute $attribute, string $part, string $operator, mixed $value, string $boolean = 'and'): Builder
    {
        $method = $boolean === 'or' ? 'orWhereHas' : 'whereHas';

        return $query->{$method}('eavValues', function (Builder $valueQuery) use ($attribute, $part, $operator, $value) {
            $valueQuery->where('attribute_id', $attribute->id);
            $column = $valueQuery->qualifyColumn('value_date');
            $whereMethod = match (strtolower($part)) {
                'month' => 'whereMonth',
                'year' => 'whereYear',
                'day' => 'whereDay',
                default => 'whereDate',
            };
            $valueQuery->{$whereMethod}($column, $operator, $value);
        });
    }

    public function applySort(Builder $query, Attribute $attribute, string $direction = 'asc'): Builder
    {
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';
        $model = $query->getModel();
        $ownerTable = $model->getTable();
        $morphType = $model->getMorphClass();
        $valuesTable = EavConfig::table('values') ?: 'eav_values';
        $valueType = $this->valueType($attribute);

        $orderSql = match ($valueType) {
            ValueTypeEnum::Number => "select `value_number` from `{$valuesTable}` where `valuable_type` = ? and `valuable_id` = `{$ownerTable}`.`id` and `attribute_id` = ? limit 1",
            ValueTypeEnum::Boolean => "select `value_boolean` from `{$valuesTable}` where `valuable_type` = ? and `valuable_id` = `{$ownerTable}`.`id` and `attribute_id` = ? limit 1",
            ValueTypeEnum::Date => "select `value_date` from `{$valuesTable}` where `valuable_type` = ? and `valuable_id` = `{$ownerTable}`.`id` and `attribute_id` = ? limit 1",
            ValueTypeEnum::Option => "select `attribute_option_id` from `{$valuesTable}` where `valuable_type` = ? and `valuable_id` = `{$ownerTable}`.`id` and `attribute_id` = ? limit 1",
            default => "select coalesce(`value_text`, `value_long_text`) from `{$valuesTable}` where `valuable_type` = ? and `valuable_id` = `{$ownerTable}`.`id` and `attribute_id` = ? limit 1",
        };

        $query->getQuery()->orderByRaw("({$orderSql}) {$direction}", [$morphType, $attribute->id]);

        return $query;
    }

    public function extractAttributeId(mixed $column): ?int
    {
        if (! is_string($column) || $column === '') {
            return null;
        }

        $column = str_replace('`', '', $column);

        if (preg_match('/^(?:[A-Za-z0-9_]+\.)?(?:attribute_|attr_)(\d+)$/', $column, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('/^(?:[A-Za-z0-9_]+\.)?eav\.id_(\d+)$/', $column, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    public function resolveAttribute(Builder $query, int $attributeId): ?Attribute
    {
        $attribute = Attribute::query()->find($attributeId);

        if (! $attribute) {
            return null;
        }

        $entityType = $query->getModel()->getMorphClass();

        if ($attribute->entity_type && $attribute->entity_type !== $entityType) {
            return null;
        }

        return $attribute;
    }

    protected function applyFilterFn(Builder $query, Attribute $attribute, FilterFnsEnum $filterFn, mixed $value): Builder
    {
        return match ($filterFn) {
            FilterFnsEnum::equals, FilterFnsEnum::equalsString => $this->applyEquals($query, $attribute, $value),
            FilterFnsEnum::notEquals, FilterFnsEnum::weakEquals => $this->applyComparison($query, $attribute, '!=', $value),
            FilterFnsEnum::greaterThan => $this->applyComparison($query, $attribute, '>', $value),
            FilterFnsEnum::greaterThanOrEqualTo => $this->applyComparison($query, $attribute, '>=', $value),
            FilterFnsEnum::lessThan => $this->applyComparison($query, $attribute, '<', $value),
            FilterFnsEnum::lessThanOrEqualTo => $this->applyComparison($query, $attribute, '<=', $value),
            FilterFnsEnum::contains, FilterFnsEnum::fuzzy, FilterFnsEnum::includesString => $this->applyComparison($query, $attribute, 'LIKE', '%'.$this->escapeLike((string) $value).'%'),
            FilterFnsEnum::includesStringSensitive => $this->applyComparison($query, $attribute, 'LIKE', '%'.$this->escapeLike((string) $value).'%'),
            FilterFnsEnum::notContains => $this->applyComparison($query, $attribute, 'NOT LIKE', '%'.$this->escapeLike((string) $value).'%'),
            FilterFnsEnum::startsWith => $this->applyComparison($query, $attribute, 'LIKE', $this->escapeLike((string) $value).'%'),
            FilterFnsEnum::notStartsWith => $this->applyComparison($query, $attribute, 'NOT LIKE', $this->escapeLike((string) $value).'%'),
            FilterFnsEnum::endsWith => $this->applyComparison($query, $attribute, 'LIKE', '%'.$this->escapeLike((string) $value)),
            FilterFnsEnum::notEndsWith => $this->applyComparison($query, $attribute, 'NOT LIKE', '%'.$this->escapeLike((string) $value)),
            FilterFnsEnum::in, FilterFnsEnum::arrIncludes, FilterFnsEnum::arrIncludesSome => $this->applyIn($query, $attribute, (array) $value),
            FilterFnsEnum::arrIncludesAll => $this->applyIn($query, $attribute, (array) $value, requireAll: true),
            FilterFnsEnum::notIn => $this->applyIn($query, $attribute, (array) $value, not: true),
            FilterFnsEnum::between => $this->applyBetween($query, $attribute, $value, inclusive: false),
            FilterFnsEnum::betweenInclusive, FilterFnsEnum::inNumberRange => $this->applyBetween($query, $attribute, $value, inclusive: true),
            FilterFnsEnum::isNull, FilterFnsEnum::empty => $this->applyNull($query, $attribute),
            FilterFnsEnum::notIsNull, FilterFnsEnum::notEmpty => $this->applyNull($query, $attribute, not: true),
            FilterFnsEnum::dayEquals => $this->applyDatePart($query, $attribute, 'day', '=', $value),
            FilterFnsEnum::monthNumEquals => $this->applyDatePart($query, $attribute, 'month', '=', $value),
            FilterFnsEnum::yearEquals => $this->applyDatePart($query, $attribute, 'year', '=', $value),
            FilterFnsEnum::monthEquals => $this->applyMonthEquals($query, $attribute, $value),
        };
    }

    protected function applyEquals(Builder $query, Attribute $attribute, mixed $value): Builder
    {
        if (is_array($value)) {
            return $this->applyIn($query, $attribute, $value);
        }

        return $this->applyComparison($query, $attribute, '=', $value);
    }

    protected function applyBetween(Builder $query, Attribute $attribute, mixed $value, bool $inclusive): Builder
    {
        if (! is_array($value) || count($value) < 2) {
            return $query;
        }

        [$from, $to] = array_values($value);

        if (filled($from)) {
            $this->applyComparison($query, $attribute, $inclusive ? '>=' : '>', $from);
        }

        if (filled($to)) {
            $this->applyComparison($query, $attribute, $inclusive ? '<=' : '<', $to);
        }

        return $query;
    }

    protected function applyMonthEquals(Builder $query, Attribute $attribute, mixed $value): Builder
    {
        try {
            $date = \Carbon\Carbon::parse($value);
        } catch (\Throwable) {
            return $query;
        }

        $this->applyDatePart($query, $attribute, 'year', '=', $date->year);
        $this->applyDatePart($query, $attribute, 'month', '=', $date->month);

        return $query;
    }

    protected function constrainValue(Builder $valueQuery, Attribute $attribute, string $operator, mixed $value): void
    {
        $valueQuery->where('attribute_id', $attribute->id);

        match ($this->valueType($attribute)) {
            ValueTypeEnum::Boolean => $valueQuery->where('value_boolean', $operator, $this->toBoolean($value)),
            ValueTypeEnum::Number => $valueQuery->where('value_number', $operator, $value),
            ValueTypeEnum::Date => $valueQuery->whereDate($valueQuery->qualifyColumn('value_date'), $operator, $value),
            ValueTypeEnum::Option => $this->constrainOption($valueQuery, $operator, $value),
            ValueTypeEnum::Options => $this->constrainOptions($valueQuery, $operator, $value),
            default => $this->constrainText($valueQuery, $operator, $value),
        };
    }

    protected function constrainValueIn(Builder $valueQuery, Attribute $attribute, array $values): void
    {
        $valueQuery->where('attribute_id', $attribute->id);

        match ($this->valueType($attribute)) {
            ValueTypeEnum::Boolean => $valueQuery->whereIn('value_boolean', array_map(fn ($value) => $this->toBoolean($value), $values)),
            ValueTypeEnum::Number => $valueQuery->whereIn('value_number', $values),
            ValueTypeEnum::Date => $valueQuery->whereIn('value_date', $values),
            ValueTypeEnum::Option => $valueQuery->whereIn('attribute_option_id', $this->optionIds($values)),
            ValueTypeEnum::Options => $valueQuery->whereHas('selectedOptions', function (Builder $optionsQuery) use ($values) {
                $optionsQuery->whereIn('attribute_option_id', $this->optionIds($values));
            }),
            default => $valueQuery->where(function (Builder $textQuery) use ($values) {
                $textQuery->whereIn('value_text', $values)
                    ->orWhereIn('value_long_text', $values)
                    ->orWhereHas('translations', function (Builder $translationQuery) use ($values) {
                        $translationQuery->whereIn('value_text', $values)
                            ->orWhereIn('value_long_text', $values);
                    });
            }),
        };
    }

    protected function constrainOption(Builder $valueQuery, string $operator, mixed $value): void
    {
        $optionIds = $this->optionIds($value);

        if ($optionIds === []) {
            $valueQuery->whereRaw('0 = 1');

            return;
        }

        if (in_array($operator, ['LIKE', 'NOT LIKE'], true) || count($optionIds) > 1) {
            $valueQuery->whereIn('attribute_option_id', $optionIds);

            return;
        }

        $valueQuery->where('attribute_option_id', $operator, $optionIds[0]);
    }

    protected function constrainOptions(Builder $valueQuery, string $operator, mixed $value): void
    {
        $optionIds = $this->optionIds($value);

        if ($optionIds === []) {
            $valueQuery->whereRaw('0 = 1');

            return;
        }

        $valueQuery->whereHas('selectedOptions', function (Builder $optionsQuery) use ($optionIds) {
            $optionsQuery->whereIn('attribute_option_id', $optionIds);
        });
    }

    protected function constrainText(Builder $valueQuery, string $operator, mixed $value): void
    {
        $valueQuery->where(function (Builder $textQuery) use ($operator, $value) {
            $textQuery->where('value_text', $operator, $value)
                ->orWhere('value_long_text', $operator, $value)
                ->orWhereHas('translations', function (Builder $translationQuery) use ($operator, $value) {
                    $translationQuery->where(function (Builder $inner) use ($operator, $value) {
                        $inner->where('value_text', $operator, $value)
                            ->orWhere('value_long_text', $operator, $value);
                    });
                });
        });
    }

    protected function constrainHasStoredValue(Builder $valueQuery, Attribute $attribute): void
    {
        match ($this->valueType($attribute)) {
            ValueTypeEnum::Boolean => $valueQuery->whereNotNull('value_boolean'),
            ValueTypeEnum::Number => $valueQuery->whereNotNull('value_number'),
            ValueTypeEnum::Date => $valueQuery->whereNotNull('value_date'),
            ValueTypeEnum::Option => $valueQuery->whereNotNull('attribute_option_id'),
            ValueTypeEnum::Options => $valueQuery->whereHas('selectedOptions'),
            default => $valueQuery->where(function (Builder $textQuery) {
                $textQuery->where(function (Builder $inner) {
                    $inner->whereNotNull('value_text')->where('value_text', '<>', '');
                })->orWhere(function (Builder $inner) {
                    $inner->whereNotNull('value_long_text')->where('value_long_text', '<>', '');
                })->orWhereHas('translations', function (Builder $translationQuery) {
                    $translationQuery->where(function (Builder $inner) {
                        $inner->whereNotNull('value_text')->where('value_text', '<>', '');
                    })->orWhere(function (Builder $inner) {
                        $inner->whereNotNull('value_long_text')->where('value_long_text', '<>', '');
                    });
                });
            }),
        };
    }

    protected function resolveAttributeFromFilterId(Builder $query, string $filterId): ?Attribute
    {
        $attributeId = $this->extractAttributeId($filterId);

        if ($attributeId) {
            return $this->resolveAttribute($query, $attributeId);
        }

        $prefix = EavConfig::filterPrefix();
        $code = str_starts_with($filterId, $prefix.'.')
            ? substr($filterId, strlen($prefix) + 1)
            : $filterId;

        if ($code === '' || str_starts_with($code, 'id_')) {
            return null;
        }

        $attribute = Attribute::query()
            ->forEntity($query->getModel()->getMorphClass())
            ->where('code', $code)
            ->first();

        return $attribute;
    }

    protected function valueType(Attribute $attribute): ValueTypeEnum
    {
        if ($attribute->value_type instanceof ValueTypeEnum) {
            return $attribute->value_type;
        }

        $inputType = $attribute->input_type instanceof InputTypeEnum
            ? $attribute->input_type
            : InputTypeEnum::tryFrom((string) $attribute->input_type);

        if ($inputType) {
            return $inputType->valueType();
        }

        if ((string) $attribute->input_type === 'datetime') {
            return ValueTypeEnum::Date;
        }

        return ValueTypeEnum::tryFrom((string) $attribute->value_type) ?? ValueTypeEnum::String;
    }

    protected function toBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));

            if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }

            if (in_array($normalized, ['0', 'false', 'no', 'off', ''], true)) {
                return false;
            }
        }

        return (bool) $value;
    }

    /**
     * @return list<int>
     */
    protected function optionIds(mixed $value): array
    {
        return collect($this->flattenFilterValues($value))
            ->map(function ($item) {
                if (is_array($item) && isset($item['id'])) {
                    return (int) $item['id'];
                }

                if (is_object($item) && isset($item->id)) {
                    return (int) $item->id;
                }

                return (int) $item;
            })
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return list<mixed>
     */
    protected function flattenFilterValues(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (! is_array($value)) {
            return [$value];
        }

        return array_values($value);
    }

    protected function normalizeOperator(string $operator): string
    {
        $operator = strtoupper(trim($operator));

        return match ($operator) {
            '<>' => '!=',
            'ISNULL' => 'IS NULL',
            'ISNOTNULL', 'NOTNULL' => 'IS NOT NULL',
            default => $operator,
        };
    }

    protected function escapeLike(string $value): string
    {
        return addcslashes($value, '%_\\');
    }

    public static function keysForEntity(string $entityType, bool $filterable = true, bool $sortable = false): array
    {
        if ($filterable && ! $sortable) {
            return EavFilterRegistrar::filterableKeysForEntity($entityType);
        }

        if ($sortable && ! $filterable) {
            return EavFilterRegistrar::sortableKeysForEntity($entityType);
        }

        return array_values(array_unique(array_merge(
            $filterable ? EavFilterRegistrar::filterableKeysForEntity($entityType) : [],
            $sortable ? EavFilterRegistrar::sortableKeysForEntity($entityType) : [],
        )));
    }

    public static function fieldMapForEntity(string $entityType): array
    {
        return EavFilterRegistrar::fieldMapForEntity($entityType);
    }

    /**
     * @return array<string, callable>
     */
    public static function handlersForEntity(string $entityType): array
    {
        $applier = app(self::class);
        $handlers = [];

        foreach (self::keysForEntity($entityType, filterable: true) as $key) {
            $handlers[$key] = function ($query, ColumnFilterData $filter) use ($applier) {
                $applier->applyFromFilter($query, $filter);
            };
        }

        return $handlers;
    }
}
