<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Modules\Packages\Package;

function actAsToken(array $abilities): User
{
    $user = User::factory()->create();
    Sanctum::actingAs($user, $abilities);

    return $user;
}

test('index returns every package for a token with packages:read', function () {
    actAsToken(['packages:read']);
    Package::factory()->count(3)->create();

    $this->getJson(route('api.v1.packages.index'))
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

test('index rejects a token missing packages:read', function () {
    actAsToken(['packages:write']);

    $this->getJson(route('api.v1.packages.index'))->assertForbidden();
});

test('show returns a single package for a token with packages:read', function () {
    actAsToken(['packages:read']);
    $package = Package::factory()->create();

    $this->getJson(route('api.v1.packages.show', $package))
        ->assertOk()
        ->assertJsonPath('data.id', $package->id)
        ->assertJsonPath('data.slug', $package->slug);
});

test('store creates a package for a token with packages:write', function () {
    actAsToken(['packages:write']);

    $payload = [
        'name' => 'Feature Package',
        'slug' => 'feature-package',
        'wiki_url' => 'https://github.com/artisanpack-ui/feature-package/wiki',
        'changelog_url' => 'https://github.com/artisanpack-ui/feature-package/blob/main/CHANGELOG.md',
        'package_registry' => 'packagist',
    ];

    $this->postJson(route('api.v1.packages.store'), $payload)
        ->assertCreated()
        ->assertJsonPath('data.slug', 'feature-package');

    $this->assertDatabaseHas('packages', ['slug' => 'feature-package']);
});

test('store rejects a token missing packages:write', function () {
    actAsToken(['packages:read']);

    $this->postJson(route('api.v1.packages.store'), [])->assertForbidden();
});

test('update patches a package for a token with packages:write', function () {
    actAsToken(['packages:write']);
    $package = Package::factory()->create(['name' => 'Old Name']);

    $this->patchJson(route('api.v1.packages.update', $package), [
        'name' => 'New Name',
        'slug' => $package->slug,
        'wiki_url' => $package->wiki_url,
        'changelog_url' => $package->changelog_url,
    ])->assertOk()->assertJsonPath('data.name', 'New Name');

    $this->assertSame('New Name', $package->fresh()->name);
});

test('destroy removes a package for a token with packages:write', function () {
    actAsToken(['packages:write']);
    $package = Package::factory()->create();

    $this->deleteJson(route('api.v1.packages.destroy', $package))->assertNoContent();

    $this->assertDatabaseMissing('packages', ['id' => $package->id]);
});

test('unauthenticated requests are rejected', function () {
    Package::factory()->create();

    $this->getJson(route('api.v1.packages.index'))->assertUnauthorized();
});

test('store is blocked by the policy when the actor is not an admin', function () {
    $editor = User::factory()->editor()->create();
    Sanctum::actingAs($editor, ['packages:write']);

    $this->postJson(route('api.v1.packages.store'), [
        'name' => 'Blocked Package',
        'slug' => 'blocked-package',
        'wiki_url' => 'https://github.com/artisanpack-ui/blocked/wiki',
        'changelog_url' => 'https://github.com/artisanpack-ui/blocked/blob/main/CHANGELOG.md',
    ])->assertForbidden();

    $this->assertDatabaseMissing('packages', ['slug' => 'blocked-package']);
});

test('destroy is blocked by the policy when the actor is not an admin', function () {
    $editor = User::factory()->editor()->create();
    Sanctum::actingAs($editor, ['packages:write']);
    $package = Package::factory()->create();

    $this->deleteJson(route('api.v1.packages.destroy', $package))->assertForbidden();

    $this->assertDatabaseHas('packages', ['id' => $package->id]);
});

test('store rejects invalid payloads with 422', function () {
    actAsToken(['packages:write']);

    $this->postJson(route('api.v1.packages.store'), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'slug', 'changelog_url']);
});

test('index filters to an exact slug match when ?slug= is given', function () {
    actAsToken(['packages:read']);
    Package::factory()->create(['slug' => 'forms']);
    Package::factory()->create(['slug' => 'forms-extra']);
    Package::factory()->create(['slug' => 'react']);

    $this->getJson(route('api.v1.packages.index', ['slug' => 'forms']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'forms');
});

test('index returns an empty collection when no package has the given slug', function () {
    actAsToken(['packages:read']);
    Package::factory()->create(['slug' => 'forms']);

    $this->getJson(route('api.v1.packages.index', ['slug' => 'missing']))
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('index ignores an empty slug filter', function () {
    actAsToken(['packages:read']);
    Package::factory()->count(2)->create();

    $this->getJson(route('api.v1.packages.index').'?slug=')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('index rejects a non-string slug filter', function () {
    actAsToken(['packages:read']);

    $this->getJson(route('api.v1.packages.index').'?slug[]=forms')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['slug']);
});

test('show resolves a package by slug as well as by id', function () {
    actAsToken(['packages:read']);
    $package = Package::factory()->create(['slug' => 'accessibility']);

    $this->getJson('/api/v1/packages/accessibility')
        ->assertOk()
        ->assertJsonPath('data.id', $package->id);

    $this->getJson("/api/v1/packages/{$package->id}")
        ->assertOk()
        ->assertJsonPath('data.slug', 'accessibility');
});

test('show returns 404 for an unknown slug or id', function () {
    actAsToken(['packages:read']);

    $this->getJson('/api/v1/packages/no-such-package')->assertNotFound();
    $this->getJson('/api/v1/packages/999999')->assertNotFound();
});

test('update resolves a package by slug', function () {
    actAsToken(['packages:write']);
    $package = Package::factory()->create(['slug' => 'icons', 'version' => '1.0.0']);

    $this->patchJson('/api/v1/packages/icons', [
        'name' => $package->name,
        'slug' => 'icons',
        'wiki_url' => $package->wiki_url,
        'changelog_url' => $package->changelog_url,
        'version' => '1.1.0',
    ])->assertOk()->assertJsonPath('data.version', '1.1.0');
});

test('show returns a structured icon reference with svg for custom icon sets', function () {
    actAsToken(['packages:read']);
    $package = Package::factory()->create(['icon' => 'ap.puzzle']);

    $response = $this->getJson(route('api.v1.packages.show', $package))
        ->assertOk()
        ->assertJsonPath('data.icon.raw', 'ap.puzzle')
        ->assertJsonPath('data.icon.set', 'ap')
        ->assertJsonPath('data.icon.name', 'puzzle');

    expect($response->json('data.icon.svg'))
        ->toStartWith('<svg')
        ->toContain('fill="currentColor"')
        ->not->toContain('<!--');
});

test('show returns a structured icon reference without svg for shared icon sets', function (string $raw, string $set, string $name) {
    actAsToken(['packages:read']);
    $package = Package::factory()->create(['icon' => $raw]);

    $this->getJson(route('api.v1.packages.show', $package))
        ->assertOk()
        ->assertJsonPath('data.icon', [
            'raw' => $raw,
            'set' => $set,
            'name' => $name,
            'svg' => null,
        ]);
})->with([
    'font awesome solid' => ['fas.cube', 'fas', 'cube'],
    'font awesome brand' => ['fab.github', 'fab', 'github'],
    'default set bare name' => ['cube', 'fas', 'cube'],
    'raw font awesome class' => ['fa-regular fa-calendar', 'far', 'calendar'],
]);

test('show returns a null icon when the package has none', function () {
    actAsToken(['packages:read']);
    $package = Package::factory()->create(['icon' => null]);

    $this->getJson(route('api.v1.packages.show', $package))
        ->assertOk()
        ->assertJsonPath('data.icon', null);
});

test('show exposes documentation and changelog import status', function () {
    actAsToken(['packages:read']);
    $package = Package::factory()->create([
        'docs_imported_at' => '2026-10-01 12:00:00',
        'docs_import_status' => 'succeeded',
        'changelog_import_status' => 'failed',
        'changelog_import_error' => 'File not found',
    ]);

    $this->getJson(route('api.v1.packages.show', $package))
        ->assertOk()
        ->assertJsonPath('data.docs_imported_at', '2026-10-01T12:00:00.000000Z')
        ->assertJsonPath('data.changelog_imported_at', null)
        ->assertJsonPath('data.imports.docs.status', 'succeeded')
        ->assertJsonPath('data.imports.docs.error', null)
        ->assertJsonPath('data.imports.docs.imported_at', '2026-10-01T12:00:00.000000Z')
        ->assertJsonPath('data.imports.changelog.status', 'failed')
        ->assertJsonPath('data.imports.changelog.error', 'File not found');
});

test('store rejects a digit-only slug that would collide with id lookups', function () {
    actAsToken(['packages:write']);

    $this->postJson(route('api.v1.packages.store'), [
        'name' => 'Numeric Package',
        'slug' => '2048',
        'wiki_url' => 'https://github.com/artisanpack-ui/numeric/wiki',
        'changelog_url' => 'https://github.com/artisanpack-ui/numeric/blob/main/CHANGELOG.md',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['slug']);

    $this->assertDatabaseMissing('packages', ['slug' => '2048']);
});

test('store still accepts a slug that mixes digits and letters', function () {
    actAsToken(['packages:write']);

    $this->postJson(route('api.v1.packages.store'), [
        'name' => 'Mixed Package',
        'slug' => '2048-ui',
        'wiki_url' => 'https://github.com/artisanpack-ui/mixed/wiki',
        'changelog_url' => 'https://github.com/artisanpack-ui/mixed/blob/main/CHANGELOG.md',
    ])->assertCreated();
});
