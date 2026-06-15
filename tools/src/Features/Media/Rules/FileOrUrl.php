<?php

namespace HMsoft\Tools\Features\Media\Rules;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Closure;

class FileOrUrl implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // التحقق مما إذا كان ملفاً مرفوعاً صالحاً
        if ($value instanceof UploadedFile && $value->isValid()) {
            return;
        }

        // التحقق مما إذا كان نصاً يمثل رابطاً (URL) صالحاً
        if (is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
            return;
        }

        // إذا لم يكن أي منهما، نرفض العملية مع رسالة الخطأ من ملفات الترجمة
        $fail(__('media::validation.file_or_url', ['attribute' => $attribute]));
    }
}
