<?php

namespace HMsoft\Tools\Features\Attribute\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class DynamicValueCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        if (is_null($value)) return null;

        $decoded = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        return $value;
    }

    public function set($model, string $key, $value, array $attributes)
    {
        if (is_null($value)) return null;

        if (is_array($value)) {
            return json_encode(array_values(array_filter($value)));
        }

        return (string) $value;
    }
}
