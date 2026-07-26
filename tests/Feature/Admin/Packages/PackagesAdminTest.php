<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Testing\AssertableInertia;
use Modules\Packages\Package;

function verifiedUser(): User
{
    return User::factory()->create(['email_verified_at' => now()]);
}

it('lists packages for verified users', function (): void {
    $packages = Package::factory()->count(3)->create();

    $this->actingAs(verifiedUser())
        ->get(route('dashboard.packages'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Packages::Admin/Index')
            ->has('packages', 3)
            ->where('packages.0.name', $packages->sortBy('name')->first()->name)
            ->where('create_url', route('dashboard.packages.add'))
        );
});

it('redirects guests from the packages list to login', function (): void {
    $this->get(route('dashboard.packages'))->assertRedirect(route('login'));
});

it('redirects unverified users from the packages list', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('dashboard.packages'))
        ->assertRedirect(route('verification.notice'));
});

it('renders the create form', function (): void {
    $this->actingAs(verifiedUser())
        ->get(route('dashboard.packages.add'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('Packages::Admin/Create'));
});

it('creates a package with valid data and redirects to edit', function (): void {
    $response = $this->actingAs(verifiedUser())
        ->post(route('dashboard.packages.store'), [
            'name' => 'Test Package',
            'slug' => 'test-package',
            'wiki_url' => 'https://github.com/owner/repo/wiki',
            'docs_url' => '',
            'changelog_url' => 'https://github.com/owner/repo/blob/main/CHANGELOG.md',
            'icon' => 'ap.puzzle',
            'version' => '1.0.0',
            'package_registry' => 'packagist',
        ]);

    $package = Package::where('slug', 'test-package')->firstOrFail();

    $response->assertRedirect(route('dashboard.packages.edit', $package));
    expect($package->name)->toBe('Test Package');
    expect($package->wiki_url)->toBe('https://github.com/owner/repo/wiki');
    expect($package->docs_url)->toBeNull();
});

it('creates a package with only a docs URL', function (): void {
    $this->actingAs(verifiedUser())
        ->post(route('dashboard.packages.store'), [
            'name' => 'Docs Package',
            'slug' => 'docs-package',
            'wiki_url' => '',
            'docs_url' => 'https://github.com/owner/repo',
            'changelog_url' => 'https://github.com/owner/repo/blob/main/CHANGELOG.md',
        ])
        ->assertSessionHasNoErrors();

    expect(Package::where('slug', 'docs-package')->first()->docs_url)
        ->toBe('https://github.com/owner/repo');
});

it('requires either a wiki or docs URL when creating a package', function (): void {
    $this->actingAs(verifiedUser())
        ->post(route('dashboard.packages.store'), [
            'name' => 'No Source',
            'slug' => 'no-source',
            'wiki_url' => '',
            'docs_url' => '',
            'changelog_url' => 'https://github.com/owner/repo/blob/main/CHANGELOG.md',
        ])
        ->assertSessionHasErrors(['wiki_url', 'docs_url']);
});

it('rejects non-GitHub docs URLs', function (): void {
    $this->actingAs(verifiedUser())
        ->post(route('dashboard.packages.store'), [
            'name' => 'Gitlab Docs',
            'slug' => 'gitlab-docs',
            'docs_url' => 'https://gitlab.com/owner/repo',
            'changelog_url' => 'https://github.com/owner/repo/blob/main/CHANGELOG.md',
        ])
        ->assertSessionHasErrors(['docs_url']);
});

it('rejects non-GitHub/GitLab wiki URLs', function (): void {
    $this->actingAs(verifiedUser())
        ->post(route('dashboard.packages.store'), [
            'name' => 'Bitbucket',
            'slug' => 'bitbucket',
            'wiki_url' => 'https://bitbucket.org/owner/repo/wiki',
            'changelog_url' => 'https://github.com/owner/repo/blob/main/CHANGELOG.md',
        ])
        ->assertSessionHasErrors(['wiki_url']);
});

it('rejects non-GitHub/GitLab changelog URLs', function (): void {
    $this->actingAs(verifiedUser())
        ->post(route('dashboard.packages.store'), [
            'name' => 'Bitbucket Changelog',
            'slug' => 'bitbucket-changelog',
            'wiki_url' => 'https://github.com/owner/repo/wiki',
            'changelog_url' => 'https://bitbucket.org/owner/repo/blob/main/CHANGELOG.md',
        ])
        ->assertSessionHasErrors(['changelog_url']);
});

it('rejects invalid package registry values', function (): void {
    $this->actingAs(verifiedUser())
        ->post(route('dashboard.packages.store'), [
            'name' => 'Test',
            'slug' => 'test',
            'wiki_url' => 'https://github.com/owner/repo/wiki',
            'changelog_url' => 'https://github.com/owner/repo/blob/main/CHANGELOG.md',
            'package_registry' => 'invalid',
        ])
        ->assertSessionHasErrors(['package_registry']);
});

it('renders the edit form with the package payload', function (): void {
    $package = Package::factory()->create(['name' => 'Edit Me']);

    $this->actingAs(verifiedUser())
        ->get(route('dashboard.packages.edit', $package))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Packages::Admin/Edit')
            ->where('package.id', $package->id)
            ->where('package.name', 'Edit Me')
            ->where('update_url', route('dashboard.packages.update', $package))
            ->where('destroy_url', route('dashboard.packages.destroy', $package))
        );
});

