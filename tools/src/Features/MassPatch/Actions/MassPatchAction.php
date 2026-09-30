<?php

namespace HMsoft\Tools\Features\MassPatch\Actions;

use Closure;
use HMsoft\Tools\Features\MassPatch\Contracts\MassPatchable;
use HMsoft\Tools\Features\MassPatch\Contracts\MassPatchWriter;
use HMsoft\Tools\Features\MassPatch\Data\MassPatchRequestData;
use HMsoft\Tools\Features\MassPatch\Support\ScopeGuard;
use HMsoft\Tools\Features\MassPatch\Support\ValueCoercer;
use Illuminate\Database\Eloquent\Model;

class MassPatchAction
{
    public function __construct(
        private readonly MassPatchWriter $writer,
        private readonly ValueCoercer $coercer,
    ) {}

    /**
     * @param  Model&MassPatchable  $model
     * @param  array<string, mixed>  $payload
     * @param  Closure(\Illuminate\Database\Eloquent\Builder): void|null  $constrain
     * @return array{affected: int}
     */
    public function execute(Model&MassPatchable $model, array $payload, ?Closure $constrain = null): array
    {
        ScopeGuard::assert($payload);

        $values = $this->coercer->coerce($model, $payload);
        $values = $model->prepareMassPatch($values);

        $data = MassPatchRequestData::fromPayload($values, $model);

        $model->authorizeMassPatch($data->values);

        $affected = $this->writer->write($model, $data->values, $constrain);

        return ['affected' => $affected];
    }
}
