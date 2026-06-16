<?php

namespace HMsoft\Tools\Features\Attribute\Actions;

use HMsoft\Tools\Features\Attribute\Data\UpdateAllAttributesData;
use HMsoft\Tools\Features\Attribute\Models\Attribute;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UpdateBulkAction
{
    public function __construct(private readonly UpdateAction $update_action) {}

    public function execute(UpdateAllAttributesData $data): Collection
    {
        return DB::transaction(function () use ($data) {
            $updated = collect();

            foreach ($data->attributes as $attributeData) {
                if (isset($attributeData->id)) {
                    $id = $attributeData->id instanceof \Spatie\LaravelData\Optional ? null : $attributeData->id;

                    if ($id) {
                        $attribute = Attribute::findOrFail($id);
                        $updated->push($this->update_action->execute($attribute, $attributeData));
                    }
                }
            }
            return $updated;
        });
    }
}
