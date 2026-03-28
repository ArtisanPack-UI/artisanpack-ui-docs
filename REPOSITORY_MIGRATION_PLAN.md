# Repository Migration Plan: GitLab → GitHub

## Current State Analysis

### Repository Information
- **Current Location**: `git@gitlab.com:jacob-martella-web-design/artisanpack-ui/artisanpack-ui-website/artisanpack-ui-docs-website.git`
- **Target Platform**: GitHub
- **Deployment**: Laravel Cloud

### Current CI/CD Setup
- ✅ GitHub Actions workflows already configured:
  - `.github/workflows/tests.yml` - Runs Pest tests
  - `.github/workflows/lint.yml` - Runs Laravel Pint
- 📝 GitLab CI files exist but are drafts (not active):
  - `.gitlab-ci-draft.yml`
  - `.gitlab-ci-setup.md`

### Key Dependencies
- **Flux UI**: Requires authenticated composer access
  - Secrets: `FLUX_USERNAME`, `FLUX_LICENSE_KEY`
- **Laravel Cloud**: Deployment target
- **PHP 8.4** & **Node 22**

---

## Migration Plan Overview

### Phase 1: Pre-Migration Preparation

#### 1.1 GitHub Repository Setup
1. **Create GitHub repository**
   - Organization: Determine GitHub organization/user
   - Repository name: `artisanpack-ui-docs` (or preferred name)
   - Visibility: Public or Private
   - Initialize: Do NOT initialize with README, .gitignore, or license (will push existing repo)

2. **Configure repository settings**
   - Default branch: `main`
   - Delete branch protection (will configure after migration)
   - Disable: Issues, Wiki, Projects (if not needed, can enable later)

