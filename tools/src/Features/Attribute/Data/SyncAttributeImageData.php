<?php

namespace HMsoft\Tools\Features\Attribute\Data;

use HMsoft\Tools\Features\Media\Traits\InteractsWithMediaRules;
use Spatie\LaravelData\Data;

class SyncAttributeImageData extends Data
{
    use InteractsWithMediaRules;

    public function __construct(
        public readonly ?bool $delete_image = null,
        public readonly mixed $image,
    ) {}

    public static function rules(): array
    {
        return array_merge(
            ['image' => ['required']],
            self::getSingleMediaRules('image')
        );
    }
}
