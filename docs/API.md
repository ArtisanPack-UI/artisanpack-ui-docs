# ArtisanPack UI Docs — v1 remote-admin API

Spec for the Sanctum-guarded API that `artisanpackui.dev` (and any other
approved client) uses to manage packages, documentation, and changelogs
on this site.

Ref: [V2_REFACTOR_PLAN.md](../V2_REFACTOR_PLAN.md) §4.2, §4.3, §8.2, §9.6.

## 1. Base URL & auth

- **Prefix:** `/api/v1`
- **Auth:** every request requires
  `Authorization: Bearer <personal-access-token>` carrying at least one
  of the [abilities](#3-abilities) below.
- **Token issuance:** admins mint tokens at
  `/dashboard/settings/api-tokens` or via
  `php artisan artisanpack:issue-token`. Tokens are shown once —
  copy them immediately.
- **Rotation:** tokens older than **90 days** are flagged on
  `/dashboard/settings/api-tokens` with a "rotate" action.

## 2. Rate limiting & CORS

- All `/api/*` traffic runs through the `throttle:api` limiter
  (`AppServiceProvider::boot()`). Anonymous requests are keyed by client
  IP; authenticated requests by user id.
- CORS is restricted to the origins in `CORS_ALLOWED_ORIGINS`
  (`config/cors.php`). Preflight from any other origin is rejected.

## 3. Abilities

The bounded allow-list from `App\Enums\TokenAbility`:

| Ability             | Grants                                            |
|---------------------|---------------------------------------------------|
| `packages:read`     | List / show any package                           |
| `packages:write`    | Create, update, delete packages                   |
| `docs:read`         | List / show documentation                         |
| `docs:write`        | Create, update, delete, reorder documentation     |
| `changelogs:read`   | List changelogs                                   |
| `changelogs:write`  | Create, update, delete changelogs                 |
| `imports:trigger`   | Queue documentation / changelog imports           |

A token without the required ability for a route receives **403**.

> Tokens minted before `imports:trigger` existed can't call the import
> endpoints. See [`reissue-import-token.md`](./reissue-import-token.md).


## 4. Error envelope

Errors are the standard Laravel JSON shape. Callers should treat
non-2xx responses as failures and read `message` for a user-visible
string.

**Validation (422)**

```json
{
    "message": "The name field is required. (and 1 more error)",
    "errors": {
        "name": ["The name field is required."],
        "slug": ["The slug field is required."]
    }
}
```

**Auth / ability (401, 403)**

```json
{ "message": "Unauthenticated." }
```

```json
{ "message": "Invalid ability provided." }
```

**Not found (404)**

```json
{ "message": "No query results for model [Modules\\Packages\\Package] 42" }
```

**Rate limit (429)**

Standard `Retry-After` header plus:

```json
{ "message": "Too Many Attempts." }
```

## 5. Endpoints

Every write is written to the [audit log](../V2_REFACTOR_PLAN.md#43-audit-log)
and visible at `/dashboard/audit-log` for admins.

### 5.1 Packages

| Method & URL                                | Ability            | Response       |
|---------------------------------------------|--------------------|----------------|
| `GET    /api/v1/packages`                   | `packages:read`    | 200 collection |
| `GET    /api/v1/packages?slug={slug}`       | `packages:read`    | 200 collection |
| `GET    /api/v1/packages/{package}`         | `packages:read`    | 200 resource   |
| `POST   /api/v1/packages`                   | `packages:write`   | 201 resource   |
| `PATCH  /api/v1/packages/{package}`         | `packages:write`   | 200 resource   |
| `DELETE /api/v1/packages/{package}`         | `packages:write`   | 204 empty      |

`{package}` in every route accepts either the numeric id or the package
slug (`/api/v1/packages/42` and `/api/v1/packages/react` are equivalent).
Unknown ids and slugs return **404**.

`?slug=` narrows the index to an exact slug match — the collection has
one item, or none when no package uses that slug.

**Package payload (create / update)**

| Field              | Type    | Required | Notes                                     |
|--------------------|---------|----------|-------------------------------------------|
| `name`             | string  | ✓        | Max 255                                   |
| `slug`             | string  | ✓        | Max 255; must not be digits only          |
| `wiki_url`         | url     | one of\* | Must be a GitHub URL                      |
| `docs_url`         | url     | one of\* | Must be a GitHub URL                      |
| `changelog_url`    | url     | ✓        | Must be a GitHub URL                      |
| `homepage`         | integer | –        |                                           |
| `icon`             | string  | –        |                                           |
| `version`          | string  | –        |                                           |
| `package_registry` | enum    | –        | `packagist` \| `npm`                      |

\* One of `wiki_url` / `docs_url` is required.

**Resource shape** (`PackageResource`)

```json
{
    "data": {
        "id": 42,
        "name": "ArtisanPack UI React",
        "slug": "react",
        "homepage": null,
        "wiki_url": "https://github.com/artisanpack-ui/react/wiki",
        "docs_url": null,
        "changelog_url": "https://github.com/artisanpack-ui/react/blob/main/CHANGELOG.md",
        "icon": {
            "raw": "ap.puzzle",
            "set": "ap",
            "name": "puzzle",
            "svg": "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 640 640\" fill=\"currentColor\">…</svg>"
        },
        "version": "3.0.0",
        "package_registry": "npm",
        "docs_imported_at": "2026-10-01T12:00:00.000000Z",
        "changelog_imported_at": null,
        "imports": {
            "docs": {
                "status": "succeeded",
                "error": null,
                "imported_at": "2026-10-01T12:00:00.000000Z"
            },
            "changelog": {
                "status": "failed",
                "error": "Failed to fetch file content",
                "imported_at": null
            }
        },
        "created_at": "2026-01-01T00:00:00.000000Z",
        "updated_at": "2026-07-25T12:00:00.000000Z"
    }
}
```

**`icon`** is `null` when the package has no icon, otherwise an iconRef:

| Field  | Notes                                                                                     |
|--------|-------------------------------------------------------------------------------------------|
| `raw`  | The stored identifier (`ap.puzzle`, `fas.cube`, `fa-brands fa-github`, `cube`, …)         |
| `set`  | `ap` for the custom ArtisanPack set; otherwise the Font Awesome set (`fas`, `fab`, `far`, `fal`, `fad`). Bare names and unknown prefixes resolve to `fas`. |
| `name` | Icon name within the set                                                                  |
| `svg`  | Sanitized SVG markup (`fill="currentColor"`) for custom-set icons only; `null` for Font Awesome icons, which every consumer already has |

**`imports`** reports the most recent run of each import type.
`status` is `null` (never run), `queued`, `succeeded`, or `failed`;
`error` holds the failure message (truncated to 1000 characters) and
is cleared when an import is queued again or succeeds. `imported_at`
(also exposed top-level as `docs_imported_at` /
`changelog_imported_at`) is the time of the last **successful**
import and is not cleared by a failure.

### 5.1.1 Import triggers

| Method & URL                                        | Ability            | Response     |
|-----------------------------------------------------|--------------------|--------------|
| `POST   /api/v1/packages/{package}/import-docs`      | `imports:trigger`  | 202 accepted |
| `POST   /api/v1/packages/{package}/import-changelog` | `imports:trigger`  | 202 accepted |

Both endpoints take no body: they queue an import from the URLs already
stored on the package and mark that import `queued` (see `imports`
above). Poll `GET /api/v1/packages/{package}` for the outcome.

- `import-docs` reads `docs_url` (preferred) or `wiki_url`; returns
  **422** when neither is set.
- `import-changelog` reads `changelog_url`; returns **422** when it is
  empty.

```json
{ "message": "Documentation import queued.", "package": "react", "source": "docs" }
```

```json
{ "message": "Changelog import queued.", "package": "react" }
```

### 5.2 Documentation

| Method & URL                                                   | Ability         |
|----------------------------------------------------------------|-----------------|
| `GET    /api/v1/packages/{package}/documentation`              | `docs:read`     |
| `GET    /api/v1/documentation/{documentation}`                 | `docs:read`     |
| `POST   /api/v1/packages/{package}/documentation`              | `docs:write`    |
| `PATCH  /api/v1/documentation/{documentation}`                 | `docs:write`    |
| `DELETE /api/v1/documentation/{documentation}`                 | `docs:write`    |
| `POST   /api/v1/packages/{package}/documentation/reorder`      | `docs:write`    |

- `GET .../documentation` returns a **nested tree** keyed by `children`,
  ordered by `menu_order` within each parent.
- `package_id` is pinned to the URL / existing owner — a `docs:write`
  token cannot move a doc between packages by injecting `package_id`
  into the PATCH body.
- Reorder is throttled to `60/minute` on top of `throttle:api`.

**Documentation payload**

| Field              | Type    | Required | Notes                             |
|--------------------|---------|----------|-----------------------------------|
| `title`            | string  | ✓        | Max 255                           |
| `slug`             | string  | ✓        | Max 255                           |
| `content`          | string  | ✓        | Markdown                          |
| `parent`           | integer | –        | Parent doc id (`0` = top-level)   |
| `menu_order`       | integer | –        | ≥ 0                               |
| `meta_description` | string  | –        | Max 255                           |

**Reorder payload**

```json
{
    "items": [
        { "id": 12, "menu_order": 0 },
        { "id": 13, "menu_order": 1 }
    ]
}
```

Response (JSON):

```json
{ "message": "Documentation order updated." }
```

### 5.3 Changelogs

| Method & URL                                       | Ability             |
|----------------------------------------------------|---------------------|
| `GET    /api/v1/packages/{package}/changelogs`     | `changelogs:read`   |
| `POST   /api/v1/packages/{package}/changelogs`     | `changelogs:write`  |
| `PATCH  /api/v1/changelogs/{changelog}`            | `changelogs:write`  |
| `DELETE /api/v1/changelogs/{changelog}`            | `changelogs:write`  |

**Changelog payload**

| Field     | Type   | Required | Notes    |
|-----------|--------|----------|----------|
| `title`   | string | –        | Max 255  |
| `content` | string | ✓        | Markdown |

`package_id` is pinned server-side, same rule as Documentation.

## 6. Audit log

Every successful write to Packages / Documentation / Changelogs — from
this API **or** the admin UI — is recorded in `audit_log` with:
`user_id`, `actor_name` (includes token name on API writes), `source`
(`web` | `api`), `resource_type`, `resource_id`, `action`
(`created` | `updated` | `deleted` | `reordered`), a JSON diff of
changed attributes, `ip_address`, `created_at`.

Surfaced at `/dashboard/audit-log` (admin only) with filters for
resource, actor, source, and date range.

## 7. Try it locally (Bruno)

A Bruno collection lives at [`docs/api/bruno/`](api/bruno/). Import the
folder into [Bruno](https://www.usebruno.com/) and set two environment
variables in the `local` environment:

- `base_url` — e.g. `http://artisanpack-ui-docs.test`
- `token` — a PAT you minted at `/dashboard/settings/api-tokens`
