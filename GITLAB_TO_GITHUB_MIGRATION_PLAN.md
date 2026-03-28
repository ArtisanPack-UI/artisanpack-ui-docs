# Migration Plan: GitLab → GitHub Wiki Documentation

## Current State Analysis

The application currently:
- Fetches wiki documentation from GitLab wikis via GitLab API v4
- Fetches changelog files from GitLab repositories
- Stores an encrypted GitLab token in settings
- Uses `GitLabService` to interact with GitLab API
- Has two main jobs: `ImportWikiDocumentation` and `ImportChangelog`
- Supports parent/child documentation relationships, YAML front matter, internal link updates

### Key Files
- `app/Services/GitLabService.php` - GitLab API integration
- `app/Jobs/ImportWikiDocumentation.php` - Wiki import job
- `app/Jobs/ImportChangelog.php` - Changelog import job
- `Modules/Admin/app/Livewire/SettingsPage.php` - Settings management
- `Modules/Packages/app/Package.php` - Package model

---

## Migration Plan Overview

### Phase 1: Research & API Analysis

#### 1.1 Understand GitHub Wiki API differences
- GitHub wikis are Git repositories (`.wiki.git` suffix)
- GitHub has limited REST API support for wikis (can list pages, get page content)
- Alternative: Clone wiki repo and parse files directly
- **Decision needed**: REST API vs Git clone approach

#### 1.2 Research GitHub API authentication
- Personal Access Tokens (PAT) vs GitHub Apps
- Required permissions/scopes for wiki access
- Rate limiting considerations

#### 1.3 Analyze URL structure differences
- **GitLab**: `https://gitlab.com/group/project/-/wikis/page-name`
- **GitHub**: `https://github.com/owner/repo/wiki/page-name`
- **GitHub Wiki Git**: `https://github.com/owner/repo.wiki.git`

---

### Phase 2: Service Layer Creation

#### 2.1 Create `GitHubService` (parallel to `GitLabService`)

**Methods needed**:
- `getWikiPages(string $wikiUrl): array` - List all wiki pages
- `getWikiPage(string $wikiUrl, string $slug): array` - Get individual page content
- `getFileContent(string $fileUrl): string` - Get changelog/file from repo

**Helper methods**:
- `extractRepoPath(string $url): string` - Parse owner/repo from URL
- `extractFilePathFromUrl(string $url): array` - Parse file info

#### 2.2 API Implementation Decision Points

**Option A: Use GitHub REST API**
- **Pros**: No Git operations, simpler
- **Cons**: Limited wiki API, may not support all features

**Option B: Clone wiki Git repos**
- **Pros**: Full access to all wiki content
- **Cons**: More complex, requires disk space, Git operations

**Recommended**: Start with REST API, fall back to Git clone if needed

---

### Phase 3: Database & Configuration Updates

#### 3.1 Settings Updates
- Add `github_token` to settings (encrypted, like `gitlab_token`)
- Keep both tokens during transition period
- Update `SettingsPage` Livewire component
- Update settings view/form

#### 3.2 Package Model Updates (if needed)
- Determine if `wiki_url` and `changelog_url` can remain generic
- Or add: `wiki_source` enum field (`gitlab` | `github`)
- Migration to handle existing packages

#### 3.3 Migration Strategy Considerations
- Will you migrate all packages at once?
- Or support both GitLab and GitHub simultaneously?
- Database flag to track which service to use per package?

---

### Phase 4: Job Updates

#### 4.1 Update `ImportWikiDocumentation` Job
- Inject service abstraction or conditional logic
- If conditional: Check package source, use appropriate service
- Maintain existing features:
  - YAML front matter extraction
  - H1 header extraction
  - Internal link updates (update URL patterns for GitHub)
  - Parent/child relationships
  - Meta description generation

#### 4.2 Update `ImportChangelog` Job
- Similar conditional logic or abstraction
- GitHub file API differences (raw content URLs)
- Authentication header differences

#### 4.3 Service Abstraction Option (recommended for clean code)
- Create interface: `WikiServiceInterface`
- Implement: `GitLabWikiService`, `GitHubWikiService`
- Factory pattern to instantiate correct service
- Jobs depend on interface, not concrete implementations

**Example structure**:
```text
app/
├── Contracts/
│   └── WikiServiceInterface.php
├── Services/
│   ├── GitLabService.php (rename to GitLabWikiService)
│   ├── GitHubWikiService.php
│   └── WikiServiceFactory.php
```

---

### Phase 5: Link Processing Updates

#### 5.1 Update internal link conversion in `ImportWikiDocumentation`
- Current: Converts GitLab wiki links → site URLs
- Update: Also handle GitHub wiki link patterns
- Test with various markdown link formats

#### 5.2 Update URL validation/parsing
- Package edit forms should accept GitHub URLs
- Validate GitHub wiki URL formats
- Validate GitHub file URLs for changelogs

---

### Phase 6: Testing Strategy

#### 6.1 Unit Tests
- Create `GitHubServiceTest` (mirror `GitLabServiceTest`)
- Test URL parsing for GitHub formats
- Test API response handling
- Mock GitHub API responses

