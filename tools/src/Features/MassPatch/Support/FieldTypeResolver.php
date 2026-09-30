<?php

namespace HMsoft\Tools\Features\MassPatch\Support;

use Illuminate\Database\Eloquent\Model;

final class FieldTypeResolver
{
    public const BOOLEAN = 'boolean';
    public const DATE = 'date';
    public const DATETIME = 'datetime';
    public const INTEGER = 'integer';
    public const OTHER = 'other';

    /**
     * @return array<string, self::BOOLEAN|self::DATE|self::DATETIME|self::INTEGER|self::OTHER>
     */
    public static function fromModel(Model $model): array
    {
        $types = [];

        foreach ($model->getCasts() as $field => $cast) {
            $types[$field] = self::normalize($cast);
        }

        return $types;
    }

    public static function normalize(mixed $cast): string
    {
        if (! is_string($cast)) {
            return self::OTHER;
        }

        $cast = ltrim($cast, '?');

        $datetimeCasts = config('mass_patch.datetime_cast_classes', []);
        if (is_array($datetimeCasts) && in_array($cast, $datetimeCasts, true)) {
            return self::DATETIME;
        }

        $base = strtolower(explode(':', $cast, 2)[0]);

        return match ($base) {
            'bool', 'boolean' => self::BOOLEAN,
            'date', 'immutable_date' => self::DATE,
            'datetime', 'immutable_datetime', 'timestamp' => self::DATETIME,
            'int', 'integer' => self::INTEGER,
            default => self::OTHER,
        };
    }

    public static function isPatchableByDefault(string $type): bool
    {
        return in_array($type, [self::BOOLEAN, self::DATE, self::DATETIME], true);
    }
}
