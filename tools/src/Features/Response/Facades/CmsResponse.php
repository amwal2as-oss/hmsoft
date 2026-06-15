<?php

namespace HMsoft\Tools\Features\Response\Facades;

use Illuminate\Support\Facades\Facade;
use HMsoft\Tools\Features\Response\Contracts\ResponseFormatter;

/**
 * @method static \Illuminate\Http\JsonResponse success(string $message = "", $data = [], int $code = 200, array $with = [], $pagination = null, $meta = null)
 * @method static \Illuminate\Http\JsonResponse error(string $message = "", int $state = 500, array $errors = [], string|null $errorCode = null, $meta = null)
 * @method static \Illuminate\Http\JsonResponse format(string $message, $data, array $errors, int $state, bool $success, array $with = [], $pagination = null, string|null $errorCode = null, $meta = null)
 *
 * @see \HMsoft\Tools\Features\Response\Contracts\ResponseFormatter
 */
class CmsResponse extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ResponseFormatter::class;
    }
}