#### 6.2 Feature Tests
- Update `ImportWikiDocumentationTest` to test GitHub sources
- Update `ImportChangelogTest` for GitHub
- Test both services if supporting dual-mode
- Test link conversion for GitHub wikis

#### 6.3 Integration Testing
- Test with real GitHub wiki (in test mode)
- Verify all markdown features work
- Verify parent/child relationships
- Verify YAML front matter handling

---

### Phase 7: UI & Admin Updates

#### 7.1 Settings Page
- Add GitHub token field
- Help text explaining GitHub PAT setup
- Link to GitHub token creation page
- Validation for token format

#### 7.2 Package Management
- Update add/edit package forms
- Accept GitHub wiki URLs
- Accept GitHub file URLs for changelogs
- Validation for GitHub URL formats
- Possibly add visual indicator of source (GitLab vs GitHub)

#### 7.3 Documentation Display (if needed)
- Check if any display logic is GitLab-specific
- Update breadcrumbs/navigation if needed

---

### Phase 8: Deployment & Migration

#### 8.1 Deployment Steps
1. Deploy new code with both services
2. Add GitHub token to production settings
3. Test with one package first
4. Update package URLs in admin panel
5. Trigger re-import jobs
6. Verify documentation displays correctly

#### 8.2 Data Migration
- **Option A**: Manual update of package URLs in admin
- **Option B**: Migration script to convert URLs
- **Option C**: Leave old packages, only new packages use GitHub

#### 8.3 Rollback Plan
- Keep GitLab service code
- Keep GitLab token in settings
- Can revert URLs if issues arise

---

## Key Technical Decisions Needed

1. **Dual Support vs Full Migration?**
   - Support both GitLab and GitHub simultaneously?
   - Or migrate all packages to GitHub?

2. **Service Architecture**
   - Interface/abstraction layer vs conditional logic?
   - Factory pattern vs dependency injection?

3. **GitHub API Approach**
   - REST API only vs Git clone fallback?
   - How to handle GitHub API rate limits?

4. **URL Storage**
   - Keep current `wiki_url` generic field?
   - Or add `wiki_source` enum to track provider?

5. **Migration Timeline**
   - All at once vs gradual package migration?
   - Transition period duration?

---

## GitHub API Specifics to Research

### Wiki Endpoints
- `GET /repos/{owner}/{repo}/pages` - List wiki pages
- `GET /repos/{owner}/{repo}/pages/{page_name}` - Get page
- May require `repo` scope on token

### File Content (Changelog)
- `GET /repos/{owner}/{repo}/contents/{path}` - Get file
- Or raw URL: `https://raw.githubusercontent.com/owner/repo/branch/path`

### Authentication
- Header: `Authorization: Bearer {token}` or `Authorization: token {token}`
- Token scopes needed: `repo` (for wikis and private repos)

---

## Recommended Implementation Order

1. Research GitHub Wiki API capabilities (confirm REST API works for your needs)
2. Create `GitHubService` with basic wiki fetching
3. Add GitHub token to settings
4. Create abstraction layer (interface + factory)
5. Update jobs to use abstraction
6. Update link processing for GitHub
7. Write comprehensive tests
8. Update UI for GitHub URLs
9. Test with one package
10. Migrate remaining packages
11. (Optional) Remove GitLab code if fully migrated

---

## Estimated Complexity by Component

| Component | Complexity | Notes |
|-----------|-----------|-------|
| GitHubService | Medium | Similar to GitLabService but different API |
| Service Abstraction | Low-Medium | Interface + factory pattern |
| Job Updates | Low | Minimal changes if using abstraction |
| Link Processing | Low | URL pattern updates |
| Settings | Low | Add field, similar to existing |
| Testing | Medium | Comprehensive test coverage needed |
| UI Updates | Low | Form field updates |
| Migration | Low-Medium | Depends on package count |

---

## Notes & Considerations

### Potential Challenges
- GitHub wiki API may be less feature-rich than GitLab
- Need to handle authentication differently
- URL patterns are significantly different
- May need to handle rate limiting for large wikis

### Benefits of Migration
- All packages now on GitHub platform
- Consistent tooling and workflow
- Potentially better GitHub integration opportunities
- Simplified infrastructure (one platform)

### Future Enhancements
- Automatic webhook-triggered updates when wiki changes
- Better error handling and retry logic
- Progress indicators for long-running imports
- Diff viewing for documentation changes

---

## Appendix: API Examples

### GitHub Wiki API Example
```bash
# List wiki pages
curl -H "Authorization: Bearer TOKEN" \
  https://api.github.com/repos/OWNER/REPO/pages

# Get specific page
curl -H "Authorization: Bearer TOKEN" \
  https://api.github.com/repos/OWNER/REPO/pages/PAGE_NAME
```

### GitHub File Content Example
```bash
# Get file content
curl -H "Authorization: Bearer TOKEN" \
  https://api.github.com/repos/OWNER/REPO/contents/CHANGELOG.md

# Get raw file content
curl -H "Authorization: Bearer TOKEN" \
  https://raw.githubusercontent.com/OWNER/REPO/main/CHANGELOG.md
```

---

**Document Version**: 1.0
**Created**: 2026-02-14
**Last Updated**: 2026-02-14
