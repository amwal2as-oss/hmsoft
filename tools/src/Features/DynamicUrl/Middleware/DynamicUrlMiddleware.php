<?php

namespace HMsoft\Tools\Features\DynamicUrl\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DynamicUrlMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('cms_dynamicUrl.enabled', false)) {

            $originalAppUrl = config('app.url');

            $host = $request->getHost();
            $scheme = $request->getScheme();
            $port = $request->getPort();
            $basePath = parse_url($originalAppUrl, PHP_URL_PATH) ?? '';

            $dynamicAppUrl = "$scheme://$host";
            if (!in_array($port, [80, 443])) {
                $dynamicAppUrl .= ":$port";
            }
            $dynamicAppUrl .= $basePath;

            // 1. مصفوفة الإعدادات الديناميكية
            $dynamicConfigs = [
                'app.url' => $dynamicAppUrl,
                'reverb.apps.apps.0.options.host' => $host,
                'reverb.apps.apps.0.options.port' => $port,
                'reverb.apps.apps.0.options.scheme' => $scheme,
                'reverb.apps.apps.0.options.useTLS' => $scheme === 'https',
            ];

            foreach (config('filesystems.disks', []) as $diskName => $diskConfig) {
                if (isset($diskConfig['url']) && is_string($diskConfig['url'])) {
                    $dynamicConfigs["filesystems.disks.{$diskName}.url"] = str_replace(
                        $originalAppUrl,
                        $dynamicAppUrl,
                        $diskConfig['url']
                    );
                }
            }

            config($dynamicConfigs);
        }

        return $next($request);
    }
}
