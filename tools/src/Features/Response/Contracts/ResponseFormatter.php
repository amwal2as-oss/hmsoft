<?php

namespace HMsoft\Tools\Features\Response\Contracts;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

interface ResponseFormatter
{
    public function success(string $message = "", $data = [], int $code = 200, array $with = [], $pagination = null, $meta = null): Response|JsonResponse;

    public function error(string $message = "", int $state = 500, array $errors = [], string|null $errorCode = null, $meta = null): Response|JsonResponse;

    public function format(string $message, $data, array $errors, int $state, bool $success, array $with = [], $pagination = null, string|null $errorCode = null, $meta = null): Response|JsonResponse;
}
