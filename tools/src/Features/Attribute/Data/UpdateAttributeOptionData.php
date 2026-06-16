<?php

namespace HMsoft\Tools\Features\Attribute\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class UpdateAttributeOptionData extends Data
{
    public function __construct(
        public readonly array $locales,
        public readonly Optional|int|null $id,
        public readonly Optional|bool $is_active,
        public readonly Optional|int $sort_number,
    ) {}

    public static function rules(): array
    {
        return [
            'id'                 => ['sometimes', 'nullable', 'integer', 'exists:attribute_options,id'],
            'is_active'          => ['sometimes', 'boolean'],
            'sort_number'        => ['sometimes', 'integer'],
            'locales'            => ['required', 'array', 'min:1'],
            'locales.*.locale'   => ['required', 'string'],
            'locales.*.title'    => ['required', 'string', 'max:255'],
        ];
    }
}
