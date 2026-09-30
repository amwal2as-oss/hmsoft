<?php

namespace HMsoft\Tools\Features\BulkDelete\Support;

use HMsoft\Tools\Features\BulkDelete\Rules\IdsOrAllRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

final class BulkDeleteIds
{
    /**
     * @return array<string, mixed>
     */
    public static function rules(
        string $table,
        string $column = 'id',
        string $messageKey = 'bulk_delete.ids_invalid',
    ): array {
        return self::rulesWith(['integer', "exists:{$table},{$column}"], $messageKey);
    }

    /**
     * @param  list<mixed>  $itemRules
     * @return array<string, mixed>
     */
    public static function rulesWith(
        array $itemRules,
        string $messageKey = 'bulk_delete.ids_invalid',
    ): array {
        return [
            'ids' => ['required', new IdsOrAllRule($messageKey)],
            'ids.*' => $itemRules,
        ];
    }

    public static function isAll(mixed $ids): bool
    {
        return $ids === true;
    }

    /**
     * @param  Builder<*>|Relation<*, *, *>  $query
     * @return Builder<*>|Relation<*, *, *>
     */
    public static function constrain(Builder|Relation $query, mixed $ids, string $column = 'id'): Builder|Relation
    {
        if (! self::isAll($ids)) {
            $query->whereIn($column, is_array($ids) ? $ids : []);
        }

        return $query;
    }
}
