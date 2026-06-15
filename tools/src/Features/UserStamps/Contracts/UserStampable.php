<?php

namespace HMsoft\Tools\Features\UserStamps\Contracts;

interface UserStampable
{
    public function getCreatedByColumn(): ?string;
    public function getUpdatedByColumn(): ?string;
    public function getDeletedByColumn(): ?string;

    /**
     * تحديد هوية المستخدم الذي قام بالإجراء.
     */
    public function getStampUserId(): int|string|null;
}
