# Deployment

## 1. Runtime

- PHP 8.5 FPM with `pdo_pgsql`, `bcmath`, `intl`, `gd`, `zip`, `pcntl`, and `opcache`.
- PostgreSQL 16.
- Nginx serves `frontend/dist` and proxies `/api`, `/sanctum`, `/broadcasting`, and `/up` to PHP-FPM.
- Laravel Reverb runs on container port 8080 and host port 8084.

## 2. Deployment Diagram

```mermaid
flowchart LR
    User([Browser<br/>React SPA])
    Edge["TLS terminator / reverse proxy<br/>HTTPS 443 -> HTTP 80"]
    subgraph COMPOSE["Docker Compose host"]
        Nginx["nginx :80<br/>frontend/dist + reverse proxy"]
        App["app :9000<br/>PHP-FPM 8.5 / Laravel API"]
        Reverb["reverb :8080 -> host :8084<br/>WebSocket server"]
        DB[("PostgreSQL 16<br/>volume pgdata")]
    end
    CI["GitHub Actions<br/>backend + frontend CI"]
    Backup[("External backup storage<br/>FR26 target")]

    User -->|HTTPS| Edge
    Edge -->|HTTP 80| Nginx
    Nginx -->|HTML / JS / CSS| User
    Nginx -->|"FastCGI /api /sanctum /broadcasting /up"| App
    App -->|"SQL 5432"| DB
    App -->|"HTTP 8080"| Reverb
    User -->|"WebSocket 8084"| Reverb
    Reverb -->|"events"| User
    User -->|"/broadcasting/auth via Nginx"| Nginx
    CI -.->|"tests + build"| COMPOSE
    App -.->|"backup target"| Backup
```

Notes:

1. The browser loads the SPA and API through Nginx. WebSocket traffic goes directly to Reverb on host port `8084`.
2. Reverb channel authorization goes through `/broadcasting/auth` via Nginx to the Laravel app.
3. Nginx listens on HTTP `80`. In production, TLS terminates at the edge; set `APP_URL` to HTTPS, secure cookies, and exact `TRUSTED_PROXIES`.
4. PostgreSQL is internal on `5432`, has no host port, and persists through the `pgdata` volume.
5. Compose currently has no queue worker or scheduler service. `QUEUE_CONNECTION=database`; add a worker before relying on queued jobs, and a scheduler before FR26 scheduled backup.
6. CI runs tests and builds only. Deployment is manual: `docker compose up -d --build`, then `docker compose exec app php artisan migrate --force`.
7. The backup storage node is dashed because FR26 is still target, not implemented.
8. `/up` is the health endpoint used by the reverse proxy or uptime monitor.

## 3. Required Environment

Set these in the production `.env` before starting Compose:

| Variable | Purpose |
| :--- | :--- |
| `APP_ENV=production` | Production mode |
| `APP_DEBUG=false` | Never expose stack traces |
| `APP_KEY` | Laravel application key |
| `APP_URL` | Public HTTPS URL |
| `APP_LOCALE=id`, `APP_FALLBACK_LOCALE=en` | Indonesian messages; `en` supplies fallback keys |
| `DB_CONNECTION=pgsql`, `DB_HOST=db`, `DB_PORT=5432` | PostgreSQL connection |
| `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Database credentials |
| `SESSION_DRIVER=database`, `SESSION_SECURE_COOKIE=true` | HTTPS session cookie |
| `SESSION_DOMAIN`, `SANCTUM_STATEFUL_DOMAINS` | Same-origin SPA domains |
| `BROADCAST_CONNECTION=reverb` | Realtime driver |
| `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET` | Reverb credentials |
| `REVERB_HOST`, `REVERB_PORT`, `REVERB_SCHEME` | Server-side Reverb target |
| `VITE_REVERB_APP_KEY`, `VITE_REVERB_HOST`, `VITE_REVERB_PORT`, `VITE_REVERB_SCHEME` | SPA build-time Reverb values |
| `TRUSTED_PROXIES` | Exact proxy CIDRs, never `*` unless the proxy is fully trusted |

The SPA is built during the Docker image build. Rebuild the `nginx` target whenever the `VITE_REVERB_*` values change.

`config/app.php` defaults `locale` to `id`, but a production `.env` with `APP_LOCALE=en` overrides that default and silently re-enables English API messages even though the SPA copy stays Indonesian.

## 4. Docker Compose

```bash
docker compose up -d --build
docker compose exec app php artisan migrate --force
```

Services:

- `app`: PHP-FPM, Laravel API.
- `nginx`: static SPA plus API proxy.
- `db`: PostgreSQL with a named volume.
- `reverb`: Reverb WebSocket server.

The Compose file reads `.env` through `env_file`. `DB_PASSWORD` is required; there is no default. Run `docker compose config` with your production `.env` before starting.

## 5. Native Development

```bash
./init.sh
php artisan serve
php artisan reverb:start --host=0.0.0.0 --port=8080
npm --prefix frontend run dev
```

The SPA runs on `http://localhost:5173`; Vite proxies `/api`, `/sanctum`, and `/broadcasting` to `http://127.0.0.1:8000`.

## 6. CI

`.github/workflows/ci.yml` runs on `main`, `staging`, and pull requests:

- Backend: Composer install, tests, Pint `--test`, PHPStan, Composer audit on PHP 8.5.
- Frontend: Node 26, typecheck, lint, Vitest, production build.

CI does not auto-commit formatting changes.

## 7. Backups

FR26 (scheduled database backup) is not implemented. Until that sprint, use the PostgreSQL provider's snapshot schedule or a cron job running `pg_dump` outside this repository. Do not claim backup coverage from this refactor.

## 8. Deployment Checklist

1. Set production `.env` values, including HTTPS session and Sanctum domains.
2. Build and start Compose.
3. Run migrations.
4. Confirm `/up` returns 200.
5. Run `php artisan tinker --execute="echo app()->getLocale();"`; it must print `id`.
6. Log in through the SPA; verify CSRF, cookies, and one flow per role.
7. Confirm Reverb connects and `OrderUpdated` arrives on the private channel.
8. Confirm the SPA is served from `frontend/dist` and API paths never return the SPA shell.
9. Record any skipped verification in the change description.
