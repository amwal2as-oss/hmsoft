<?php

namespace HMsoft\Tools\Features\SortNumber\Contracts;

interface Sortable
{
    /**
     * تحديد اسم الحقل المسؤول عن الترتيب في قاعدة البيانات.
     *
     * @return string
     */
    public function getSortNumberColumnName(): string;
}
