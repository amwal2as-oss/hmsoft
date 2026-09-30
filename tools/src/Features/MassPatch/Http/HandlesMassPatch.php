<?php

namespace HMsoft\Tools\Features\MassPatch\Http;

use Closure;
use HMsoft\Tools\Features\MassPatch\Actions\MassPatchAction;
use HMsoft\Tools\Features\MassPatch\Contracts\MassPatchable;
use HMsoft\Tools\Features\Response\Facades\CmsResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait HandlesMassPatch
{
    /**
     * @param  Model&MassPatchable  $model
     * @param  Closure(\Illuminate\Database\Eloquent\Builder): void|null  $constrain
     */
    protected function massPatchResponse(Model&MassPatchable $model, Request $request, ?Closure $constrain = null): JsonResponse
    {
        $payload = $request->json()->all();
        if ($payload === []) {
            $payload = $request->all();
        }

        $result = app(MassPatchAction::class)->execute($model, $payload, $constrain);

        return CmsResponse::success(
            message: __(config('mass_patch.messages.updated', 'mass_patch.updated')),
            data: ['affected' => $result['affected']],
        );
    }
}
