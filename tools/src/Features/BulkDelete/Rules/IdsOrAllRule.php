<?php

namespace HMsoft\Tools\Features\BulkDelete\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class IdsOrAllRule implements ValidationRule
{
    public function __construct(
        private readonly string $messageKey = 'bulk_delete.ids_invalid',
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === true) {
            return;
        }

        if (! is_array($value) || $value === [] || ! array_is_list($value)) {
            $fail(__($this->messageKey));
        }
    }
}
