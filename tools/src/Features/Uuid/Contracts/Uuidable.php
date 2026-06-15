<?php

namespace HMsoft\Tools\Features\Uuid\Contracts;

interface Uuidable
{
    /**
     * تحديد اسم الحقل الذي سيتم حقن الـ UUID فيه.
     */
    public function getUuidColumnName(): string;

    /**
     * تحديد طريقة توليد الـ UUID (مفيد لتغيير الإصدار V4 إلى V7 أو Ordered).
     */
    public function generateUuid(): string;
}