#### 1.2 Document Current State
1. **Export from GitLab** (if needed):
   - Open merge requests (screenshot or note status)
   - Open issues (if any)
   - Protected branch rules
   - CI/CD variables (list, don't export values)
   - Wiki pages (if any)
   - Milestones, labels

2. **List current GitLab secrets/variables**:
   - `FLUX_USERNAME`
   - `FLUX_LICENSE_KEY`
   - `LARAVEL_CLOUD_API_TOKEN` (if using API deployment)
   - `LARAVEL_CLOUD_PROJECT_ID`
   - `LARAVEL_CLOUD_STAGING_PROJECT_ID`
   - Any database credentials for testing
   - Any other custom variables

---

### Phase 2: Repository Migration

#### 2.1 Mirror Repository to GitHub

**Option A: Push Mirror (Recommended - Preserves Everything)**
```bash
# Clone the repository as a mirror (includes all branches, tags, refs)
git clone --mirror git@gitlab.com:jacob-martella-web-design/artisanpack-ui/artisanpack-ui-website/artisanpack-ui-docs-website.git

# Navigate into the cloned directory
cd artisanpack-ui-docs-website.git

# Add GitHub as the new remote
git remote add github git@github.com:OWNER/REPO.git

# Push everything to GitHub
git push --mirror github

# Clean up (optional)
cd ..
rm -rf artisanpack-ui-docs-website.git
```

**Option B: Update Existing Local Clone**
```bash
# In your current working directory
cd /Users/jacobmartella/Herd/artisanpack-ui-docs

# Add GitHub as a new remote
git remote add github git@github.com:OWNER/REPO.git

# Push all branches and tags
git push github --all
git push github --tags

# Verify
git remote -v
```

#### 2.2 Verify Migration
- [ ] All branches pushed (main, develop, update/gitlab-to-github, etc.)
- [ ] All tags pushed
- [ ] Commit history intact
- [ ] `.github` directory present
- [ ] GitHub Actions workflows appear in Actions tab

---

### Phase 3: GitHub Configuration

#### 3.1 Secrets & Environment Variables

Navigate to **Settings → Secrets and variables → Actions**

**Repository Secrets** (for all environments):
```bash
FLUX_USERNAME=<your-flux-username>
FLUX_LICENSE_KEY=<your-flux-license-key>
```

**Environment-Specific Secrets**:

Create environments:
1. **Testing** (for test workflows)
   - `FLUX_USERNAME`
   - `FLUX_LICENSE_KEY`

2. **Production** (for deployment)
   - `LARAVEL_CLOUD_API_TOKEN` (if using API deployment)
   - `LARAVEL_CLOUD_PROJECT_ID`
   - Any production-specific secrets

3. **Staging** (for staging deployment)
   - `LARAVEL_CLOUD_API_TOKEN`
   - `LARAVEL_CLOUD_STAGING_PROJECT_ID`
   - Any staging-specific secrets

#### 3.2 Branch Protection Rules

Configure for **main** branch:
- **Settings → Branches → Add branch protection rule**

Recommended settings:
- [x] Require a pull request before merging
  - [x] Require approvals: 1 (or more)
  - [x] Dismiss stale pull request approvals when new commits are pushed
- [x] Require status checks to pass before merging
  - [x] Require branches to be up to date before merging
  - Required status checks:
    - `ci` (from tests.yml)
    - `quality` (from lint.yml)
- [x] Require conversation resolution before merging
- [x] Do not allow bypassing the above settings
- [ ] Allow force pushes: **Disabled**
- [ ] Allow deletions: **Disabled**

Configure for **develop** branch (if used):
- Similar to main but potentially less strict
- May allow force pushes for active development (use caution)

#### 3.3 Repository Settings

**General Settings** (Settings → General):
- Repository name: `artisanpack-ui-docs`
- Description: "Documentation website for ArtisanPack UI packages"
- Topics/Tags: `laravel`, `documentation`, `artisanpack-ui`, `livewire`
- Default branch: `main`

**Features to enable/disable**:
- [x] Issues (if you want GitHub Issues)
- [ ] Projects (if you want GitHub Projects)
- [ ] Wiki (likely not needed if using custom wiki)
- [x] Discussions (optional - for community discussions)
- [x] Preserve this repository (Archive on deletion)

**Pull Request Settings**:
- [x] Allow squash merging
- [x] Allow merge commits
- [ ] Allow rebase merging (optional)
- [x] Always suggest updating pull request branches
- [x] Allow auto-merge
- [x] Automatically delete head branches

---

### Phase 4: CI/CD Updates

#### 4.1 Review & Enhance GitHub Actions

Your existing workflows are good! Optional enhancements:

**tests.yml enhancements**:
```yaml
# Add caching for better performance
- name: Cache Composer Dependencies
  uses: actions/cache@v4
  with:
    path: vendor
    key: ${{ runner.os }}-composer-${{ hashFiles('**/composer.lock') }}
    restore-keys: ${{ runner.os }}-composer-

# Add test reporting
- name: Upload Test Results
  if: always()
  uses: actions/upload-artifact@v4
  with:
    name: test-results
    path: storage/logs/

# Add code coverage (if desired)
- name: Run Tests with Coverage
  run: ./vendor/bin/pest --coverage --coverage-clover=coverage.xml

- name: Upload Coverage
  uses: codecov/codecov-action@v4
  with:
    file: ./coverage.xml
```

**lint.yml consideration**:
- Currently auto-fixes are commented out
- Decision: Keep manual fixing, or enable auto-commit?

#### 4.2 Create Deployment Workflow

If using Laravel Cloud API deployment:

**`.github/workflows/deploy.yml`**:
```yaml
name: Deploy

on:
  push:
    branches:
      - main  # Production
      - develop  # Staging

jobs:
  deploy-production:
    if: github.ref == 'refs/heads/main'
    runs-on: ubuntu-latest
    environment: production
    needs: [test, lint]  # Ensure tests pass first

    steps:
      - name: Deploy to Laravel Cloud
        run: |
          curl -X POST "https://cloud.laravel.com/api/projects/${{ secrets.LARAVEL_CLOUD_PROJECT_ID }}/deployments" \
            -H "Authorization: Bearer ${{ secrets.LARAVEL_CLOUD_API_TOKEN }}" \
            -H "Content-Type: application/json" \
            -d "{\"commit\": \"${{ github.sha }}\"}"

  deploy-staging:
    if: github.ref == 'refs/heads/develop'
    runs-on: ubuntu-latest
    environment: staging
    needs: [test, lint]

    steps:
      - name: Deploy to Staging
        run: |
          curl -X POST "https://cloud.laravel.com/api/projects/${{ secrets.LARAVEL_CLOUD_STAGING_PROJECT_ID }}/deployments" \
            -H "Authorization: Bearer ${{ secrets.LARAVEL_CLOUD_API_TOKEN }}" \
            -H "Content-Type: application/json" \
            -d "{\"commit\": \"${{ github.sha }}\"}"
```

#### 4.3 Configure Laravel Cloud

Update Laravel Cloud to work with GitHub:

1. **In Laravel Cloud Dashboard**:
   - Go to your project settings
   - Under "Source Control" or "Deployments":
     - Change repository URL to GitHub URL
     - Update webhook to point to GitHub
   - Under "Auto Deploy":
     - Set to deploy from GitHub
     - Configure branch: `main` for production
     - Enable "Wait for CI/CD" if you want tests to pass first

2. **GitHub Webhook** (may be automatic):
   - Go to GitHub Settings → Webhooks
   - Should see Laravel Cloud webhook
   - Verify it's active and receiving events

---

### Phase 5: Team & Access Management

#### 5.1 Invite Collaborators
- **Settings → Collaborators and teams**
- Add team members with appropriate roles:
  - **Admin**: Full access
  - **Write**: Push, create PRs, merge (without bypassing protection)
  - **Read**: View and clone only

#### 5.2 Configure Team Permissions (if using GitHub Organization)
- Create teams: Developers, Maintainers, etc.
- Assign repository access to teams
- Configure code review assignments

---

### Phase 6: Issue & PR Migration (Optional)

#### 6.1 Migrate Open Issues
If you have open GitLab issues:
- **Option A**: Manual migration (for small number)
  - Create equivalent GitHub issues
  - Reference original GitLab issue
  - Close GitLab issues with link to GitHub

- **Option B**: Use migration tool
  - [node-gitlab-2-github](https://github.com/piceaTech/node-gitlab-2-github)
  - [gitlab-to-github](https://github.com/matti/gitlab-to-github)

#### 6.2 Migrate Open Merge Requests
For open MRs:
1. Note the changes and branch names
2. Create equivalent Pull Requests on GitHub
3. Link to original GitLab MR for context
4. Close GitLab MRs with migration note

---

### Phase 7: Documentation Updates

#### 7.1 Update Repository URLs in Code

Files to update:
- [ ] `README.md` - Update badges, clone URLs, links
- [ ] `CONTRIBUTING.md` (if exists) - Update PR/issue links
- [ ] `composer.json` - Update repository URL (if present)
- [ ] `package.json` - Update repository URL
- [ ] Documentation files - Update any GitLab links

Search and replace:
```bash
# Find GitLab references
grep -r "gitlab.com" . --exclude-dir=node_modules --exclude-dir=vendor

# Update to GitHub URLs
# gitlab.com/... → github.com/...
```

#### 7.2 Update CI/CD Badge URLs

If you have badges in README:

```markdown
<!-- Old GitLab badges -->
![GitLab CI](https://gitlab.com/.../badges/.../pipeline.svg)

<!-- New GitHub badges -->
![Tests](https://github.com/OWNER/REPO/workflows/tests/badge.svg)
![Linter](https://github.com/OWNER/REPO/workflows/linter/badge.svg)
```

#### 7.3 Create GitHub-Specific Files (Optional)

**Pull Request Template** - `.github/pull_request_template.md`:
```markdown
## Description
<!-- Describe your changes -->

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Checklist
- [ ] Tests pass locally
- [ ] Code follows style guidelines (Pint)
- [ ] Documentation updated (if needed)
- [ ] No new warnings or errors

## Related Issues
Closes #
```

**Issue Templates** - `.github/ISSUE_TEMPLATE/`:
- `bug_report.md`
- `feature_request.md`

**Code Owners** - `.github/CODEOWNERS`:
```text
# Default owners for everything
*       @your-username

# Specific directories
/app/   @backend-team
/resources/  @frontend-team
```

---

### Phase 8: Update Local Development

#### 8.1 Update Local Git Remotes

For all team members:

```bash
# View current remotes
git remote -v

# Option A: Replace origin
git remote set-url origin git@github.com:OWNER/REPO.git

# Option B: Keep both (recommended during transition)
git remote rename origin gitlab
git remote add origin git@github.com:OWNER/REPO.git

# Verify
git remote -v

# Fetch from new remote
git fetch origin

# Set upstream for current branch
git branch --set-upstream-to=origin/main main
```

#### 8.2 Update Git Configurations

If you had GitLab-specific hooks or configurations:
```bash
# Check git config
cat .git/config

# Update any GitLab-specific URLs
git config --list | grep gitlab
```

---

### Phase 9: Testing & Validation

#### 9.1 Test Workflows
1. **Create test branch**:
   ```bash
   git checkout -b test/github-migration
   git push origin test/github-migration
   ```

2. **Create test Pull Request**:
   - Make a small change
   - Open PR to `main`
   - Verify:
     - [ ] CI workflows trigger automatically
     - [ ] Tests run successfully
     - [ ] Linting runs successfully
     - [ ] Branch protection enforced
     - [ ] Status checks appear

3. **Test deployment** (if applicable):
   - Merge test PR to `develop` (if staging enabled)
   - Verify staging deployment
   - Test with production (carefully!)

#### 9.2 Verify Permissions
- [ ] Team members can clone
- [ ] Team members can push (if allowed)
- [ ] Protected branches enforce rules
- [ ] Secrets accessible to workflows
- [ ] No unexpected access denied errors

---

### Phase 10: Cutover & Communication

#### 10.1 Final Cutover Steps

1. **Update Git remote to GitHub**:
   ```bash
   git remote set-url origin git@github.com:OWNER/REPO.git
   ```

2. **Make GitHub the primary**:
   - Update all bookmarks
   - Update deployment webhooks
   - Update external integrations

3. **Archive GitLab repository** (don't delete immediately):
   - Mark as read-only
   - Add README notice pointing to GitHub
   - Keep for historical reference (1-3 months)

#### 10.2 Team Communication

**Announcement template**:
```markdown
# Repository Migrated to GitHub

We've moved our repository from GitLab to GitHub!

**New Repository**: https://github.com/OWNER/REPO

## Action Required

1. Update your git remote:
   ```bash
   cd artisanpack-ui-docs
   git remote set-url origin git@github.com:OWNER/REPO.git
   git fetch origin
   ```

2. Update bookmarks and links

3. Open new PRs on GitHub (not GitLab)

4. Report any issues in GitHub Issues

## What Changed
- ✅ All code, branches, and tags migrated
- ✅ CI/CD running on GitHub Actions
- ✅ Branch protection configured
- ✅ Deployment to Laravel Cloud maintained

## Questions?
Contact: [team contact]
```

---

### Phase 11: Post-Migration Cleanup

#### 11.1 GitLab Repository
**After 1-4 weeks of successful GitHub operation**:

1. **Archive GitLab repository**:
   - Settings → General → Advanced → Archive project
   - Prevents new pushes but keeps history

2. **Update GitLab README**:
   ```markdown
   # ARCHIVED - Moved to GitHub

   This repository has been migrated to GitHub.

   **New Location**: https://github.com/OWNER/REPO

   This GitLab repository is archived for historical reference only.
   ```

3. **After 3-6 months** (optional):
   - Consider deleting GitLab repository
   - Ensure GitHub backup/export first
   - Export issues, MRs for archive if needed

#### 11.2 Cleanup Local Files

Remove GitLab-specific files:
```bash
git rm .gitlab-ci-draft.yml .gitlab-ci-setup.md
git commit -m "Remove GitLab CI files after migration to GitHub"
git push origin main
```

---

## Migration Checklist

### Pre-Migration
- [ ] Create GitHub repository
- [ ] Document GitLab state (issues, MRs, settings)
- [ ] List all secrets/variables
- [ ] Plan team communication
- [ ] Set migration date

### Migration Day
- [ ] Push repository to GitHub
- [ ] Verify all branches and tags
- [ ] Configure GitHub secrets
- [ ] Set up branch protection
- [ ] Configure repository settings
- [ ] Update Laravel Cloud integration
- [ ] Test GitHub Actions workflows
- [ ] Invite team members

### Post-Migration
- [ ] Update team git remotes
- [ ] Update documentation URLs
- [ ] Test deployment pipeline
- [ ] Create first GitHub PR
- [ ] Communicate to team
- [ ] Monitor for issues (1 week)
- [ ] Archive GitLab repository
- [ ] Remove GitLab CI files

---

## Rollback Plan

If migration encounters critical issues:

1. **Immediate**: Keep GitLab as active
   ```bash
   git remote set-url origin git@gitlab.com:...(original)
   ```

2. **Restore Laravel Cloud**: Point back to GitLab

3. **Identify issue**: Debug GitHub-specific problem

4. **Fix and retry**: Address issues and migrate again

---

## Key Decisions Needed

1. **GitHub Organization or Personal Account?**
   - Organization: Better for team management, permissions
   - Personal: Simpler, fewer settings

2. **Repository Name**:
   - Keep: `artisanpack-ui-docs-website`
   - Shorten: `artisanpack-ui-docs`
   - Other: `docs` (if under organization)

3. **Keep GitLab Repository?**
   - Archive and keep indefinitely
   - Archive and delete after X months
   - Delete immediately (not recommended)

4. **Deployment Strategy**:
   - Laravel Cloud auto-deploy on push
   - Laravel Cloud manual deployment via API
   - GitHub Actions triggers deployment

5. **Migration Timing**:
   - Immediate (this weekend)
   - After current sprint/milestone
   - Coordinate with team availability

---

## Resources

### Documentation
- [GitHub Docs: Importing from GitLab](https://docs.github.com/en/migrations/importing-source-code/using-the-command-line-to-import-source-code/importing-a-git-repository-using-the-command-line)
- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [Laravel Cloud Documentation](https://cloud.laravel.com/docs)

### Tools
- [GitHub CLI](https://cli.github.com/) - `gh` command for GitHub operations
- [GitLab to GitHub Migration Tools](https://github.com/piceaTech/node-gitlab-2-github)

### Support
- GitHub Support: https://support.github.com/
- Laravel Cloud Support: Via dashboard
- Team: [your contact info]

---

**Document Version**: 1.0
**Created**: 2026-02-14
**Last Updated**: 2026-02-14
**Status**: Draft - Pending Review
