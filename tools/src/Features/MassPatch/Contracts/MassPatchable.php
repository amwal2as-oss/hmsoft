<?php

namespace HMsoft\Tools\Features\MassPatch\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface MassPatchable
{
    /**
     * @return list<string>
     */
    public function defineMassPatchableAttributes(): array;

    /**
     * @return array<string, mixed>
     */
    public function defineMassPatchRules(): array;

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function prepareMassPatch(array $values): array;

    /**
     * @param  array<string, mixed>  $values
     */
    public function authorizeMassPatch(array $values): void;

    public function constrainMassPatchQuery(Builder $query): void;
}
