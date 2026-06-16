<?php

namespace HMsoft\Tools\Features\Attribute\Actions;

use HMsoft\Tools\Features\Attribute\Data\SyncAttributeImageData;
use HMsoft\Tools\Features\Attribute\Data\UpdateAttributeData;
use HMsoft\Tools\Features\Attribute\Models\Attribute;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\Optional;

class UpdateAction
{
    public function __construct(private readonly SyncImageAction $syncImageAction) {}

    public function execute(Attribute $attribute, UpdateAttributeData $data): Attribute
    {
        return DB::transaction(function () use ($attribute, $data) {
            $updateData = collect($data->toArray())
                ->except(['locales', 'options', 'id', 'image', 'delete_image'])
                ->reject(fn($value) => $value instanceof Optional)
                ->toArray();

            if (isset($updateData['type'])) {
                $updateData['cast_type'] = match ($updateData['type']) {
                    'checkbox' => 'json',
                    'select', 'radio', 'number' => 'integer',
                    'boolean' => 'boolean',
                    default => 'string',
                };
            }

            if (!empty($updateData)) {
                $attribute->update($updateData);
            }

            $locales = $data->locales instanceof Optional ? null : $data->locales;
            if ($locales !== null && method_exists($attribute, 'syncTranslations')) {
                $attribute->syncTranslations($attribute, $locales);
            }

            $optionsData = $data->options instanceof Optional ? null : $data->options;
            if ($optionsData !== null && in_array($attribute->type, ['select', 'radio', 'checkbox'])) {
                $keepOptionIds = [];
                foreach ($optionsData as $optData) {
                    $optArray = is_array($optData) ? $optData : $optData->toArray();

                    if (isset($optArray['id']) && $optArray['id']) {
                        $option = $attribute->options()->findOrFail($optArray['id']);
                        $option->update(collect($optArray)->except(['locales', 'id'])->reject(fn($v) => $v instanceof Optional)->toArray());
                    } else {
                        $option = $attribute->options()->create(collect($optArray)->except(['locales', 'id'])->reject(fn($v) => $v instanceof Optional)->toArray());
                    }

                    $keepOptionIds[] = $option->id;

                    if (isset($optArray['locales']) && method_exists($option, 'syncTranslations')) {
                        $option->syncTranslations($option, $optArray['locales']);
                    }
                }
                $attribute->options()->whereNotIn('id', $keepOptionIds)->delete();
            } elseif (isset($updateData['type']) && !in_array($updateData['type'], ['select', 'radio', 'checkbox'])) {
                $attribute->options()->delete();
            }

            $imageFile = $data->image instanceof Optional ? null : $data->image;
            $shouldDelete = $data->delete_image instanceof Optional ? false : (bool)$data->delete_image;
            $syncImageData = SyncAttributeImageData::from([
                'image' => $imageFile,
                'delete_image' => $shouldDelete
            ]);
            $this->syncImageAction->execute($attribute, $syncImageData);

            return $attribute->refresh()->load(Attribute::DEFAULT_INCLUDES);
        });
    }
}
