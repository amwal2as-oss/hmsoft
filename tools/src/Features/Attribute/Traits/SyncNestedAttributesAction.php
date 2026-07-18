<?php

namespace HMsoft\Tools\Features\Attribute\Traits;

use HMsoft\Tools\Features\Attribute\Services\EavValueSyncService;
use Illuminate\Database\Eloquent\Model;

/**
 * Backward-compatible wrapper. Prefer syncEavAttributes() on the model.
 */
class SyncNestedAttributesAction
{
    public function __construct(
        private readonly ?EavValueSyncService $syncService = null
    ) {}

    private function syncService(): EavValueSyncService
    {
        return $this->syncService ?? app(EavValueSyncService::class);
    }

    public function execute(Model $owner, array $attributesData): void
    {
        $normalized = collect($attributesData)->map(function ($item) {
            if (! is_array($item)) {
                return $item;
            }

            // Legacy payloads used attribute_id + locale + value
            if (isset($item['attribute_id']) && ! isset($item['code'])) {
                return $item;
            }

            return $item;
        })->all();

        $this->syncService()->sync($owner, $normalized);
    }
}
