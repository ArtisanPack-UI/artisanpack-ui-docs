<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Modules\Packages\Documentation;
use Modules\Packages\Package;

function actAsDocsToken(array $abilities): User
{
    $user = User::factory()->create();
    Sanctum::actingAs($user, $abilities);

    return $user;
}

test('index returns a nested tree for a token with docs:read', function () {
    actAsDocsToken(['docs:read']);
    $package = Package::factory()->create();

    $root = Documentation::factory()->for($package)->create([
        'title' => 'Root', 'parent' => 0, 'menu_order' => 0,
    ]);
    Documentation::factory()->for($package)->create([
        'title' => 'Child A', 'parent' => $root->id, 'menu_order' => 1,
    ]);
    Documentation::factory()->for($package)->create([
        'title' => 'Child B', 'parent' => $root->id, 'menu_order' => 0,
    ]);

    $response = $this->getJson(route('api.v1.packages.documentation.index', $package))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Root')
        ->assertJsonCount(2, 'data.0.children');

    // Nested children respect menu_order (Child B before Child A).
    expect($response->json('data.0.children.0.title'))->toBe('Child B');
});

test('index rejects a token missing docs:read', function () {
    actAsDocsToken(['docs:write']);
    $package = Package::factory()->create();

    $this->getJson(route('api.v1.packages.documentation.index', $package))->assertForbidden();
});

test('store creates a doc scoped to the package', function () {
    actAsDocsToken(['docs:write']);
    $package = Package::factory()->create();

    $this->postJson(route('api.v1.packages.documentation.store', $package), [
        'title' => 'Intro',
        'slug' => 'intro',
        'content' => '# Welcome',
    ])->assertCreated()->assertJsonPath('data.package_id', $package->id);

    $this->assertDatabaseHas('documentation', ['slug' => 'intro', 'package_id' => $package->id]);
});

test('store rejects a token missing docs:write', function () {
    actAsDocsToken(['docs:read']);
    $package = Package::factory()->create();

    $this->postJson(route('api.v1.packages.documentation.store', $package), [])
        ->assertForbidden();
});

test('show returns a single doc for a token with docs:read', function () {
    actAsDocsToken(['docs:read']);
    $doc = Documentation::factory()->create();

    $this->getJson(route('api.v1.documentation.show', $doc))
        ->assertOk()
        ->assertJsonPath('data.id', $doc->id);
});

test('update patches a doc for a token with docs:write', function () {
    actAsDocsToken(['docs:write']);
    $doc = Documentation::factory()->create(['title' => 'Old']);

    $this->patchJson(route('api.v1.documentation.update', $doc), [
        'title' => 'New',
        'slug' => $doc->slug,
        'package_id' => $doc->package_id,
        'content' => $doc->content,
    ])->assertOk()->assertJsonPath('data.title', 'New');
});

test('destroy removes a doc for a token with docs:write', function () {
    actAsDocsToken(['docs:write']);
    $doc = Documentation::factory()->create();

    $this->deleteJson(route('api.v1.documentation.destroy', $doc))->assertNoContent();

    $this->assertDatabaseMissing('documentation', ['id' => $doc->id]);
});

test('reorder updates menu_order for owned docs', function () {
    actAsDocsToken(['docs:write']);
    $package = Package::factory()->create();
    $a = Documentation::factory()->for($package)->create(['menu_order' => 0]);
    $b = Documentation::factory()->for($package)->create(['menu_order' => 1]);

    $this->postJson(route('api.v1.packages.documentation.reorder', $package), [
        'items' => [
            ['id' => $a->id, 'menu_order' => 5],
            ['id' => $b->id, 'menu_order' => 2],
        ],
    ])->assertOk()->assertJson(['message' => 'Documentation order updated.']);

    expect($a->fresh()->menu_order)->toBe(5)
        ->and($b->fresh()->menu_order)->toBe(2);
});

test('update ignores package_id in body and keeps the doc pinned to its owner', function () {
    actAsDocsToken(['docs:write']);
    $original = Package::factory()->create();
    $other = Package::factory()->create();
    $doc = Documentation::factory()->for($original)->create();

    $this->patchJson(route('api.v1.documentation.update', $doc), [
        'title' => 'renamed',
        'slug' => $doc->slug,
        'package_id' => $other->id,
        'content' => $doc->content,
    ])->assertOk();

    expect($doc->fresh()->package_id)->toBe($original->id);
});

test('update permits omitting package_id since it is pinned to the doc', function () {
    actAsDocsToken(['docs:write']);
    $doc = Documentation::factory()->create();

    $this->patchJson(route('api.v1.documentation.update', $doc), [
        'title' => 'partial',
        'slug' => $doc->slug,
        'content' => $doc->content,
    ])->assertOk()->assertJsonPath('data.title', 'partial');
});

test('reorder rejects docs not owned by the package', function () {
    actAsDocsToken(['docs:write']);
    $packageA = Package::factory()->create();
    $packageB = Package::factory()->create();
    $foreign = Documentation::factory()->for($packageB)->create();

    $this->postJson(route('api.v1.packages.documentation.reorder', $packageA), [
        'items' => [['id' => $foreign->id, 'menu_order' => 1]],
    ])->assertStatus(422);
});
