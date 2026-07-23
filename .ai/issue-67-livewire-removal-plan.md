# Issue #67 — Livewire stack incremental removal plan

Branch: `chore/67-remove-livewire-stack-incrementally`. Companion to milestone v3.0.

## Scope

Per issue #67, remove `livewire/livewire`, `livewire/volt`, `livewire/flux`, and
`mhmiton/laravel-modules-livewire` — plus every view, class, config, provider, route,
and JS import that depends on them — **incrementally as pages port to Inertia**.

The wording ("incrementally as pages port to Inertia") makes #67 a planning/tracking
issue at this point in the milestone: the Inertia stack does not land until #68, so the
Livewire packages cannot be removed from `composer.json` yet without breaking every route
in the app. Individual artifacts are retired by the specific port/removal issues that
follow. Final composer + npm removal is deferred to #114 ("Remove dead dependencies").

This document is the source of truth for what needs to come out and which issue takes
it out.

## Method

Grepped `composer.json`, `package.json`, `app/`, `bootstrap/`, `config/`, `routes/`,
`resources/views/`, and every `Modules/*/` tree for `Livewire`, `Volt`, `Flux`,
`livewire`, `volt`, `flux`, `livewire-drag-and-drop`, and `livewire:navigated` on the
`release/3.0` HEAD (`06b0171`). Every hit is captured below and mapped to the issue that
retires it.

## Inventory

### Composer packages — deferred to #114

| Package | Constraint | Retired by |
|---|---|---|
| `livewire/livewire` | `^4.0` | #114 |
| `livewire/volt` | `^1.7` | #114 |
| `livewire/flux` | `^2.1.1` | #114 |
| `mhmiton/laravel-modules-livewire` | `^7.0` | #114 |
| `artisanpack-ui/livewire-ui-components` | `^2.0` | #114 |

### npm packages — deferred to #114

| Package | Constraint | Retired by |
|---|---|---|
| `@artisanpack-ui/livewire-drag-and-drop` | `^2.0.0` | #114 |

### `app/` — main application

| Artifact | Retired by |
|---|---|
| `app/Providers/VoltServiceProvider.php` (registered in `bootstrap/providers.php`) | #80 (last Volt route retires with `/settings/*`) |
| `app/Livewire/Actions/Logout.php` (referenced from `Modules/Auth/routes/web.php:39`) | #81 |

### `resources/views/` — root

| Artifact | Retired by |
|---|---|
| `resources/views/livewire/settings/delete-user-form.blade.php` | #80 |
| `resources/views/livewire/settings/password.blade.php` | #80 |
| `resources/views/livewire/settings/appearance.blade.php` | #80 |
| `resources/views/livewire/settings/profile.blade.php` | #80 |
| `resources/views/livewire/auth/*` (6 files: `login`, `register`, `forgot-password`, `reset-password`, `confirm-password`, `verify-email`) | #81 |
| `resources/views/flux/icon/*` (4 files: `layout-grid`, `folder-git-2`, `chevrons-up-down`, `book-open-text`) | #114 (Flux icon overrides are only meaningful while Flux is installed) |
| `resources/views/flux/navlist/group.blade.php` | #114 |

### `config/`

| Artifact | Retired by |
|---|---|
| `config/livewire-ui-components.php` | #114 (paired with `artisanpack-ui/livewire-ui-components` uninstall) |
| `config/modules-livewire.php` | #114 (paired with `mhmiton/laravel-modules-livewire` uninstall) |
| `config/artisanpack.php` → `livewire-ui-components` block | #114 |

### `routes/`

| Artifact | Retired by |
|---|---|
| `routes/web.php` → `use Livewire\Volt\Volt;` + 3 `Volt::route()` calls for `settings/{profile,password,appearance}` | #80 |

### `Modules/Auth`

