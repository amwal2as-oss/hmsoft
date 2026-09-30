<?php

namespace HMsoft\Tools\Features\MassPatch\Writers;

use Closure;
use HMsoft\Tools\Features\MassPatch\Contracts\MassPatchable;
use HMsoft\Tools\Features\MassPatch\Contracts\MassPatchWriter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EloquentMassPatchWriter implements MassPatchWriter
{
    public function write(Model&MassPatchable $model, array $values, ?Closure $constrain = null): int
    {
        $class = $model::class;
        $key = $model->getKeyName();
        $affected = 0;

        DB::transaction(function () use ($class, $key, $values, $constrain, $model, &$affected) {
            $query = $class::withoutGlobalScopes()->orderBy($key);

            $model->constrainMassPatchQuery($query);

            if ($constrain !== null) {
                $constrain($query);
            }

            $query->chunkById(100, function ($rows) use ($values, &$affected) {
                foreach ($rows as $row) {
                    $row->fill($values);
                    $row->save();
                    $affected++;
                }
            }, $key);
        });

        return $affected;
    }
}
