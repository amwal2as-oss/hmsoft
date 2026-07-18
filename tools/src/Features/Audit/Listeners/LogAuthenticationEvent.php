<?php

namespace HMsoft\Tools\Features\Audit\Listeners;

use HMsoft\Tools\Features\Audit\Jobs\ProcessAuditLogJob;
use HMsoft\Tools\Features\Audit\Support\AuditConfig;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Request;

class LogAuthenticationEvent
{
    /**
     * Handle the incoming Auth event.
     */
    public function handle(Login|Failed|Logout $event): void
    {
        if (! AuditConfig::shouldLogAuthentication()) {
            return;
        }

        // 1. Determine what happened
        $action = match (true) {
            $event instanceof Login => 'logged_in',
            $event instanceof Failed => 'login_failed',
            $event instanceof Logout => 'logged_out',
            default => 'unknown_auth_event',
        };

        // 2. Determine who tried to do it
        // Note: For a Failed login, the user might not exist, or they typed the wrong password.
        $user = $event->user;
        $userId = $user ? $user->id : null;

        // If the user exists, use their ID for the polymorphic relation. 
        // If not, we just log a system event with ID 0.
        $auditableId = $userId ?? 0;

        // 3. Build the Zero-Trust Context
        $context = [
            'user_id' => $userId,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'session_id' => session()->getId(),
        ];

        // 4. Capture what credentials they tried (NEVER log the plain text password)
        $credentialsUsed = [];
        if ($event instanceof Failed) {
            $credentialsUsed = [
                'attempted_email' => $event->credentials['email'] ?? 'unknown',
            ];
        }

        // 5. Dispatch it to our Hash-Chained background queue!
        ProcessAuditLogJob::dispatch(
            'users',            // The auditable_type alias
            $auditableId,       // The auditable_id
            $action,            // 'logged_in', 'login_failed', etc.
            [],                 // old_values (not applicable here)
            $credentialsUsed,   // new_values (useful to see what email a hacker tried)
            $context
        );
    }
}
