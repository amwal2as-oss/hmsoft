<?php

namespace HMsoft\Tools\Features\Active\Contracts;

interface Activable
{
    /**
     * تحديد اسم الحقل المسؤول عن حالة التفعيل في قاعدة البيانات.
     *
     * @return string
     */
    public function getActiveColumnName(): string;

    /**
     * تحديد ما إذا كان يجب تطبيق النطاق العام (Global Scope) تلقائياً.
     *
     * @return bool
     */
    public function shouldApplyActiveScope(): bool;
}
