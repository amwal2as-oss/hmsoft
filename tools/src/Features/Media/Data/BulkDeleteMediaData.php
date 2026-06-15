<?php

namespace HMsoft\Tools\Features\Media\Data;

use Spatie\LaravelData\Data;

class BulkDeleteMediaData extends Data
{
    public function __construct(public readonly array $ids) {}

    public static function rules(): array
    {
        return [
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:media,id'],
        ];
    }
}
