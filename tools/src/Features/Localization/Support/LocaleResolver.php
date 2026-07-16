<?php

namespace HMsoft\Tools\Features\Localization\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LocaleResolver
{
    /**
     * Resolve the request locale using the same priority engine as SetLocaleMiddleware.
     *
     * Safe to call before middleware runs (e.g. route model binding by slug).
     */
    public static function resolve(?Request $request = null): string
    {
        $request ??= request();

        $supportedLocales = config('cms_localization.supported_locales', ['en']);
        $fallbackLocale   = config('cms_localization.fallback_locale', 'en');

        if (! $request) {
            return App::getLocale() ?: $fallbackLocale;
        }

        return self::determineFromRequest($request, $supportedLocales, $fallbackLocale);
    }

    /**
     * Resolve and apply the locale to the application container.
     */
    public static function apply(?Request $request = null): string
    {
        $locale = self::resolve($request);
        App::setLocale($locale);

        if ($request?->hasSession()) {
            Session::put(
                config('cms_localization.detectors.session_key', 'locale'),
                $locale
            );
        }

        return $locale;
    }

    public static function determineFromRequest(
        Request $request,
        ?array $supportedLocales = null,
        ?string $fallbackLocale = null
    ): string {
        $supportedLocales ??= config('cms_localization.supported_locales', ['en']);
        $fallbackLocale   ??= config('cms_localization.fallback_locale', 'en');
        $config           = config('cms_localization.detectors', []);

        $routeLocale = $request->route($config['route_parameter'] ?? 'locale');
        if ($routeLocale && in_array($routeLocale, $supportedLocales, true)) {
            return $routeLocale;
        }

        $acceptLang = $request->header($config['header'] ?? 'Accept-Language');
        if ($acceptLang) {
            foreach (explode(',', $acceptLang) as $lang) {
                $localeCode = strtolower(trim(explode(';', $lang)[0]));
                $shortLocaleCode = substr($localeCode, 0, 2);

                if (in_array($shortLocaleCode, $supportedLocales, true)) {
                    return $shortLocaleCode;
                }
            }
        }

        if ($request->hasSession()) {
            $sessionLocale = Session::get($config['session_key'] ?? 'locale');
            if ($sessionLocale && in_array($sessionLocale, $supportedLocales, true)) {
                return $sessionLocale;
            }
        }

        return $fallbackLocale;
    }
}
