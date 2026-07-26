# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

## [3.0.0] - 2026-07-25

### Added
- Inertia.js v2 + React 19 + TypeScript frontend, replacing the Livewire/Volt/Flux stack
- Inertia SSR (Node process alongside PHP-FPM) for first-paint parity with server-rendered pages
- ArtisanPack UI design system adoption: dark-first theme, Poppins + Space Mono, neon triad on space-black grounds, `.ap-box` / gradient / glow language
- Design tokens as CSS custom properties (`resources/css/tokens/*.css`) wired into Tailwind v4 `@theme` so utilities like `bg-base`, `text-secondary`, `shadow-glow-gradient`, `rounded-box` map to AP-UI tokens
- Laravel Fortify for web auth: email verification, 2FA (with password + OTP confirmation), password reset (registration disabled)
- Laravel Sanctum-guarded JSON API under `/api/v1` for `artisanpackui.dev` and other consumers, with per-token ability scopes (`packages:read`, `packages:write`, `docs:read`, `docs:write`, `changelogs:read`, `changelogs:write`)
- Personal access token management at `/settings/api-tokens` with abilities checklist, `last_used_at`, revoke, and a 90-day rotation warning
- `php artisan artisanpack:issue-token` command to mint the first API token before the UI is available
- CORS + rate limiting scoped to the API surface
- Audit log for every write on both the web admin and the API (`audit_log` table capturing actor, source, resource type + id, action, JSON diff, IP, timestamp), viewable at `/dashboard/audit-log` with filters by resource, actor, and source
- React `SearchOverlay` (⌘K) replacing the Livewire spotlight
- Designed 404 and 500 error pages
- v1 API documentation

### Changed
- Upgraded Laravel 12 → 13 and PHP baseline to 8.4
- Redesigned every public and admin surface against the new design system while preserving §4.1 URL structure byte-identically
- Docs viewer redesigned to the "Precision rail" layout: sticky top bar (logo + ⌘K + theme + social) with a 2px `--grad-neon` hairline, then a `290px | 1fr | 264px` 3-column grid (sidebar tree · content · TOC)
- Docs orderer ported from Livewire to a React nested drag-and-drop implementation (1:1 UX parity), reusing the same reorder endpoint that's exposed on the API
- Authentication and settings routes rewired to Fortify + Inertia pages; existing session/logout flows preserved

### Removed
- Livewire stack: `livewire/livewire`, `livewire/volt`, `livewire/flux`, `mhmiton/laravel-modules-livewire`, `artisanpack-ui/livewire-ui-components`
- npm: `@artisanpack-ui/livewire-drag-and-drop`
- User registration (Fortify feature disabled — accounts are provisioned by an admin)

## [2.0.0] - 2026-03-28

### Added
- GitHub API integration via `GitHubService` for wiki and changelog imports
- Wiki service abstraction layer (`WikiServiceFactory`) supporting both GitHub and GitLab sources
- GitHub token management in admin settings
- URL validation for GitHub and GitLab wiki/changelog URLs
- Source badges in admin UI to indicate GitHub or GitLab origin
- GitHub issue and pull request templates
- CodeRabbit configuration for automated PR reviews
- GitHub Actions CI workflows for tests and linting on all pushes
- Wikilink syntax support (`[[Page Name]]` and `[[Page Name|Display Text]]`)

### Changed
- Migrated repository hosting from GitLab to GitHub
- `ImportWikiDocumentation` job now supports GitHub wiki cloning with subdirectory handling
- `ImportChangelog` job updated to fetch changelogs from GitHub repositories
- Internal documentation link processing updated for GitHub wiki URL patterns
- CI workflows now trigger on pushes to all branches (not just main/develop)

### Fixed
- Removed orphaned layout blade files referencing non-existent components
- Fixed Flux navlist icon component syntax
- Resolved test failures from scaffolded starter kit tests that didn't match app routing

### Removed
- Direct dependency on GitLab as the sole wiki/changelog source
