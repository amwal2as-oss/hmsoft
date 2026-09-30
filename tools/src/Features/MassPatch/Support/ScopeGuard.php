<?php

namespace HMsoft\Tools\Features\MassPatch\Support;

use Illuminate\Validation\ValidationException;

final class ScopeGuard
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public static function assert(array $payload): void
    {
        if ($payload === [] || array_is_list($payload)) {
            throw ValidationException::withMessages([
                'payload' => [__(config('mass_patch.messages.values_required', 'mass_patch.values_required'))],
            ]);
        }
    }
}
