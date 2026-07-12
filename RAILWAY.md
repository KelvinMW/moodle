# Deploying OneBoard (Moodle 5.2) to Railway

This branch (`OneBoard-v1`) ships a Docker-based Railway deploy:

- **PHP 8.3 + Apache**, document root = `public/`
- **PostgreSQL** (Railway Postgres plugin)
- **Redis** sessions (Railway Redis plugin)
- `config.php` is generated from environment variables at boot — no secrets in git
- `moodledata` lives on a Railway **Volume** (persists across deploys)

## 1. Create the Railway project

1. New Project → **Deploy from GitHub repo** → `KelvinMW/moodle`, branch `OneBoard-v1`.
2. Add plugin **PostgreSQL**.
3. Add plugin **Redis**.
4. On the Moodle service add a **Volume** mounted at `/var/moodledata`.

## 2. Reference the plugin variables

On the Moodle service → **Variables**, add references so the DB/Redis vars are injected:

```
PGHOST=${{Postgres.PGHOST}}
PGPORT=${{Postgres.PGPORT}}
PGDATABASE=${{Postgres.PGDATABASE}}
PGUSER=${{Postgres.PGUSER}}
PGPASSWORD=${{Postgres.PGPASSWORD}}
REDISHOST=${{Redis.REDISHOST}}
REDISPORT=${{Redis.REDISPORT}}
REDISPASSWORD=${{Redis.REDISPASSWORD}}
```

(Names match what the Railway plugins expose; adjust if your plugin uses different keys.)

## 3. Set the OneBoard variables

| Variable | Required | Default | Notes |
|----------|----------|---------|-------|
| `MOODLE_ADMIN_PASS` | **yes** (first deploy) | — | Admin password; deploy fails without it |
| `MOODLE_ADMIN_USER` | no | `admin` | |
| `MOODLE_ADMIN_EMAIL` | no | `admin@example.com` | |
| `MOODLE_SITE_FULLNAME` | no | `OneBoard` | |
| `MOODLE_SITE_SHORTNAME` | no | `OneBoard` | |
| `MOODLE_WWWROOT` | recommended | Railway domain | e.g. `https://oneboard.up.railway.app` |
| `MOODLE_DATAROOT` | no | `/var/moodledata` | must match the Volume mount |
| `MOODLE_DB_PREFIX` | no | `mdl_` | |
| `MOODLE_LANG` | no | `en` | |
| `MOODLE_DEBUG` | no | — | `true` to show errors |

> **Set `MOODLE_WWWROOT` to your final public URL before first install** — Moodle bakes the
> site URL into stored config. Generate the Railway domain first, then set this to match.

## 4. Deploy

Railway builds the `Dockerfile`. On first boot the entrypoint:
1. prepares `moodledata` on the volume,
2. writes `config.php` from env,
3. points Apache at `public/` on `$PORT`,
4. runs `admin/cli/install_database.php` (fresh DB) or `admin/cli/upgrade.php` (existing).

Subsequent deploys only upgrade — data is preserved.

## 5. Cron (recommended)

Moodle needs its scheduled task runner. Add a separate Railway **Cron** service (or a
scheduled job) from the same image running:

```
php admin/cli/cron.php
```

every minute.
