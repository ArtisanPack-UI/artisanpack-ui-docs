# Issue #66 — `artisanpack-ui/*` composer package audit for Laravel 13

Branch: `chore/66-audit-artisanpack-composer-packages`. Companion to milestone v3.0.

## Scope

Per issue #66, audit composer constraints on the in-house `artisanpack-ui/*` packages
named in the milestone plan: `accessibility`, `core`, `icons`, `security`, `seo`,
`privacy`, `performance`, `analytics`, `react`. File follow-up issues on any package
still pinned to `^12.0` only.

The already-shipped Laravel 12 → 13 upgrade (issue #65, PR #125) bumped
`artisanpack-ui/livewire-ui-components` from `^1.0.0` to `^2.0` as part of the Livewire 4
migration; that package is covered here as well for completeness.

## Method

For packages currently in `composer.json`, the installed version's `require` block in
`vendor/artisanpack-ui/<pkg>/composer.json` is the authoritative constraint. For packages
not yet installed (queued behind separate install issues #97–#100), the latest published
release on Packagist (`https://repo.packagist.org/p2/artisanpack-ui/<pkg>.json`) is used.

## Findings

### Installed (present in `composer.json`)

| Package | Root constraint | Resolved version | Package's own Laravel constraint | L13 support |
|---|---|---|---|---|
| `artisanpack-ui/accessibility` | `^2.0` | 2.3.0 | `illuminate/support: ^12.0\|^13.0` | Yes |
| `artisanpack-ui/core` | `^1.0` | 1.2.0 | `illuminate/support: ^11.0\|^12.0\|^13.0` | Yes |
| `artisanpack-ui/icons` | `^2.0` | 2.2.0 | `illuminate/support: ^12.0 \|\| ^13.0` | Yes |
| `artisanpack-ui/livewire-ui-components` | `^2.0` | 2.1.0 | `illuminate/support: ^10.0\|^11.0\|^12.0\|^13.0` | Yes |
| `artisanpack-ui/security` | `^1.0` | 1.0.3 | `illuminate/support: >=5.3` | Yes |

### Not yet installed (Packagist latest)

| Package | Latest version | Package's own Laravel constraint | L13 support | Notes |
|---|---|---|---|---|
| `artisanpack-ui/seo` | 1.3.0 | `illuminate/support: ^10.0\|^11.0\|^12.0\|^13.0` | Yes | Install work tracked in #97 |
| `artisanpack-ui/privacy` | 1.1.0 | `illuminate/support: ^10.0\|^11.0\|^12.0\|^13.0` | Yes | Install work tracked in #98 |
| `artisanpack-ui/performance` | 1.1.0 | `illuminate/support: ^10.0\|^11.0\|^12.0\|^13.0` | Yes | Install work tracked in #99 |
| `artisanpack-ui/analytics` | 1.4.0 | `illuminate/support: ^11.0\|^12.0\|^13.0` | Yes | Install work tracked in #100 |
| `artisanpack-ui/react` | n/a | n/a | n/a | Not a Composer package. The `ArtisanPack-UI/react` GitHub repo is the npm monorepo (`@artisanpack-ui/react`, `@artisanpack-ui/tokens`, `@artisanpack-ui/react-laravel`) used by the v3 Inertia + React refactor. Out of scope for a composer audit; JS-side pinning belongs to the frontend workstream. |

## Result

Every Composer package in the audit list already advertises `^13.0` support in its own
`require` block. No package is pinned to `^12.0` only.

**No follow-up issues need to be filed.** The condition in issue #66 ("File follow-up
issues on any package pinned to `^12.0`") did not trigger for any package.

## No code changes

This is a documentation-only branch. `composer.json` is left untouched — none of the
current constraints need widening or bumping to get L13 compatibility.
