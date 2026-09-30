<?php

namespace HMsoft\Tools\Features\MassPatch\Contracts;

use Closure;
use Illuminate\Database\Eloquent\Model;

interface MassPatchWriter
{
    /**
     * @param  Model&MassPatchable  $model
     * @param  array<string, mixed>  $values
     * @param  Closure(\Illuminate\Database\Eloquent\Builder): void|null  $constrain
     */
    public function write(Model&MassPatchable $model, array $values, ?Closure $constrain = null): int;
}
