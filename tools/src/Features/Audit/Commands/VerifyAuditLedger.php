<?php

namespace HMsoft\Tools\Features\Audit\Commands;

use Illuminate\Console\Command;
use HMsoft\Tools\Features\Audit\Models\AuditLog;

class VerifyAuditLedger extends Command
{
    // This is the command you will type in the terminal
    protected $signature = 'audit:verify';
    protected $description = 'Cryptographically verify the integrity of the audit ledger.';

    public function handle()
    {
        $this->info('Starting Zero-Trust Ledger Verification...');

        // Get all logs in exact chronological order
        $logs = AuditLog::orderBy('id', 'asc')->get();

        // The genesis block always expects this starting hash
        $expectedPreviousHash = str_repeat('0', 64);

        $errorFound = false;

        foreach ($logs as $log) {
            // 1. Check if the chain link is broken (e.g. a row was deleted)
            if ($log->previous_hash !== $expectedPreviousHash) {
                $this->error("🚨 CRITICAL: Chain broken at Audit ID: {$log->id}. A previous record was deleted!");
                $errorFound = true;
                break;
            }

            // 2. Reconstruct the exact data payload to check for tampered JSON
            $payload = json_encode([
                'user_id' => $log->user_id,
                'event' => $log->event,
                'auditable_type' => $log->auditable_type,
                'auditable_id' => $log->auditable_id,
                'old_values' => $log->old_values,
                'new_values' => $log->new_values,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'session_id' => $log->session_id,
                'previous_hash' => $log->previous_hash,
            ]);

            // 3. Recalculate the hash mathematically
            $calculatedHash = hash('sha256', $payload);

            // 4. Compare it to what is saved in the database
            if ($calculatedHash !== $log->hash) {
                $this->error("🚨 CRITICAL: Data Tampering detected at Audit ID: {$log->id}!");
                $this->line("Expected Hash: {$log->hash}");
                $this->line("Actual Hash:   {$calculatedHash}");
                $errorFound = true;
                break;
            }

            // Set the expected hash for the next row in the loop
            $expectedPreviousHash = $log->hash;
        }

        if (!$errorFound) {
            $this->info('✅ Ledger integrity verified. 0 tampered records found.');
        }
    }
}
