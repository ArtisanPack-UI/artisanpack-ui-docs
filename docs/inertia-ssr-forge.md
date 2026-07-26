# Inertia SSR on Laravel Forge

Runbook for enabling and operating the Inertia server-side renderer for
`artisanpack-ui-docs` on Laravel Forge. Companion to the wider Forge deploy
runbook (issue #116); this file is scoped to the SSR daemon and the
zero-downtime restart pattern used on every deploy.

## What SSR does here

Public docs pages are rendered on the server via a Node.js process so search
engines and social crawlers receive fully hydrated HTML. React then hydrates
the same DOM in the browser (`hydrateRoot` in `resources/js/app.tsx`), so no
re-render blip.

- Client entry: `resources/js/app.tsx` → `public/build/`.
- Server entry: `resources/js/ssr.tsx` → `bootstrap/ssr/ssr.js`.
- Server-side glue: the `HandleInertiaRequests` middleware + the
  `inertia.ssr.*` config in `config/inertia.php`.
- Daemon command: `php artisan inertia:start-ssr` (listens on
  `http://127.0.0.1:13714` by default).

Both bundles are produced by `npm run build`, which runs
`vite build && vite build --ssr`. `bootstrap/ssr/` is git-ignored — the
bundle is a build artifact.

## Forge setup (one-time)

1. Open the site in Forge → **Application** panel.
2. Toggle **Inertia SSR** on. Forge creates a daemon that runs
   `php artisan inertia:start-ssr` from the site root with auto-restart on
   crash.
3. Confirm the deploy script includes the SSR restart step (see below). If
   Forge did not add it automatically, patch it manually.
4. Confirm the site's `.env` has `NODE_ENV=production` and the correct
   `APP_URL`. `VITE_APP_NAME` is optional (defaults to
   `"ArtisanPack UI Docs"`).

No extra env vars are required for SSR itself — `inertia.ssr.enabled` and
`inertia.ssr.url` come from `config/inertia.php` and are safe to leave at
the defaults.

## Deploy script

The site uses Forge Zero-Downtime Deployments — see
[`forge-deploy.md`](./forge-deploy.md#deploy-script) for the full
script. The SSR-relevant slice looks like this: build both bundles
**before** the daemon restart so `bootstrap/ssr/ssr.js` is on disk
when the supervisor relaunches it.

```bash
$CREATE_RELEASE()

cd $FORGE_RELEASE_DIRECTORY

$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# `npm run build` writes public/build/ AND bootstrap/ssr/ssr.js.
npm ci || npm install
npm run build

$FORGE_PHP artisan optimize --except=views
$FORGE_PHP artisan storage:link
$FORGE_PHP artisan migrate --force

# Zero-downtime SSR restart — must run AFTER `npm run build`. The
# `|| true` covers the case where SSR is toggled off in Forge.
$FORGE_PHP artisan inertia:stop-ssr || true

$ACTIVATE_RELEASE()

$RESTART_QUEUES()
```

`inertia:stop-ssr` returns immediately; Forge's daemon supervisor sees the
exit and re-launches `php artisan inertia:start-ssr` within a second or two.
Requests that land during the restart fall back to client-side rendering
because `inertia.ssr.ensure_bundle_exists` is `true` (the middleware skips
SSR when the bundle isn't reachable, so no visitor sees a 500).

## Zero-downtime restart pattern

The pattern is: **build fully, then reload the daemon** — never the other
way around.

1. `npm ci && npm run build` writes the new `bootstrap/ssr/ssr.js`
   atomically (Vite writes to a temp file, then renames).
2. `php artisan inertia:stop-ssr` sends the running Node process a shutdown
   signal. In-flight renders finish before the process exits.
3. Forge's supervisor auto-restarts the daemon. The new process loads the
   new bundle on boot.
4. During steps 2–3, the middleware falls through to CSR — the page still
   loads, just without pre-rendered HTML. Crawlers see a brief window of
   CSR-only responses; humans don't notice.

No `--force`, `kill -9`, or manual `supervisorctl restart` is needed. If
Forge's daemon supervisor is misconfigured and the process does not come
back up, see **Rollback**.

## Health check

After deploy, from the Forge site's SSH console:

```bash
php artisan inertia:check-ssr
```

Exits `0` and prints the SSR server status when healthy; non-zero when the
daemon is down or the bundle is missing. Wire this into the post-deploy
smoke check (issue #113) once the staging environment exists.

You can also curl the daemon directly:

```bash
curl -s http://127.0.0.1:13714/health
```

## Rollback

If a deploy leaves SSR broken (bundle fails to load, daemon crash-loops,
`inertia:check-ssr` non-zero):

1. **Immediate**: turn off the **Inertia SSR** toggle in Forge. The daemon
   stops; the middleware falls back to CSR for every request. Site stays
   up.
2. Investigate the SSR log — Forge exposes the daemon's stdout/stderr from
   the daemon detail page.
3. If the bundle itself is bad, roll the deploy back (`git checkout` the
   prior tag, re-run `npm run build`) and re-enable SSR.
4. If it's a config or env issue, fix it, re-deploy, then re-enable the
   toggle.

CSR fallback is not a permanent posture — SEO parity is the reason SSR is
on — but it is the correct panic button while root-causing.

## Related

- `resources/js/ssr.tsx` — server entry point.
- `resources/js/app.tsx` — matching client entry (uses `hydrateRoot`).
- `vite.config.js` — `ssr: 'resources/js/ssr.tsx'` on the Laravel plugin.
- `package.json` — `build: "vite build && vite build --ssr"`.
- `config/inertia.php` — SSR toggle, URL, `ensure_bundle_exists`.
- Issue #116 — full Forge deploy runbook (SSR is one section of it).
- Issue #113 — SSR staging dry-run + Lighthouse gate.
