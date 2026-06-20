<?php

namespace HMsoft\Tools\Features\Media\Data;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class UpdateMediaData extends Data
{
    public function __construct(
        public readonly Optional|int $id,
        public readonly Optional|array $locales,
        public readonly Optional|int $sort_number,
        public readonly Optional|array $custom_properties,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        $fullPayload = $context->fullPayload;
        $isNewMedia = empty($fullPayload['id']);

        return [
            'id'                        => ['sometimes', 'required', 'integer', 'exists:media,id'],
            'sort_number'               => ['sometimes', 'integer'],
            'custom_properties'         => ['sometimes', 'array'],
            'locales'                   => [$isNewMedia ? 'required' : 'sometimes', 'array', 'min:1'],
            'locales.*.locale'          => ['required_with:locales'],
            'locales.*.title'           => [
                $isNewMedia ? 'required' : 'sometimes',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($fullPayload) {
                    $pathParts = explode('.', $attribute);
                    if ($pathParts[0] === 'locales') {
                        $localeIndex = $pathParts[1];
                        $currentLocale = Arr::get($fullPayload, "locales.{$localeIndex}.locale");

                        $routeParam = Request::route('medium');
                        $mediaId = $fullPayload['id'] ?? (is_object($routeParam) ? $routeParam->id : $routeParam);

                        if ($currentLocale) {
                            $query = DB::table('media_translations')
                                ->where('title', $value)
                                ->where('locale', $currentLocale);

                            if ($mediaId) {
                                $query->where('media_id', '!=', $mediaId);
                            }

                            if ($query->exists()) {
                                $fail(trans('validation.unique', ['attribute' => 'title']));
                            }
                        }
                    }
                }
            ],
            'locales.*.alt'             => ['sometimes', 'nullable', 'string', 'max:255'],
            'locales.*.description'     => ['sometimes', 'nullable', 'string'],
        ];
    }
}
