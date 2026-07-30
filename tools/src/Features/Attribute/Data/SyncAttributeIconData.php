<?php

namespace HMsoft\Tools\Features\Attribute\Data;

use HMsoft\Tools\Features\Media\Traits\InteractsWithMediaRules;
use Spatie\LaravelData\Data;

class SyncAttributeIconData extends Data
{
    use InteractsWithMediaRules;

    public function __construct(
        public readonly ?bool $delete_icon = null,
        public readonly mixed $icon,
    ) {}

    public static function rules(): array
    {
        return array_merge(
            ['icon' => ['required']],
            self::getSingleMediaRules('icon')
        );
    }
}
