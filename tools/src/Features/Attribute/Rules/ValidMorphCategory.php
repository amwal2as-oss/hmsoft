<?php

namespace HMsoft\Tools\Features\Attribute\Rules;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Closure;

class ValidMorphCategory implements ValidationRule
{
    public function __construct(protected array $payload) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $index = explode('.', $attribute)[1] ?? null;
        $type = Arr::get($this->payload, "categories.{$index}.category_type");

        if (!$type) {
            return;
        }

        $modelClass = Relation::getMorphedModel($type) ?? $type;

        if (! class_exists($modelClass)) {
            $fail(trans('cms_eav::validation.messages.morph_not_found', ['type' => $type]));
            return;
        }

        if (method_exists($modelClass, 'validateEavAttachment')) {
            $validationResult = $modelClass::validateEavAttachment($value);

            if ($validationResult !== true) {
                $message = is_string($validationResult)
                    ? $validationResult
                    : trans('cms_eav::validation.messages.category_conditions_failed');

                $fail($message);
            }
        } else {
            $table = (new $modelClass)->getTable();
            if (! DB::table($table)->where('id', $value)->exists()) {
                $fail(trans('cms_eav::validation.messages.invalid_category'));
            }
        }
    }
}
