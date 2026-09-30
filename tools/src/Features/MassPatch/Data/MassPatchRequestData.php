<?php

namespace HMsoft\Tools\Features\MassPatch\Data;

use HMsoft\Tools\Features\MassPatch\Contracts\MassPatchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelData\Data;

class MassPatchRequestData extends Data
{
    /**
     * @param  array<string, mixed>  $values
     */
    public function __construct(
        public readonly array $values,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @param  Model&MassPatchable  $model
     */
    public static function fromPayload(array $payload, Model&MassPatchable $model): self
    {
        $allowed = $model->defineMassPatchableAttributes();

        if ($payload === [] || array_is_list($payload)) {
            throw ValidationException::withMessages([
                'payload' => [__(config('mass_patch.messages.values_required', 'mass_patch.values_required'))],
            ]);
        }

        $unknown = array_values(array_diff(array_keys($payload), $allowed));

        if ($unknown !== []) {
            throw ValidationException::withMessages([
                $unknown[0] => [__(config('mass_patch.messages.unknown_fields', 'mass_patch.unknown_fields'), [
                    'fields' => implode(', ', $unknown),
                ])],
            ]);
        }

        validator($payload, $model->defineMassPatchRules())->validate();

        return new self(values: $payload);
    }
}
