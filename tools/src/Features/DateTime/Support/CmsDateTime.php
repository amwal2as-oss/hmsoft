<?php

namespace HMsoft\Tools\Features\DateTime\Support;

use Closure;
use DateTimeInterface;
use HMsoft\Tools\Features\DateTime\Contracts\DateTimeResolverInterface;
use Illuminate\Support\Carbon;

final class CmsDateTime
{
    private static ?Closure $apiTimezoneCallback = null;

    /**
     * Register a callback that returns the API timezone string for the current context.
     *
     * The package does not read users, columns, or external storage — your callback does.
     *
     * @param  callable(): ?string  $callback
     */
    public static function resolveApiTimezoneUsing(callable $callback): void
    {
        self::$apiTimezoneCallback = $callback instanceof Closure
            ? $callback
            : Closure::fromCallable($callback);
    }

    public static function hasApiTimezoneCallback(): bool
    {
        return self::$apiTimezoneCallback !== null;
    }

    /**
     * @internal Used by CallbackDateTimeResolver
     */
    public static function resolveApiTimezone(): ?string
    {
        if (self::$apiTimezoneCallback === null) {
            return null;
        }

        $result = (self::$apiTimezoneCallback)();

        return is_string($result) && $result !== '' ? $result : null;
    }

    public static function apiTimezone(): string
    {
        return app(DateTimeResolverInterface::class)->apiTimezone();
    }

    public static function storageTimezone(): string
    {
        return DateTimeConfig::storageTimezone();
    }

    public static function toApi(DateTimeInterface|string|null $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value) && self::isDateOnly($value)) {
            return $value;
        }

        $date = $value instanceof DateTimeInterface
            ? Carbon::instance($value)
            : Carbon::parse($value, self::storageTimezone());

        return $date->timezone(self::apiTimezone())->toIso8601String();
    }

    public static function fromApi(DateTimeInterface|string $value): Carbon
    {
        if (is_string($value) && self::isDateOnly($value)) {
            return Carbon::parse($value, self::apiTimezone())
                ->startOfDay()
                ->timezone(self::storageTimezone());
        }

        $date = $value instanceof DateTimeInterface
            ? Carbon::instance($value)
            : Carbon::parse($value, self::apiTimezone());

        return $date->timezone(self::storageTimezone());
    }

    public static function nowUtc(): Carbon
    {
        return Carbon::now(self::storageTimezone());
    }

    public static function nowApi(): Carbon
    {
        return Carbon::now(self::apiTimezone());
    }

    /**
     * @param  array<string|int, mixed>|null  $data
     * @return array<string|int, mixed>|null
     */
    public static function transformArray(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        $result = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $result[$key] = self::transformArray($value);
                continue;
            }

            if (is_string($key) && self::shouldTransformKey($key, $value)) {
                $result[$key] = self::toApi($value);
                continue;
            }

            $result[$key] = $value;
        }

        return $result;
    }

    private static function shouldTransformKey(string $key, mixed $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        $extraKeys = DateTimeConfig::transformKeys();
        $matchesKey = str_ends_with($key, '_at') || in_array($key, $extraKeys, true);

        if (! $matchesKey) {
            return false;
        }

        if (is_string($value) && self::isDateOnly($value)) {
            return false;
        }

        return $value instanceof DateTimeInterface || is_string($value);
    }

    private static function isDateOnly(string $value): bool
    {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $value);
    }
}