| Artifact | Retired by |
|---|---|
| `Modules/Auth/app/Livewire/Auth/Login.php` | #81 (after #76 ports `/login` to Inertia + Fortify) |
| `Modules/Auth/app/Livewire/Auth/Register.php` | #81 (Fortify install in #75 disables registration; class is deleted with the module) |
| `Modules/Auth/app/Livewire/Auth/ForgotPassword.php` | #81 (after #79) |
| `Modules/Auth/app/Livewire/Auth/ResetPassword.php` | #81 (after #79) |
| `Modules/Auth/app/Livewire/Auth/ConfirmPassword.php` | #81 |
| `Modules/Auth/app/Livewire/Auth/VerifyEmail.php` | #81 (after #77) |
| `Modules/Auth/resources/views/livewire/auth/*` (6 blades) | #81 |
| `Modules/Auth/routes/web.php` — 6 `use Modules\Auth\Livewire\...` + route bindings | #81 (rewritten to Fortify + Inertia route file, or deleted if Fortify + `routes/auth.php` cover it) |

### `Modules/Core`

| Artifact | Retired by |
|---|---|
| `Modules/Core/app/Livewire/HomePage.php` | #82 (Home page port) |
| `Modules/Core/resources/views/livewire/home-page.blade.php` | #82 |
| `Modules/Core/routes/web.php` — `use Modules\Core\Livewire\HomePage;` + binding | #82 |
| `Modules/Core/resources/views/partials/secondary-sidebar.blade.php` — `livewire:navigated` listener (JS re-init hook) | #82 (Inertia dispatches `inertia:navigated` instead; script rewritten during the Home / shared-layout port) |

### `Modules/Admin`

| Artifact | Retired by |
|---|---|
| `Modules/Admin/app/Livewire/Dashboard.php` | #90 (Admin shell port) |
| `Modules/Admin/app/Livewire/SettingsPage.php` | #96 (Site settings port) |
| `Modules/Admin/resources/views/livewire/dashboard.blade.php` | #90 |
| `Modules/Admin/resources/views/livewire/settings-page.blade.php` | #96 |
| `Modules/Admin/routes/web.php` — 2 `use Modules\Admin\Livewire\...` + bindings | #90 / #96 |
| `Modules/Admin/resources/assets/js/admin.js` — `import "@artisanpack-ui/livewire-drag-and-drop";` | #90 (JS bundle rewritten for React/Inertia admin shell) |

### `Modules/Packages`

| Artifact | Retired by |
|---|---|
| `Modules/Packages/app/Livewire/Admin/Packages.php` | #91 (Packages list + CRUD) |
| `Modules/Packages/app/Livewire/Admin/AddPackage.php` | #91 |
| `Modules/Packages/app/Livewire/Admin/EditPackage.php` | #91 |
| `Modules/Packages/app/Livewire/Admin/ManageDocumentation.php` | #92 (Docs manager with drag-and-drop) |
| `Modules/Packages/app/Livewire/Admin/Concerns/HasPackageUrlValidation.php` | #91 (concern moves to a FormRequest or is retired with `EditPackage` / `AddPackage`) |
| `Modules/Packages/app/Livewire/Public/Documentation.php` | #84 (Docs viewer port) |
| `Modules/Packages/app/Livewire/Public/Changelog.php` | #85 (Changelog viewer port) |
| `Modules/Packages/resources/views/livewire/admin/*` (4 blades) | #91 / #92 |
| `Modules/Packages/resources/views/livewire/public/documentation.blade.php` | #84 |
| `Modules/Packages/resources/views/livewire/public/changelog.blade.php` | #85 |
| `Modules/Packages/routes/web.php` — 6 `use Modules\Packages\Livewire\...` + bindings | #84 / #85 / #91 / #92 |
| `Modules/Packages/resources/assets/js/app.js` — `import LivewireDragAndDrop from '@artisanpack-ui/livewire-drag-and-drop';` | #92 (drag-and-drop reimplemented in React via the shared Inertia admin bundle) |

### `Modules/Pages`

| Artifact | Retired by |
|---|---|
| `Modules/Pages/app/Livewire/Admin/Pages.php` | #93 (Pages list + CRUD) |
| `Modules/Pages/app/Livewire/Admin/AddPage.php` | #93 |
| `Modules/Pages/app/Livewire/Admin/EditPage.php` | #93 |
| `Modules/Pages/app/Livewire/Admin/ManagePageOrder.php` | #94 (Page menu-order editor) |
| `Modules/Pages/app/Livewire/Public/Page.php` | #83 (Pages viewer port) |
| `Modules/Pages/resources/views/livewire/admin/*` (4 blades) | #93 / #94 |
| `Modules/Pages/resources/views/livewire/public/page.blade.php` | #83 |
| `Modules/Pages/routes/web.php` — 5 `use Modules\Pages\Livewire\...` + bindings | #83 / #93 / #94 |
| `Modules/Pages/resources/assets/js/app.js` — `import LivewireDragAndDrop from '@artisanpack-ui/livewire-drag-and-drop';` | #94 (drag-and-drop reimplemented in React) |

