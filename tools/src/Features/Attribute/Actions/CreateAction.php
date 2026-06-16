<?php

namespace HMsoft\Tools\Features\Attribute\Actions;

use HMsoft\Tools\Features\Attribute\Data\StoreAttributeData;
use HMsoft\Tools\Features\Attribute\Data\SyncAttributeImageData;
use HMsoft\Tools\Features\Attribute\Models\Attribute;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\Optional;

class CreateAction
{
    public function __construct(private readonly SyncImageAction $syncImageAction) {}

    public function execute(StoreAttributeData $data): Attribute
    {
        return DB::transaction(function () use ($data) {
            $createData = collect($data->toArray())
                ->except(['locales', 'options', 'image', 'delete_image'])
                ->reject(fn($value) => $value instanceof Optional)
                ->toArray();

            $createData['cast_type'] = match ($data->type) {
                'checkbox' => 'json',
                'select', 'radio', 'number' => 'integer',
                'boolean' => 'boolean',
                default => 'string',
            };

            $attribute = Attribute::create($createData);

            if (method_exists($attribute, 'syncTranslations')) {
                $attribute->syncTranslations($attribute, $data->locales);
            }

            if (in_array($data->type, ['select', 'radio', 'checkbox']) && !empty($data->options)) {
                foreach ($data->options as $optionData) {
                    $option = $attribute->options()->create([
                        'is_active' => $optionData->is_active ?? true,
                        'sort_number' => $optionData->sort_number ?? 0,
                    ]);
                    if (method_exists($option, 'syncTranslations')) {
                        $option->syncTranslations($option, $optionData->locales);
                    }
                }
            }

            $syncImageData = SyncAttributeImageData::from([
                'image' => $data->image ?? null,
                'delete_image' => false
            ]);

            $this->syncImageAction->execute($attribute, $syncImageData);

            return $attribute->load(Attribute::DEFAULT_INCLUDES);
        });
    }
}
