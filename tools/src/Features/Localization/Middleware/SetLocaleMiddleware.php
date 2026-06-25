<?php

namespace HMsoft\Tools\Features\Localization\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleMiddleware
{
    /**
     * Handle an incoming request to set the application's locale.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. جلب اللغات المدعومة واللغة الافتراضية من الإعدادات
        $supportedLocales = config('cms_localization.supported_locales', ['en']);
        $fallbackLocale   = config('cms_localization.fallback_locale', 'en');

        // 2. تحديد اللغة المناسبة
        $locale = $this->determineLocale($request, $supportedLocales, $fallbackLocale);

        // 3. تعيين لغة التطبيق
        App::setLocale($locale);

        // 4. حفظ اللغة في الجلسة (لطلبات الويب)
        if ($request->hasSession()) {
            Session::put(config('cms_localization.detectors.session_key', 'locale'), $locale);
        }

        return $next($request);
    }

    /**
     * Determine the locale from the request based on priority.
     */
    protected function determineLocale(Request $request, array $supportedLocales, string $fallbackLocale): string
    {
        $config = config('cms_localization.detectors');

        /* 1. التحقق من الرابط (URL Segment) */
        $routeLocale = $request->route($config['route_parameter']);
        if ($routeLocale && in_array($routeLocale, $supportedLocales)) {
            return $routeLocale;
        }

        /* 2. التحقق من هيدر الطلب (Accept-Language) - مفيد جداً للـ API */
        $acceptLang = $request->header($config['header']);
        if ($acceptLang) {
            foreach (explode(',', $acceptLang) as $lang) {
                $localeCode = strtolower(trim(explode(';', $lang)[0]));
                // استخراج أول حرفين فقط (مثلاً en-US تصبح en)
                $shortLocaleCode = substr($localeCode, 0, 2);

                if (in_array($shortLocaleCode, $supportedLocales)) {
                    return $shortLocaleCode;
                }
            }
        }

        /* 3. التحقق من الجلسة (Session) */
        if ($request->hasSession()) {
            $sessionLocale = Session::get($config['session_key']);
            if ($sessionLocale && in_array($sessionLocale, $supportedLocales)) {
                return $sessionLocale;
            }
        }

        /* 4. إرجاع اللغة الافتراضية */
        return $fallbackLocale;
    }
}
