# Forge deploy runbook

Runbook for deploying `artisanpack-ui-docs` on Laravel Forge. Covers daemon
setup, the deploy script, required env vars, and the rollback plan.

The SSR restart pattern lives in [`inertia-ssr-forge.md`](./inertia-ssr-forge.md);
this file references it rather than duplicating.

## Site

- Production hostname: **`docs.artisanpackui.dev`**
- Site root on Forge: `/home/forge/docs.artisanpackui.dev`
- Deploy branch: **`main`**.

## Daemons

Three long-running processes run alongside PHP-FPM. All are configured
under Forge → **Daemons** with `sudo` user `forge` and auto-restart on
crash.

| Purpose            | Command                                                                       | Notes                                                                                        |
| ------------------ | ----------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------- |
| Inertia SSR        | `php artisan inertia:start-ssr`                                               | Listens on `127.0.0.1:13714`. See [`inertia-ssr-forge.md`](./inertia-ssr-forge.md).          |
| Queue worker       | `php artisan queue:work --sleep=3 --tries=3 --max-time=3600`                  | Drains `ImportWikiDocumentation` and `ImportChangelog`. Queue driver is `database`.          |
| Scheduler (if any) | `php artisan schedule:work`                                                   | Only required if `routes/console.php` gains scheduled tasks. Not needed for the 3.0 cutover. |

Forge's toggle for **Inertia SSR** (Application panel) provisions the SSR
daemon for you; add the queue worker manually.

## Environment variables

Set these on the site's **Environment** panel in Forge. Anything not listed
here uses the config-file default and does not need to be set.

| Variable                                            | Value                            | Why                                                                                       |
| --------------------------------------------------- | -------------------------------- | ----------------------------------------------------------------------------------------- |
| `APP_ENV`                                           | `production`                     | Enables prod-only paths in framework code.                                                |
| `APP_DEBUG`                                         | `false`                          | Never `true` in production.                                                               |
| `APP_URL`                                           | `https://docs.artisanpackui.dev` | Used by route helpers, canonical URLs, mail links.                                        |
| `APP_KEY`                                           | *(generated)*                    | `php artisan key:generate` once; never rotate without re-encrypting sessions/tokens.      |
| `NODE_ENV`                                          | `production`                     | Read by the SSR Node process and Vite build.                                              |
| `DB_*`                                              | *(Forge-managed)*                | Standard MySQL creds from the Forge database panel.                                       |
| `QUEUE_CONNECTION`                                  | `database`                       | Matches the worker daemon; jobs table is created by the framework migrations.             |
| `SESSION_DRIVER`                                    | `database`                       | Default. Sessions persist across FPM restarts.                                            |
| `CACHE_STORE`                                       | `database`                       | Default. Swap to `redis` only if a Redis instance is added.                               |
| `CORS_ALLOWED_ORIGINS`                              | `https://artisanpackui.dev`      | Comma-separated. No wildcards — see `config/cors.php`.                                    |
| `API_RATE_LIMIT_PER_MINUTE`                         | `60`                             | Global per-token throttle applied by `throttle:api`.                                      |
| `TRUSTED_PROXIES`                                   | *(Forge LB CIDR, or empty)*      | Only set if a load balancer sits in front of the site.                                    |
| `INERTIA_SSR_ENABLED`                               | *(unset — defaults to `true`)*   | Only set to `false` as a panic switch; prefer the Forge daemon toggle so it's reversible. |
| `MAIL_*`                                            | *(mailer creds)*                 | Required for 2FA / password reset emails.                                                 |

Google Analytics/Search Console keys and any third-party service creds
belong in the same panel; they're not release-gating.

## Deploy script

The site uses Forge **Zero-Downtime Deployments**: `$CREATE_RELEASE()`
clones the configured branch into a fresh release directory,
`$ACTIVATE_RELEASE()` atomically swaps the `current` symlink, and
`$RESTART_QUEUES()` restarts queue workers. Nothing in the script
needs to know the branch name — Forge reads it from Application →
**Git Repository → Branch**, which is why the branch flip is a
one-click change with no script edit.

Two things the script does that aren't in Forge's Laravel default:

1. `npm ci && npm run build` **before** `inertia:stop-ssr` — the SSR
   daemon loads `bootstrap/ssr/ssr.js`, which is a build output. The
   restart has to see the new bundle on disk.
2. `inertia:stop-ssr || true` — the `|| true` handles the case where
   SSR is toggled off, so the daemon isn't running and the artisan
   command exits non-zero without failing the deploy.

