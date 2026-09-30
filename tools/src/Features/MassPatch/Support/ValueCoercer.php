<?php

namespace HMsoft\Tools\Features\MassPatch\Support;

use Illuminate\Database\Eloquent\Model;

final class ValueCoercer
{
    /**
     * Coerce request values before validation. Booleans accept "true"/"1"/etc.
     * Dates are left as-is so model casts store them.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function coerce(Model $model, array $values): array
    {
        $types = FieldTypeResolver::fromModel($model);

        foreach ($values as $field => $value) {
            if (($types[$field] ?? null) !== FieldTypeResolver::BOOLEAN) {
                continue;
            }

            if ($value === null) {
                continue;
            }

            $values[$field] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        return $values;
    }
}
