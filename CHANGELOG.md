# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

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
