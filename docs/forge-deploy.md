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

Forge's default deploy script needs three modifications for this app:

1. `npm ci && npm run build` **before** the SSR restart — the SSR daemon
   loads `bootstrap/ssr/ssr.js`, which is a build output.
2. `php artisan inertia:stop-ssr` at the end so Forge's supervisor
   relaunches SSR against the fresh bundle.
3. `queue:restart` so the queue worker picks up new code.

```bash
cd /home/forge/docs.artisanpackui.dev

git pull origin main

$FORGE_COMPOSER install --no-interaction --prefer-dist --optimize-autoloader --no-dev

npm ci --no-audit --no-fund
npm run build

( flock -w 10 9 || exit 1
    echo 'Restarting FPM...'
    sudo -S service $FORGE_PHP_FPM reload ) 9>/tmp/fpmlock

$FORGE_PHP artisan migrate --force
$FORGE_PHP artisan config:cache
$FORGE_PHP artisan route:cache
$FORGE_PHP artisan view:cache
$FORGE_PHP artisan event:cache

# Signal the queue worker to reload; the daemon supervisor restarts it.
$FORGE_PHP artisan queue:restart

# SSR restart MUST run after npm run build. See inertia-ssr-forge.md
# for the full zero-downtime pattern.
$FORGE_PHP artisan inertia:stop-ssr
```

Change the `git pull` branch to `main` once #117 merges.

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
   - In Forge → **Deployments**, click **Redeploy** on the last known-good
     commit — this is the preferred path; it stays on the configured
     branch and Forge tracks the deployed SHA correctly.
   - SSH fallback (only if Forge's Redeploy button is unavailable):
     ```bash
     cd /home/forge/docs.artisanpackui.dev
     git fetch --tags
     git checkout <last-good-tag>          # e.g. v2.0.0 during 3.0 cutover
     $FORGE_COMPOSER install --no-interaction --prefer-dist --optimize-autoloader --no-dev
     npm ci --no-audit --no-fund && npm run build
     $FORGE_PHP artisan config:cache
     $FORGE_PHP artisan route:cache
     $FORGE_PHP artisan view:cache
     $FORGE_PHP artisan event:cache
     $FORGE_PHP artisan queue:restart
     $FORGE_PHP artisan inertia:stop-ssr
     ```
     `git checkout <tag>` leaves the working tree in detached-HEAD
     state. Before the next normal deploy fires, reattach the
     configured branch so Forge's `git pull origin <branch>` succeeds:
     ```bash
     git checkout main
     git pull --ff-only
     ```
3. **Bad migration**: if the migration is destructive, restore the most
   recent Forge database snapshot from the **Database** panel *before*
   rolling code back — the older code will not tolerate the new schema.
   Non-destructive migrations (added columns, added tables) are safe to
   leave in place while running the prior release.
4. **Bad `.env` change**: revert the specific keys in Forge →
   **Environment**, then click **Deploy Now** to re-cache config.

Do not use `git reset --hard <tag>` on the deploy checkout to roll
back. It rewrites the local branch pointer without touching the
remote, so the next `git pull origin <branch>` fast-forwards straight
back to the bad commit — the "rollback" evaporates on the next
deploy. Use Forge's Redeploy button on the last-good commit, or the
`git checkout <tag>` (detached) + reattach sequence above.

## Major-version cutover pattern

For future `release/x.y` → `main` cutovers, run the flip in this order
so Forge is never pointed at a nonexistent branch:

1. Merge the release PR to `main` and tag on `main`.
2. Forge → **Application** → set **Git Repository → Branch** to `main`
   (or your target branch).
3. Update the deploy-script snippet in this file and in
   [`inertia-ssr-forge.md`](./inertia-ssr-forge.md#deploy-script)
   to match the new branch name.
4. Click **Deploy Now**. Verify against
   [Post-deploy verification](#post-deploy-verification).
5. Once healthy, delete the release branch:
   `git push origin --delete release/x.y`.

## Related

- [`inertia-ssr-forge.md`](./inertia-ssr-forge.md) — SSR daemon setup,
  restart pattern, health check, SSR-specific rollback.
- `config/cors.php`, `config/sanctum.php` — API surface config that
  reads from `CORS_ALLOWED_ORIGINS` and Sanctum env vars.
- `config/inertia.php` — SSR toggle + URL, safe at defaults.
- Issue #113 — SSR staging dry-run + Lighthouse gate.
- Issue #117 — v3.0.0 tag + `release/3.0` → `main` merge.
