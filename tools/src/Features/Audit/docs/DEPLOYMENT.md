# Audit Deployment

Production guidance for queue workers, scheduling, and hosting environments.

---

## Requirements

| Requirement | Reason |
|-------------|--------|
| `CMS_AUDIT_ENABLED=true` | Audit system active |
| Persistent queue driver | Jobs survive process restarts |
| Running queue worker(s) | `ProcessAuditLogJob` is queued |
| `audit_logs` migration applied | Ledger storage |
| Scheduler cron (optional) | Automated verification |

---

## Queue driver

Recommended `.env`:

```env
QUEUE_CONNECTION=database
CMS_AUDIT_ENABLED=true
```

Alternative drivers: `redis`, `sqs`, `beanstalkd`.

Create jobs table if using database queue:

```bash
php artisan queue:table
php artisan migrate
```

---

## Scenario A — VPS / dedicated server (recommended)

Use a process manager to keep workers alive.

### PM2 example

Create `ecosystem.config.js`:

```js
module.exports = {
    apps: [
        {
            name: "cms-audit-queue",
            script: "artisan",
            args: "queue:work database --sleep=3 --tries=3 --max-time=3600",
            interpreter: "php",
            cwd: "/path/to/project",
        },
    ],
};
```

Start:

```bash
pm2 start ecosystem.config.js
pm2 save
pm2 startup
```

### Supervisor example (Linux)

```ini
[program:cms-audit-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/project/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/project/storage/logs/queue-worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start cms-audit-queue:*
```

---

## Scenario B — Shared hosting

Many shared hosts kill long-running daemons. Use cron-triggered workers instead.

### Step 1 — Database queue

```env
QUEUE_CONNECTION=database
```

### Step 2 — Cron every minute

```bash
* * * * * cd /path-to-your-project && php artisan queue:work --stop-when-empty >> /dev/null 2>&1
```

The `--stop-when-empty` flag:

1. Wakes up
2. Processes all pending audit jobs
3. Exits cleanly (hosting-friendly)

**Trade-off:** Up to 1-minute delay before audit rows appear.

---

## Scenario C — Laravel Sail / local dev

Run worker in a separate terminal:

```bash
php artisan queue:listen --tries=1
```

Or use the project's `composer dev` script if it includes queue listening.

---

## Scenario D — Audit disabled deployment

For APIs without audit infrastructure:

```env
CMS_AUDIT_ENABLED=false
```

No queue worker needed for audit. Models with audit traits remain safe (no-op).

---

## Scheduler setup

Ensure system cron invokes Laravel scheduler:

```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

Register verification in `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('audit:verify')->hourly();
Schedule::command('audit:state-match')->dailyAt('03:00');
```

Commands only exist when `CMS_AUDIT_ENABLED=true`.

---

## Monitoring checklist

| Check | How |
|-------|-----|
| Queue backlog | `php artisan queue:monitor database:default` or DB `jobs` table count |
| Worker alive | PM2/Supervisor status |
| Ledger growing | `SELECT COUNT(*) FROM audit_logs` |
| Chain integrity | `php artisan audit:verify` |
| Data integrity | `php artisan audit:state-match` |
| Failed jobs | `php artisan queue:failed` |

---

## Performance notes

- Audit writes are **serialized** via row locking on the latest ledger entry — high concurrency creates queue backlog, not corrupted hashes.
- Scale horizontally with **one worker** for audit integrity ordering, or accept that multiple workers rely on DB locking (current implementation handles this via `lockForUpdate`).
- `audit:state-match` is CPU/IO intensive — schedule off-peak only.

---

## Deploy checklist

1. Set `CMS_AUDIT_ENABLED=true` in production `.env`
2. Run `php artisan migrate` (creates `audit_logs` if not exists)
3. Start queue worker(s)
4. Configure scheduler + verification commands
5. Protect `/api/audit` routes with authorization middleware
6. Run `php artisan audit:verify` post-deploy smoke test
7. Clear morph map cache if new models shipped: `php artisan cache:clear`

---

## Troubleshooting

| Problem | Solution |
|---------|----------|
| No rows in `audit_logs` | Check queue worker + `CMS_AUDIT_ENABLED` |
| Jobs in `failed_jobs` | `php artisan queue:failed` → inspect exception |
| `audit:verify` fails after restore | Restore full ledger or truncate and accept gap |
| High queue latency | Add worker capacity; check DB lock contention |
| Morph alias wrong after deploy | `php artisan cache:clear` |

See also: [CONFIGURATION.md](./CONFIGURATION.md) | [VERIFICATION.md](./VERIFICATION.md)