```bash
$CREATE_RELEASE()

cd $FORGE_RELEASE_DIRECTORY

$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Client + SSR bundles. `npm ci` keeps the install deterministic
# against package-lock.json; `npm run build` writes public/build/
# AND bootstrap/ssr/ssr.js.
npm ci || npm install
npm run build

$FORGE_PHP artisan optimize --except=views
$FORGE_PHP artisan storage:link
$FORGE_PHP artisan migrate --force

# SSR restart — must run AFTER `npm run build`. See
# `inertia-ssr-forge.md` for the full zero-downtime pattern.
$FORGE_PHP artisan inertia:stop-ssr || true

$ACTIVATE_RELEASE()

$RESTART_QUEUES()
```

`optimize --except=views` runs `config:cache`, `route:cache`, and
`event:cache` in one shot. Views are skipped because Blade caches
compile lazily on first render and there's no gain from priming them.

## Post-deploy verification

Run these from Forge → **SSH** on the site as the `forge` user.

```bash
# SSR healthy?
php artisan inertia:check-ssr

# Queue worker running?
ps -ef | grep "queue:work" | grep -v grep

# Migrations applied?
php artisan migrate:status | tail -20

# Site returns 200?
curl -sS -o /dev/null -w "%{http_code}\n" https://docs.artisanpackui.dev/

# Rendered on the server?
# CSR fallback ships an empty `<div id="app" data-page="…"></div>`; SSR
# fills that div with the pre-rendered app markup. Grep for a heading
# inside the response body — 0 hits means SSR did not render (whether
# the daemon is down or the middleware fell through).
# `inertia:check-ssr` above is the authoritative daemon-liveness check;
# this is the request-path check that the daemon actually got used.
curl -sS https://docs.artisanpackui.dev/ | grep -Ec '<h[12][^>]*>'   # >0 means SSR rendered
```

## Rollback

Rollbacks are ordered by blast radius — start with the smallest.

1. **SSR broken only** (bundle bad, daemon crash-loops): flip Forge →
   **Inertia SSR** off. Site falls back to CSR. Investigate; fix; re-enable.
   Details in [`inertia-ssr-forge.md`](./inertia-ssr-forge.md#rollback).
2. **Bad deploy, code-level** (500s, wrong routes, broken assets):
   Forge → **Deployments** shows the deploy history. The site uses
   Zero-Downtime Deployments, so each deploy is a distinct
   `~/site/releases/<timestamp>/` directory and `~/site/current` is a
   symlink to whichever release is live. Click **Rollback** on the
   last known-good deployment — Forge re-points `current` at that
   release directory. No rebuild, no downtime; assets and
   `bootstrap/ssr/ssr.js` from that release are already on disk.
   The SSR daemon keeps running; if the bad release changed the SSR
   bundle format, `php artisan inertia:stop-ssr` from
   [SSR-only rollback](#rollback) forces a supervisor restart against
   the rolled-back bundle.

   If Forge's rollback UI is unavailable, SSH in and swap the symlink
   by hand:
   ```bash
   cd /home/forge/docs.artisanpackui.dev
   ls -1 releases/                # timestamps of retained releases
   ln -sfn releases/<last-good> current
   php artisan inertia:stop-ssr || true
   php artisan queue:restart
   ```
3. **Bad migration**: if the migration is destructive, restore the most
   recent Forge database snapshot from the **Database** panel *before*
   rolling the code release back — the older code will not tolerate
   the new schema. Non-destructive migrations (added columns, added
   tables) are safe to leave in place while running the prior release.
4. **Bad `.env` change**: revert the specific keys in Forge →
   **Environment**, then click **Deploy Now** to re-cache config.

## Major-version cutover pattern

For future `release/x.y` → `main` cutovers:

1. Merge the release PR to `main` and tag on `main`.
2. Forge → **Application** → set **Git Repository → Branch** to `main`
   (or your target branch). The deploy script doesn't need to change
   — ZDD reads the branch name from Forge, not the script.
3. Click **Deploy Now**. Verify against
   [Post-deploy verification](#post-deploy-verification).
4. Once healthy, delete the release branch:
   `git push origin --delete release/x.y`.

## Related

- [`inertia-ssr-forge.md`](./inertia-ssr-forge.md) — SSR daemon setup,
  restart pattern, health check, SSR-specific rollback.
- `config/cors.php`, `config/sanctum.php` — API surface config that
  reads from `CORS_ALLOWED_ORIGINS` and Sanctum env vars.
- `config/inertia.php` — SSR toggle + URL, safe at defaults.
- Issue #113 — SSR staging dry-run + Lighthouse gate.
- Issue #117 — v3.0.0 tag + `release/3.0` → `main` merge.
