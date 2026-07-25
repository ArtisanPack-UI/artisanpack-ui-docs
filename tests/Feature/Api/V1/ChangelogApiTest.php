<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Modules\Packages\Changelog;
use Modules\Packages\Package;

function actAsChangelogsToken(array $abilities): User
{
    $user = User::factory()->create();
    Sanctum::actingAs($user, $abilities);

    return $user;
}

test('index returns the package changelogs for a token with changelogs:read', function () {
    actAsChangelogsToken(['changelogs:read']);
    $package = Package::factory()->create();
    Changelog::factory()->for($package)->count(2)->create();
    Changelog::factory()->create(); // Different package — should not leak.

    $this->getJson(route('api.v1.packages.changelogs.index', $package))
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('index rejects a token missing changelogs:read', function () {
    actAsChangelogsToken(['changelogs:write']);
    $package = Package::factory()->create();

    $this->getJson(route('api.v1.packages.changelogs.index', $package))->assertForbidden();
});

test('store creates a changelog scoped to the package', function () {
    actAsChangelogsToken(['changelogs:write']);
    $package = Package::factory()->create();

    $this->postJson(route('api.v1.packages.changelogs.store', $package), [
        'title' => '1.0.0',
        'content' => 'Initial release.',
    ])->assertCreated()->assertJsonPath('data.package_id', $package->id);

    $this->assertDatabaseHas('changelogs', ['package_id' => $package->id, 'title' => '1.0.0']);
});

test('store rejects a token missing changelogs:write', function () {
    actAsChangelogsToken(['changelogs:read']);
    $package = Package::factory()->create();

    $this->postJson(route('api.v1.packages.changelogs.store', $package), [])
        ->assertForbidden();
});

test('update patches a changelog for a token with changelogs:write', function () {
    actAsChangelogsToken(['changelogs:write']);
    $changelog = Changelog::factory()->create(['content' => 'old']);

    $this->patchJson(route('api.v1.changelogs.update', $changelog), [
        'content' => 'new content',
        'package_id' => $changelog->package_id,
    ])->assertOk()->assertJsonPath('data.content', 'new content');
});

test('update ignores package_id in body and keeps the changelog pinned to its owner', function () {
    actAsChangelogsToken(['changelogs:write']);
    $original = Package::factory()->create();
    $other = Package::factory()->create();
    $changelog = Changelog::factory()->for($original)->create();

    $this->patchJson(route('api.v1.changelogs.update', $changelog), [
        'content' => 'updated',
        'package_id' => $other->id,
    ])->assertOk();

    expect($changelog->fresh()->package_id)->toBe($original->id);
});

test('destroy removes a changelog for a token with changelogs:write', function () {
    actAsChangelogsToken(['changelogs:write']);
    $changelog = Changelog::factory()->create();

    $this->deleteJson(route('api.v1.changelogs.destroy', $changelog))->assertNoContent();

    $this->assertDatabaseMissing('changelogs', ['id' => $changelog->id]);
});

test('store is blocked by the policy when the actor is not an admin', function () {
    $editor = User::factory()->editor()->create();
    Sanctum::actingAs($editor, ['changelogs:write']);
    $package = Package::factory()->create();

    $this->postJson(route('api.v1.packages.changelogs.store', $package), [
        'title' => '1.0.0',
        'content' => 'Blocked release.',
    ])->assertForbidden();

    $this->assertDatabaseMissing('changelogs', ['package_id' => $package->id, 'title' => '1.0.0']);
});

test('destroy is blocked by the policy when the actor is not an admin', function () {
    $editor = User::factory()->editor()->create();
    Sanctum::actingAs($editor, ['changelogs:write']);
    $changelog = Changelog::factory()->create();

    $this->deleteJson(route('api.v1.changelogs.destroy', $changelog))->assertForbidden();

    $this->assertDatabaseHas('changelogs', ['id' => $changelog->id]);
});

test('store rejects invalid payloads with 422', function () {
    actAsChangelogsToken(['changelogs:write']);
    $package = Package::factory()->create();

    $this->postJson(route('api.v1.packages.changelogs.store', $package), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['content']);
});

test('unauthenticated requests are rejected', function () {
    $package = Package::factory()->create();

    $this->getJson(route('api.v1.packages.changelogs.index', $package))->assertUnauthorized();
});