it('updates a package', function (): void {
    $package = Package::factory()->create([
        'name' => 'Original',
        'version' => '1.0.0',
    ]);

    $this->actingAs(verifiedUser())
        ->patch(route('dashboard.packages.update', $package), [
            'name' => 'Renamed',
            'slug' => $package->slug,
            'wiki_url' => $package->wiki_url,
            'docs_url' => '',
            'changelog_url' => $package->changelog_url,
            'version' => '2.0.0',
            'package_registry' => 'packagist',
        ])
        ->assertRedirect(route('dashboard.packages.edit', $package));

    expect($package->fresh()->name)->toBe('Renamed');
    expect($package->fresh()->version)->toBe('2.0.0');
});

it('deletes a package and redirects back to the list', function (): void {
    $package = Package::factory()->create();

    $this->actingAs(verifiedUser())
        ->delete(route('dashboard.packages.destroy', $package))
        ->assertRedirect(route('dashboard.packages'));

    expect(Package::find($package->id))->toBeNull();
});

it('rejects unauthenticated writes', function (): void {
    $package = Package::factory()->create();

    $this->post(route('dashboard.packages.store'), [])->assertRedirect(route('login'));
    $this->patch(route('dashboard.packages.update', $package), [])->assertRedirect(route('login'));
    $this->delete(route('dashboard.packages.destroy', $package))->assertRedirect(route('login'));
});

it('forbids editors from creating packages', function (): void {
    $editor = User::factory()->editor()->create(['email_verified_at' => now()]);

    $this->actingAs($editor)
        ->get(route('dashboard.packages.add'))
        ->assertForbidden();

    $this->actingAs($editor)
        ->post(route('dashboard.packages.store'), [])
        ->assertForbidden();
});

it('forbids editors from deleting packages', function (): void {
    $editor = User::factory()->editor()->create(['email_verified_at' => now()]);
    $package = Package::factory()->create();

    $this->actingAs($editor)
        ->delete(route('dashboard.packages.destroy', $package))
        ->assertForbidden();

    expect(Package::find($package->id))->not->toBeNull();
});

it('allows editors to update packages', function (): void {
    $editor = User::factory()->editor()->create(['email_verified_at' => now()]);
    $package = Package::factory()->create();

    $this->actingAs($editor)
        ->get(route('dashboard.packages.edit', $package))
        ->assertOk();
});

it('exposes can_create and can_delete flags on the packages list', function (): void {
    $admin = User::factory()->admin()->create(['email_verified_at' => now()]);
    $editor = User::factory()->editor()->create(['email_verified_at' => now()]);

    $this->actingAs($admin)
        ->get(route('dashboard.packages'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('can_create', true)
            ->where('can_delete', true)
        );

    $this->actingAs($editor)
        ->get(route('dashboard.packages'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('can_create', false)
            ->where('can_delete', false)
        );
});
