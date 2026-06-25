<?php

namespace HMsoft\Tools\Features\Media\Actions;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StreamExternalFileAction
{
    /**
     * @param string $fileUrl الرابط الخارجي للملف
     * @param string|null $fallbackName اسم الملف في حال تعذر استخراجه من الرابط
     */
    public function execute(string $fileUrl, ?string $fallbackName = 'downloaded_file')
    {
        try {
            // 1. استخراج اسم الملف
            $fileName = Str::afterLast(parse_url($fileUrl, PHP_URL_PATH), '/') ?: $fallbackName;

            // 2. جلب الـ Headers فقط باستخدام (HEAD Request) لتجنب تحميل الملف في الرام
            $headResponse = Http::timeout(10)->head($fileUrl);

            if (!$headResponse->successful()) {
                return response()->json(['error' => 'Unable to access the file from the provided URL.'], 404);
            }

            // 3. استخراج الـ Headers الديناميكية
            $contentType = $headResponse->header('Content-Type') ?: 'application/octet-stream';
            $contentLength = $headResponse->header('Content-Length');

            // 4. تجهيز مصفوفة الـ Headers التي سيتم إرسالها للمتصفح
            $headers = [
                'Content-Type' => $contentType,
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                // هيدر Content-Disposition يجبر المتصفح على التحميل مع إعطائه اسم الملف
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            ];

            // إذا كان السيرفر الخارجي يوفر حجم الملف، نمرره لكي يظهر شريط التحميل للمستخدم
            if ($contentLength) {
                $headers['Content-Length'] = $contentLength;
                $headers['Access-Control-Expose-Headers'] = 'Content-Length';
            }

            // 5. إرجاع StreamedResponse للبث المباشر (أعلى أداء للسيرفر)
            return response()->streamDownload(function () use ($fileUrl) {

                // فتح اتصال للقراءة من الرابط الخارجي
                $in = fopen($fileUrl, 'rb');
                // فتح اتصال للكتابة وإرسال البيانات للمستخدم
                $out = fopen('php://output', 'wb');

                if ($in !== false && $out !== false) {
                    // نقل البيانات على دفعات (Chunks) بدون استنزاف الرام
                    stream_copy_to_stream($in, $out);
                }

                if ($in) fclose($in);
                if ($out) fclose($out);
            }, $fileName, $headers);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }
}
