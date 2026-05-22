# Issue #20 — Docs `docs/` directory import: handoff notes

Branch: `feature/20-docs-directory-import` (not yet PR'd). These notes are for testing the
change on the Mac mini.

## What this adds

A second documentation import source: a package repo's `docs/` directory, alongside the
existing GitHub/GitLab **wiki** import. When a package has a `docs_url`, it takes priority
over `wiki_url`; `wiki_url` remains the fallback and the existing wiki import path is
unchanged.

## First step on the Mac mini

```bash
git checkout feature/20-docs-directory-import
php artisan migrate          # adds packages.docs_url, makes packages.wiki_url nullable
php artisan test             # full suite was green here (153 passing)
```

## How to test the import end-to-end

1. In the admin UI, edit a package and set **Docs URL** to a GitHub repo, e.g.
   `https://github.com/ArtisanPack-UI/forms` (or a docs subdir:
   `https://github.com/ArtisanPack-UI/forms/tree/main/docs`).
2. Make sure a `github_token` setting exists (encrypted) — same token the wiki import uses.
3. Click **Import Documentation**. The job clones the repo (shallow), reads `docs/`, and
   creates `documentation` rows.
4. Confirm the queue worker is running (`php artisan queue:work`) since the import is queued.

The import job is still `App\Jobs\ImportWikiDocumentation` — it now branches on `docs_url`.

## Key behaviors / decisions baked in

- **Parent page priority** when a section has multiple candidate files (decided with Jacob):
  `index.md`/`README.md` inside the dir > same-name file in the dir (`advanced/advanced.md`)
  > root same-name file (`advanced.md`). Slugs are hierarchical (`advanced/webhooks`).
- **Slug normalization**: TitleCase and spaced filenames become kebab-case
  (`Quick-Start.md` → `quick-start`, `admin/Menu-and-Pages.md` → `admin/menu-and-pages`).
- **Front matter** parsed for `title`, `menu_order`, `parent`, `meta_description` (all
  optional). Front matter is stripped from stored content. `menu_order` is only written when
  present in front matter, so **admin drag-and-drop ordering survives re-imports**.
- **Link rewriting**: flat wiki-style links (`[Webhooks](Advanced-Webhooks)`), `[[wikilinks]]`,
  and relative `.md` links are translated to `/documentation/{pkg}/{slug}`, resolved against
  the actual set of imported slugs.
- **Ignore rules** (built-in + opt-out): skips `_Sidebar.md`/`_`-prefixed files, and the
  dirs `plans`, `design`, `benchmarks`, `node_modules`, `vendor`. Per-file opt-out via a
  `.docsignore` file in `docs/` (gitignore-style) or `draft: true` / `hidden: true` front
  matter.

## Real-repo validation already run (in ~/Code/ArtisanPack UI Packages)

Parser output looked correct for forms (39 pages), cms-framework (52), react/vue, hooks,
visual-editor (plans/ + design/ correctly excluded), core.

**Action needed before importing some packages** — a few internal/planning files sit at
`docs/` root and are NOT auto-excluded. Add a `.docsignore` or `draft: true` to them:
- `core/docs/install-packages-command-plan.md`
- `cms-framework/docs/developer/Skipped-Tests.md`, `Test-Coverage.md`
- several `visual-editor/docs/*` planning files at root

## Known caveat: the API endpoint

New route: `POST /api/v1/packages/{package}/import-docs` (for a GitHub Actions release
workflow to trigger an import). It's on `auth:sanctum` to match the existing `api.php`
convention — **but Laravel Sanctum is not actually installed** in this repo (no
`vendor/laravel/sanctum`, no `sanctum` guard in `config/auth.php`). So this route (and the
pre-existing `packages` apiResource) won't authenticate at runtime until Sanctum is added.
Per Jacob's decision we left it as-is for now. The controller logic is tested directly; the
route test only asserts registration + middleware. If you want it functional, that's a
follow-up: `composer require laravel/sanctum`, publish config, add the `sanctum` guard.

## Files

New: `app/Services/GitHubDocsService.php`,
`Modules/Packages/app/Http/Controllers/ImportDocumentationController.php`,
`database/migrations/2026_05_22_000000_add_docs_url_to_packages.php`,
tests under `tests/Feature/{Services,Jobs,Api}`.

Changed: `Package` model, `WikiServiceFactory`, `ImportWikiDocumentation`, Add/Edit Package
Livewire + views, `HasPackageUrlValidation`, `PackageRequest`, `PackageResource`,
`Modules/Packages/routes/api.php`.