### `Modules/Users`

No Livewire code. `Modules/Users/routes/web.php` currently has no route bindings. All
users-management UI lands new in #95.

### `Modules/Admin` — scaffolding code

| Artifact | Retired by |
|---|---|
| `app/Console/Commands/OptionalPackagesCommand.php` — lines that shell out to `composer require mhmiton/laravel-modules-livewire` and `php artisan vendor:publish --tag=modules-livewire-config`, and the `@artisanpack-ui/livewire-drag-and-drop` entry in the npm list | #114 (this command bootstraps a fresh install; the Livewire lines come out when the composer/npm packages come out) |

## Removal ordering (canonical sequence)

1. **#75 → #80** — install Fortify, port every auth screen + `/settings/{profile,password,appearance}` to Inertia. Every `Volt::route(...)` call and every blade under `resources/views/livewire/{settings,auth}` becomes dead in this window.
2. **#81** — delete `Modules/Auth` Livewire classes, blades, `use` statements, `Modules/Auth/routes/web.php` bindings, `app/Livewire/Actions/Logout.php`. First module to fully leave Livewire.
3. **#82–#89** — port every public page (`/`, pages viewer, docs viewer, changelog viewer, redirects, sitemap, search overlay, error pages). Each port deletes the matching Livewire class + view + route binding in `Modules/Core` and `Modules/Packages` / `Modules/Pages`. The `livewire:navigated` JS hook in `Modules/Core/resources/views/partials/secondary-sidebar.blade.php` is rewritten to `inertia:navigated` in #82.
4. **#90–#96** — port every admin screen. Each admin port deletes the matching Livewire class + view + route binding in `Modules/Admin`, `Modules/Packages`, `Modules/Pages`. The three `Modules/*/resources/assets/js/app.js` imports of `@artisanpack-ui/livewire-drag-and-drop` are replaced with React drag-and-drop in the corresponding port (#90 / #92 / #94).
5. **#80 tail** — once the last `Volt::route()` is gone from `routes/web.php`, delete `app/Providers/VoltServiceProvider.php` and unregister it from `bootstrap/providers.php`.
6. **#114 — cutover** — `composer remove livewire/livewire livewire/volt livewire/flux mhmiton/laravel-modules-livewire artisanpack-ui/livewire-ui-components`, `npm uninstall @artisanpack-ui/livewire-drag-and-drop`, delete `config/livewire-ui-components.php`, `config/modules-livewire.php`, the `livewire-ui-components` block in `config/artisanpack.php`, `resources/views/flux/`, and prune the Livewire lines from `app/Console/Commands/OptionalPackagesCommand.php`. Only viable once every artifact above is retired.

## Verification checklist for #114

Before closing #114 the following must all be true (grep from repo root):

- `grep -rE "Livewire|Volt::|@livewire|@volt|livewire:navigated" --include="*.php" --include="*.blade.php" --include="*.js" .` returns nothing outside `vendor/`, `node_modules/`, `bootstrap/cache/`.
- `grep -E "livewire|volt|flux" composer.json` returns nothing.
- `grep -E "livewire" package.json` returns nothing.
- `resources/views/livewire/`, `resources/views/flux/`, `config/livewire-ui-components.php`, `config/modules-livewire.php`, `app/Providers/VoltServiceProvider.php`, `app/Livewire/` do not exist.
- `Modules/*/app/Livewire/` directories do not exist.
- `Modules/*/resources/views/livewire/` directories do not exist.
- Pest suite green (`php artisan test`), including the URL parity harness in #112.

## No code changes on this branch

This is a documentation-only branch — matches the precedent from issue #66. `composer.json`,
`package.json`, and every source file are left untouched. Removals happen on the retiring
issues above.
