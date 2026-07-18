<?php

namespace HMsoft\Tools\Features\Attribute\Traits;

use HMsoft\Tools\Features\Attribute\Actions\GetObjectAttributesAction;
use HMsoft\Tools\Features\Attribute\Models\EavValue;
use HMsoft\Tools\Features\Attribute\Services\EavFilterRegistrar;
use HMsoft\Tools\Features\Attribute\Services\EavValueSyncService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasEavAttributes
{
    public static function bootHasEavAttributes(): void
    {
        static::deleting(function (Model $model) {
            if (! method_exists($model, 'eavValues')) {
                return;
            }

            $model->eavValues()->each(function (EavValue $value) {
                $value->translations()->delete();
                $value->selectedOptions()->delete();
                $value->delete();
            });
        });
    }

    public function eavValues(): MorphMany
    {
        return $this->morphMany(EavValue::class, 'valuable');
    }

    /** @deprecated Use eavValues() */
    public function attributeValues(): MorphMany
    {
        return $this->eavValues();
    }

    public function syncEavAttributes(array $payload): void
    {
        app(EavValueSyncService::class)->sync($this, $payload);
    }

    /**
     * Load attribute definitions with resolved values for this model instance.
     */
    public function getEavAttributesWithValues(?string $categoryType = null, ?int $categoryId = null): array
    {
        return app(GetObjectAttributesAction::class)->execute(
            entityType: $this->eavEntityType(),
            valuableId: $this->getKey(),
            categoryType: $categoryType,
            categoryId: $categoryId,
        )->map(fn (array $row) => [
            'attribute_id' => $row['attribute']->id,
            'code' => $row['attribute']->code,
            'input_type' => $row['attribute']->input_type?->value ?? $row['attribute']->input_type,
            'value' => $row['presented_value'],
            'value_id' => $row['value']?->id,
        ])->values()->all();
    }

    public function eavEntityType(): string
    {
        return method_exists($this, 'getMorphClass')
            ? $this->getMorphClass()
            : $this->getTable();
    }

    protected function getEavFilterableExtra(): array
    {
        return EavFilterRegistrar::filterableKeysForEntity($this->eavEntityType());
    }

    protected function getEavSortableExtra(): array
    {
        return EavFilterRegistrar::sortableKeysForEntity($this->eavEntityType());
    }
}
