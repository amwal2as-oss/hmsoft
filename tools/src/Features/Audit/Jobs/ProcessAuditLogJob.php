<?php

namespace HMsoft\Tools\Features\Audit\Jobs;

use HMsoft\Tools\Features\Audit\Models\AuditLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessAuditLogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $type,
        public int|string $id,
        public string $event,
        public array $old,
        public array $new,
        public array $context
    ) {}

    public function handle(): void
    {
        DB::transaction(function () {
            // Lock the latest row to prevent race conditions during concurrent writes
            $lastLog = AuditLog::lockForUpdate()->latest('id')->first();

            // Genesis block hash (if table is empty)
            $previousHash = $lastLog ? $lastLog->hash : str_repeat('0', 64);

            // Construct the payload for hashing exactly as it will be stored
            $payload = json_encode([
                'user_id' => $this->context['user_id'],
                'event' => $this->event,
                'auditable_type' => $this->type,
                'auditable_id' => $this->id,
                'old_values' => $this->old,
                'new_values' => $this->new,
                'ip_address' => $this->context['ip_address'],
                'user_agent' => $this->context['user_agent'],
                'session_id' => $this->context['session_id'],
                'previous_hash' => $previousHash,
            ]);

            $newHash = hash('sha256', $payload);

            AuditLog::create([
                'user_id' => $this->context['user_id'],
                'event' => $this->event,
                'auditable_type' => $this->type,
                'auditable_id' => $this->id,
                'old_values' => empty($this->old) ? null : $this->old,
                'new_values' => empty($this->new) ? null : $this->new,
                'ip_address' => $this->context['ip_address'],
                'user_agent' => $this->context['user_agent'],
                'session_id' => $this->context['session_id'],
                'previous_hash' => $previousHash,
                'hash' => $newHash,
            ]);
        });
    }
}
