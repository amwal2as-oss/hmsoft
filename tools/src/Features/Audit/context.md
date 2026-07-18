# Zero-Trust Security Audit Ledger

> **Note:** This file is legacy context. For the full developer reference, see [README.md](./README.md) and the [docs/](./docs/) folder.

## Overview

The Security Audit module is an enterprise-grade, cryptographically verifiable logging system designed for the Syrian Precious Metals Authority (PMA). It adheres to a strict Zero-Trust architecture.

Rather than just keeping a history of changes, this system creates an **Immutable Hash Chain**. Every action is mathematically bound to the action before it using SHA-256 hashing. If a rogue database administrator, hacker, or compromised system process directly alters the database via raw SQL, the cryptographic chain breaks, and the system's verifiers will instantly detect the tampering.

---

# Feature-Driven Architecture (FDD)

This module is entirely self-contained within:

```bash
app/Features/Audit
```

It encapsulates its own Models, Traits, Jobs, Providers, and Console Commands.

---

# Core Components

## 1. AuditLog Model & Migration

Stores:

- Polymorphic relations:
    - `auditable_type`
    - `auditable_id`
- Event type
- JSON payloads:
    - `old_values`
    - `new_values`
- User context:
    - IP Address
    - User Agent
- Cryptographic hashes

---

## 2. Auditable Trait

Added to any Eloquent model (example: `User`).

It automatically listens for:

- `created`
- `updated`
- `deleted`

events and dispatches a background job to record changes.

---

## 3. ProcessAuditLogJob

A queued background job responsible for:

- Generating the SHA-256 hash
- Locking the database row during insertion
- Preventing race conditions

---

## 4. AuditServiceProvider

The bootstrap provider for the feature.

Responsibilities:

- Dynamically scans FDD folders for models
- Caches Morph Maps for performance
- Registers security console commands

---

# Developer Rules & Usage

## 1. How to Audit a New Model

Whenever you create a new model in any Feature domain (example: `Invoice`, `GoldPrice`), you must do exactly **two things**.

---

### Step 1: Add the `Auditable` Trait

```php
use HMsoft\Tools\Features\Audit\Traits\Auditable;
```

Then:

```php
use Auditable;
```

---

### Step 2: Define `getMorphClass()`

This assigns a secure database-safe alias and prevents full namespaces from leaking into the database.

## Example Model

```php
<?php

namespace App\Features\Pricing\Models;

use Illuminate\Database\Eloquent\Model;
use HMsoft\Tools\Features\Audit\Traits\Auditable;

class GoldPrice extends Model
{
    use Auditable;

    /**
     * MANDATORY:
     * Defines the alias used by the database and Morph Map.
     */
    public function getMorphClass()
    {
        return 'gold_prices';
    }
}
```

---

## Important Note

Because the `AuditServiceProvider` caches Morph Maps for performance, you must clear cache after adding a new audited model:

```bash
php artisan cache:clear
```

---

# 2. Never Bypass Eloquent

The `Auditable` trait relies entirely on Eloquent model events.

## ❌ Incorrect

```php
DB::table('users')->update([
    'role' => 'admin'
]);
```

This bypasses audit logging completely.

---

## ✅ Correct

```php
$user = User::find(1);

$user->role = 'admin';

$user->save();
```

Always use Eloquent models when modifying audited data.

---

# Verification Commands (The Alarm System)

The system includes two critical Artisan commands designed to detect tampering.

In production, both commands should be automated through Laravel Scheduler.

---

# 1. Ledger Integrity Verifier

## Command

```bash
php artisan audit:verify
```

## What It Does

Scans the `audit_logs` table from the Genesis Block to the latest entry.

For every row it:

1. Recalculates the SHA-256 hash
2. Compares it against the stored hash

---

## What It Detects

- Deleted audit rows
- Modified audit rows
- Tampering inside the `audit_logs` table itself
- Attempted cover-ups by attackers or rogue administrators

If tampering is detected, the command fails and identifies the exact corrupted row.

---

# 2. State-Match Verifier

## Command

```bash
php artisan audit:state-match
```

## What It Does

Dynamically scans every audited model and compares:

- Current database state
- Latest immutable audit ledger state

---

## What It Detects

If an attacker bypasses the application and runs raw SQL directly against tables like:

- `users`
- `gold_prices`
- `invoices`

the verifier instantly detects that the live database state no longer matches the cryptographic ledger.

---

# Production Deployment & Server Workarounds

Because cryptographic logging relies on queued jobs (`ProcessAuditLogJob`), queue workers must continuously process jobs.

---

# Scenario A: VPS / Dedicated Server (Recommended)

For:

- Linux VPS
- Windows VPS (IIS)
- Dedicated Servers

use **PM2** to daemonize Laravel queue workers.

---

## Step 1: Create `ecosystem.config.js`

```js
module.exports = {
    apps: [
        {
            name: "pma-audit-queue",
            script: "artisan",
            args: "queue:work database --sleep=3 --tries=3",
            interpreter: "php",
        },
    ],
};
```

---

## Step 2: Start PM2

```bash
pm2 start ecosystem.config.js
pm2 save
```

---

# Scenario B: Shared Hosting Workaround

Shared hosting providers (HostGator, GoDaddy, etc.) often terminate long-running daemon processes.

To safely process audit queues without violating hosting policies:

---

## Step 1: Configure Queue Driver

In `.env`:

```env
QUEUE_CONNECTION=database
```

---

## Step 2: Create Cron Job

Open:

- cPanel
- Plesk
- Hosting Cron Manager

Create a cron job that runs every minute:

```bash
cd /path-to-your-project && php artisan queue:work --stop-when-empty >> /dev/null 2>&1
```

---

## How the Workaround Functions

The `--stop-when-empty` flag makes the worker:

1. Wake up
2. Process all pending audit jobs
3. Exit gracefully once the queue is empty

Because the process naturally terminates, shared hosting security systems will not flag it as a rogue background daemon.

---

# Security Guarantees

This architecture provides:

- Immutable audit history
- Cryptographic tamper detection
- Detection of raw SQL modifications
- Protection against insider threats
- Zero-Trust change verification
- Full historical accountability

---

# Recommended Production Enhancements

For enterprise deployments, consider adding:

- Signed audit exports
- External SIEM integration
- Offsite immutable backups
- Blockchain anchoring for hash snapshots
- Real-time tamper alerting
- Multi-region audit replication
- Hardware Security Module (HSM) signing

---

# Final Notes

This system is intentionally designed under the assumption that:

- Databases can be compromised
- Administrators can become malicious
- Servers can be breached
- Internal actors can abuse privileges

The audit ledger therefore acts as a cryptographic source of truth rather than relying on trust in infrastructure alone.
