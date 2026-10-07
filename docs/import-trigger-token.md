# Issuing a token for remote import triggers

How to create a docs-site API token that can queue documentation and
changelog imports remotely, for the artisanpack-ui.dev marketing plugin
or a package's GitHub release workflow.

## Background

Two endpoints queue imports from the URLs stored on a package:

- `POST /api/v1/packages/{package}/import-docs`
- `POST /api/v1/packages/{package}/import-changelog`

Both require a token with the **`imports:trigger`** ability (#189).
A token without it receives **`403`**
(`{"message": "Invalid ability provided."}`).

These are **docs-site** tokens (Laravel Sanctum), minted on this site.
They are not GitHub personal access tokens, and nothing about them is
configured under the GitHub org's "Personal access tokens" settings.

A token's abilities can't be changed after it is created. **Rotate** on
the API tokens page re-issues a token with the **same** abilities, so a
token that needs `imports:trigger` added must be minted fresh.

## 1. Mint the token

Use one token per consumer (e.g. one for the marketing plugin, one for
release workflows) so each can be revoked on its own.

**Option A: settings page**

1. Sign in to production as an admin and open
   **`/dashboard/settings/api-tokens`** (you'll be asked to confirm your
   password).
2. Create a token with a descriptive name, e.g.
   `release-workflow-imports` or `marketing-site`.
3. Tick only the abilities the consumer needs:
   - Release workflow: **Imports: Trigger**.
   - Marketing plugin: **Imports: Trigger** plus **Packages: Read** (to
     poll import status), and **Packages: Write** if it updates versions.
4. Copy the token immediately. It is shown only once.

**Option B: Artisan on the production server**

```bash
php artisan artisanpack:issue-token \
    --user=you@example.com \
    --name=release-workflow-imports \
    --ability=imports:trigger
```

Repeat `--ability` for each extra ability. The plain-text token is
printed once.

## 2. Store it where the consumer reads it

- **GitHub release workflows:** add it as an Actions secret, either once
  for the whole org (**ArtisanPack-UI** org → **Settings → Secrets and
  variables → Actions → New organization secret**) or per package repo
  (repo → **Settings → Secrets and variables → Actions**). A workflow
  step then sends it as `Authorization: Bearer ${{ secrets.<NAME> }}`.
- **Marketing plugin:** paste it into the plugin's docs-site API
  settings on artisanpack-ui.dev.

## 3. Verify

```bash
curl -s -w "\n%{http_code}\n" -X POST \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json" \
    https://docs.artisanpackui.dev/api/v1/packages/<slug>/import-docs
```

Expect **`202`** with `"Documentation import queued."`. Then, with a
token that has `packages:read`, `GET /api/v1/packages/<slug>` should
show `imports.docs.status` change from `queued` to `succeeded`.

`{package}` accepts either the numeric id or the slug.

## Troubleshooting

| Response | Meaning                                                                                   |
|----------|-------------------------------------------------------------------------------------------|
| `403`    | The token doesn't have `imports:trigger`. Mint a new one with the ability.                |
| `401`    | The token was revoked or mistyped.                                                        |
| `404`    | Unknown package id or slug.                                                               |
| `422`    | The package has no `docs_url` / `wiki_url` (or no `changelog_url` for `import-changelog`). |
| `429`    | Rate limited by `throttle:api`. Retry after the `Retry-After` header.                     |

An import that is accepted but ends with `imports.*.status` of
`failed` is a job-side problem. The most common cause is a missing or
undecryptable GitHub token in the site settings. Check
`imports.*.error`.

See [`API.md`](./API.md) §3 (abilities) and §5.1.1 (import triggers) for
the full contract.
