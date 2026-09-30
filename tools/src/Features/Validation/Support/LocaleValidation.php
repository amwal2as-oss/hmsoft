<?php

namespace HMsoft\Tools\Features\Validation\Support;

use Illuminate\Validation\Validator;

final class LocaleValidation
{
    public static function hasAtLeastOne(array $locales, string $field): bool
    {
        return collect($locales)->contains(
            fn (array $locale): bool => filled($locale[$field] ?? null)
        );
    }

    /**
     * @param  array<int, string>  $fields
     */
    public static function hasAtLeastOneOf(array $locales, array $fields): bool
    {
        return collect($locales)->contains(function (array $locale) use ($fields): bool {
            foreach ($fields as $field) {
                if (filled($locale[$field] ?? null)) {
                    return true;
                }
            }

            return false;
        });
    }

    public static function whenLocalesInRequest(Validator $validator, callable $callback): void
    {
        $validator->after(function (Validator $validator) use ($callback): void {
            if (! array_key_exists('locales', $validator->getData())) {
                return;
            }

            $callback($validator, $validator->getData()['locales'] ?? []);
        });
    }

    public static function requireAtLeastOne(Validator $validator, string $field, string $messageKey): void
    {
        self::whenLocalesInRequest($validator, function (Validator $validator, array $locales) use ($field, $messageKey): void {
            if (! self::hasAtLeastOne($locales, $field)) {
                $validator->errors()->add("locales.*.$field", trans($messageKey));
            }
        });
    }

    /**
     * @param  array<int, string>  $fields
     */
    public static function requireAtLeastOneOf(Validator $validator, array $fields, string $messageKey, string $errorKey = 'locales'): void
    {
        self::whenLocalesInRequest($validator, function (Validator $validator, array $locales) use ($fields, $messageKey, $errorKey): void {
            if (! self::hasAtLeastOneOf($locales, $fields)) {
                $validator->errors()->add($errorKey, trans($messageKey));
            }
        });
    }

    /**
     * @param  array<int, string>  $fields
     */
    public static function requireAtLeastOneEach(Validator $validator, array $fields, string $messageKey): void
    {
        foreach ($fields as $field) {
            self::requireAtLeastOne($validator, $field, $messageKey);
        }
    }
}
