<?php

namespace HMsoft\Tools\Features\DateTime\Controllers;

use HMsoft\Tools\Features\DateTime\Actions\ConvertDateTimeAction;
use HMsoft\Tools\Features\DateTime\Actions\GetDateTimeConfigAction;
use HMsoft\Tools\Features\DateTime\Actions\GetDateTimeNowAction;
use HMsoft\Tools\Features\Response\Facades\CmsResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DateTimeController
{
    public function __construct(
        private readonly GetDateTimeConfigAction $configAction,
        private readonly GetDateTimeNowAction $nowAction,
        private readonly ConvertDateTimeAction $convertAction,
    ) {}

    /**
     * Current datetime configuration and resolved API timezone.
     */
    public function config(): JsonResponse
    {
        return CmsResponse::success(
            data: $this->configAction->execute()
        );
    }

    /**
     * Current server time in storage (UTC) and resolved API timezone.
     */
    public function now(): JsonResponse
    {
        return CmsResponse::success(
            data: $this->nowAction->execute()
        );
    }

    /**
     * Convert a datetime string between storage (UTC) and API timezone.
     *
     * Body: { "value": "...", "direction": "to_api"|"to_storage" }
     */
    public function convert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'value' => ['required', 'string'],
            'direction' => ['required', 'string', 'in:to_api,to_storage,to_utc'],
        ]);

        return CmsResponse::success(
            data: $this->convertAction->execute(
                value: $validated['value'],
                direction: $validated['direction'],
            )
        );
    }
}
