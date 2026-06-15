<?php

namespace HMsoft\Tools\Features\Response\Services;

use HMsoft\Tools\Features\Response\Contracts\ResponseFormatter;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CmsResponse implements ResponseFormatter
{
    public function success(string $message = "", $data = [], int $code = 200, array $with = [], $pagination = null, $meta = null): Response|JsonResponse
    {
        return $this->format(message: $message, data: $data, errors: [], state: $code, success: true, with: $with, pagination: $pagination, meta: $meta);
    }

    public function error(string $message = "", int $state = 500, array $errors = [], string|null $errorCode = null, $meta = null): Response|JsonResponse
    {
        return $this->format(message: $message, data: [], errors: $errors, state: $state, success: false, errorCode: $errorCode, meta: $meta);
    }

    public function format(string $message, $data, array $errors, int $state, bool $success, array $with = [], $pagination = null, string|null $errorCode = null, $meta = null): Response|JsonResponse
    {
        return response()->json([
            "message"    => $message,
            "data"       => $data,
            "errors"     => $errors,
            "error_code" => $errorCode,
            "state"      => $state,
            "success"    => $success,
            "pagination" => $pagination,
            "meta"       => $meta,
            ...$with
        ], $state);
    }
}
