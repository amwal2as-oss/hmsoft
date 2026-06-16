<?php

namespace HMsoft\Tools\Features\Attribute\Actions;

use HMsoft\Tools\Features\Attribute\Models\Attribute;
use Illuminate\Support\Facades\DB;

class DeleteAction
{
    public function executeSingle(Attribute $attribute): bool
    {
        return $attribute->delete();
    }

    public function executeBulk(array $ids): bool
    {
        return DB::transaction(fn() => Attribute::whereIn('id', $ids)->delete() > 0);
    }
}
