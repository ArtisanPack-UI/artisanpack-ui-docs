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
