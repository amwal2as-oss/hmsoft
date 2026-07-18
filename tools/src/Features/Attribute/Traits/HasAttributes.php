<?php

namespace HMsoft\Tools\Features\Attribute\Traits;

/**
 * @deprecated Use HasEavAttributes
 */
trait HasAttributes
{
    use HasEavAttributes;

    public static function bootHasAttributes(): void
    {
        static::bootHasEavAttributes();
    }
}
