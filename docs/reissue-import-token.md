# Re-issuing the release-workflow import token

Runbook for moving the GitHub release workflows that trigger documentation
imports onto a token with the `imports:trigger` ability.

## Why this is needed

The import endpoints now require the `imports:trigger` ability
(#189):

- `POST /api/v1/packages/{package}/import-docs`
- `POST /api/v1/packages/{package}/import-changelog`

Before this change, any authenticated token could call `import-docs`.
A token minted without `imports:trigger` now receives **`403`**
(`{"message": "Invalid ability provided."}`). Until the token is
replaced, documentation is not re-imported after a release.

A token's abilities can't be edited after it's created. **Rotate** on
the API tokens page also won't fix this, because it re-issues the token
with the **same** abilities. You need to mint a new token.

## When to do it

Right after the PR that introduces `imports:trigger` is deployed to
production. Until then, production doesn't know about the new ability.

## Steps

### 1. Find the token the workflows use

1. Sign in to production as an admin and open
   **`/dashboard/settings/api-tokens`** (you'll be asked to confirm your
   password).
2. Find the token used by the release workflows. The **last used**
   column shows recent activity after releases. Note its name. You'll
   revoke it in step 4.

### 2. Mint a new token with `imports:trigger`

**Option A: settings page**

1. On `/dashboard/settings/api-tokens`, create a new token.
2. Give it a descriptive name, e.g. `release-workflow-imports`.
3. Tick **Imports: Trigger**. If the workflows do nothing else, this is
   the only ability they need. Least privilege: don't add read or write
   abilities they don't use.
4. Copy the token immediately. It is shown only once.

**Option B: Artisan on the production server**

```bash
php artisan artisanpack:issue-token \
    --user=you@example.com \
    --name=release-workflow-imports \
    --ability=imports:trigger
```

The plain-text token is printed once. Copy it.

### 3. Update the workflow secret

In every package repository whose release workflow calls `import-docs`
(or `import-changelog`), replace the value of the repository or
organization secret that holds the docs-site token:

- **Organization secret** (shared by all package repos): GitHub →
  **ArtisanPack-UI** org → **Settings → Secrets and variables →
  Actions** → edit the secret → paste the new token.
- **Repository secrets**: repeat in each package repo under
  **Settings → Secrets and variables → Actions**.

Workflow files don't need to change. The endpoint URLs and the
`Authorization: Bearer` header stay the same.

### 4. Verify, then revoke the old token

1. Trigger an import with the new token, either by re-running the latest
   release workflow or manually:

   ```bash
   curl -s -w "\n%{http_code}\n" -X POST \
       -H "Authorization: Bearer $NEW_TOKEN" \
       -H "Accept: application/json" \
       https://docs.artisanpackui.dev/api/v1/packages/<slug>/import-docs
   ```

   Expect **`202`** with `"Documentation import queued."`.
2. Confirm the import finished. `GET /api/v1/packages/<slug>` (with a
   token that has `packages:read`) should show `imports.docs.status`
   change from `queued` to `succeeded`.
3. Back on `/dashboard/settings/api-tokens`, **revoke** the old token
   from step 1.

## Troubleshooting

| Response | Meaning                                                                                   |
|----------|-------------------------------------------------------------------------------------------|
| `403`    | The token doesn't have `imports:trigger`: the secret still holds the old token, or the new one was minted without the ability. |
| `401`    | The token was revoked or mistyped.                                                        |
| `404`    | Unknown package id or slug.                                                               |
| `422`    | The package has no `docs_url` / `wiki_url` (or no `changelog_url` for `import-changelog`). |
| `429`    | Rate limited by `throttle:api`. Retry after the `Retry-After` header.                     |

See [`API.md`](./API.md) §3 (abilities) and §5.1.1 (import triggers) for
the full contract.
