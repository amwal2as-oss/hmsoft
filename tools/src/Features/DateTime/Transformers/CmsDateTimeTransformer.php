<?php

namespace HMsoft\Tools\Features\DateTime\Transformers;

use DateTimeInterface;
use HMsoft\Tools\Features\DateTime\Support\CmsDateTime;
use Spatie\LaravelData\Support\DataProperty;
use Spatie\LaravelData\Support\Transformation\TransformationContext;
use Spatie\LaravelData\Transformers\Transformer;

/**
 * Serializes all Spatie Data DateTime properties via CmsDateTime (UTC → API timezone).
 */
final class CmsDateTimeTransformer implements Transformer
{
    public function transform(DataProperty $property, mixed $value, TransformationContext $context): ?string
    {
        if (! $value instanceof DateTimeInterface) {
            return null;
        }

        return CmsDateTime::toApi($value);
    }
}
