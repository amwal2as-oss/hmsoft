<?php

namespace HMsoft\Tools\Features\MassPatch\Traits;

use HMsoft\Tools\Features\MassPatch\Support\FieldTypeResolver;
use HMsoft\Tools\Features\MassPatch\Support\RuleBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

trait IsMassPatchable
{
    /**
     * @return list<string>
     */
    public function defineMassPatchableAttributes(): array
    {
        return array_values(array_unique(array_diff(
            array_merge(
                $this->getMassPatchableBase(),
                $this->getMassPatchableExtra()
            ),
            $this->getMassPatchDeniedBase(),
            $this->getMassPatchDeniedExtra()
        )));
    }

    /**
     * @return array<string, mixed>
     */
    public function defineMassPatchRules(): array
    {
        return array_replace(
            RuleBuilder::for($this),
            $this->getMassPatchRulesExtra()
        );
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function prepareMassPatch(array $values): array
    {
        return $values;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function authorizeMassPatch(array $values): void
    {
    }

    public function constrainMassPatchQuery(Builder $query): void
    {
    }

    /**
     * Boolean and date/datetime columns discovered from casts.
     *
     * @return list<string>
     */
    protected function getMassPatchableBase(): array
    {
        $fromCasts = collect(FieldTypeResolver::fromModel($this))
            ->filter(fn (string $type) => FieldTypeResolver::isPatchableByDefault($type))
            ->keys();

        $columns = $this->massPatchTableColumns();

        if ($columns !== []) {
            $fromCasts = $fromCasts->intersect($columns);
        }

        return $fromCasts->values()->all();
    }

    /**
     * Extra fields that are not bool/date (e.g. sort_number).
     *
     * @return list<string>
     */
    protected function getMassPatchableExtra(): array
    {
        return [];
    }

    /**
     * @return list<string>
     */
    protected function getMassPatchDeniedBase(): array
    {
        return [
            'id',
            'created_at',
            'updated_at',
            'deleted_at',
            'created_by',
            'updated_by',
            'password',
            'remember_token',
            'two_fa_secret',
            'two_fa_email_code',
            'two_fa_email_expires_at',
        ];
    }

    /**
     * Fields discovered by Base that must not be mass-patched.
     *
     * @return list<string>
     */
    protected function getMassPatchDeniedExtra(): array
    {
        return [];
    }

    /**
     * Per-field validation overrides. Keys are unprefixed field names.
     *
     * @return array<string, mixed>
     */
    protected function getMassPatchRulesExtra(): array
    {
        return [];
    }

    /**
     * @return list<string>
     */
    protected function massPatchTableColumns(): array
    {
        try {
            if (method_exists($this, 'getCachedTableColumns')) {
                return array_values($this->getCachedTableColumns($this->getTable()));
            }

            return Schema::getColumnListing($this->getTable());
        } catch (\Throwable) {
            return [];
        }
    }
}
