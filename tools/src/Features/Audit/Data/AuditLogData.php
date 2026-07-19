<?php

namespace HMsoft\Tools\Features\Audit\Data;

use HMsoft\Tools\Features\Audit\Models\AuditLog;
use HMsoft\Tools\Features\DateTime\Support\CmsDateTime;
use HMsoft\Tools\Features\DynamicFilters\Data\BaseData;

class AuditLogData extends BaseData
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?int $user_id,
        public readonly ?string $event,
        public readonly ?string $auditable_type,
        public readonly ?string $auditable_id,
        public readonly ?array $old_values,
        public readonly ?array $new_values,
        public readonly ?string $ip_address,
        public readonly ?string $user_agent,
        public readonly ?string $session_id,
        public readonly ?string $previous_hash,
        public readonly ?string $hash,
        public readonly ?string $created_at,
        public readonly ?array $actor, // Holds the partial User data
    ) {}

    public static function fromModel(AuditLog $log): self
    {
        // Safely extract the actor details only if the relationship was loaded in the query
        $actor = null;
        if ($log->relationLoaded('user') && $log->user) {
            $actor = [
                'id' => $log->user->id,
                'first_name' => $log->user->first_name,
                'last_name' => $log->user->last_name,
                'email' => $log->user->email,
            ];
        }

        return new self(
            id: $log->id,
            user_id: $log->user_id,
            event: $log->event,
            auditable_type: $log->auditable_type,
            auditable_id: (string) $log->auditable_id, // Cast to string in case it is a UUID
            old_values: CmsDateTime::transformArray($log->old_values),
            new_values: CmsDateTime::transformArray($log->new_values),
            ip_address: $log->ip_address,
            user_agent: $log->user_agent,
            session_id: $log->session_id,
            previous_hash: $log->previous_hash,
            hash: $log->hash,
            created_at: CmsDateTime::toApi($log->created_at),
            actor: $actor,
        );
    }
}
