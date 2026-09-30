<?php

namespace HMsoft\Tools\Features\MassPatch\Support;

use HMsoft\Tools\Features\MassPatch\Contracts\MassPatchable;
use Illuminate\Database\Eloquent\Model;

final class RuleBuilder
{
    /**
     * @param  Model&MassPatchable  $model
     * @return array<string, list<string>>
     */
    public static function for(Model&MassPatchable $model): array
    {
        $types = FieldTypeResolver::fromModel($model);
        $rules = [];

        foreach ($model->defineMassPatchableAttributes() as $field) {
            $rules[$field] = match ($types[$field] ?? FieldTypeResolver::OTHER) {
                FieldTypeResolver::BOOLEAN => ['sometimes', 'boolean'],
                FieldTypeResolver::DATE, FieldTypeResolver::DATETIME => ['sometimes', 'nullable', 'date'],
                FieldTypeResolver::INTEGER => ['sometimes', 'integer'],
                default => ['sometimes'],
            };
        }

        $fields = $model->defineMassPatchableAttributes();
        $pairs = config('mass_patch.schedule_pairs', [
            ['from_time', 'to_time'],
            ['start_date', 'end_date'],
        ]);

        if (! is_array($pairs)) {
            return $rules;
        }

        foreach ($pairs as $pair) {
            if (! is_array($pair) || count($pair) < 2) {
                continue;
            }

            [$start, $end] = array_values($pair);

            if (! in_array($start, $fields, true) || ! in_array($end, $fields, true)) {
                continue;
            }

            $rules[$end] = array_values(array_unique([
                ...($rules[$end] ?? ['sometimes', 'nullable', 'date']),
                "after_or_equal:{$start}",
            ]));
        }

        return $rules;
    }
}
