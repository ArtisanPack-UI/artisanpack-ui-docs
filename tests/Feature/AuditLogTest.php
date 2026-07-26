<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogger;
use Inertia\Testing\AssertableInertia;
use Laravel\Sanctum\Sanctum;
use Modules\Packages\Changelog;
use Modules\Packages\Documentation;
use Modules\Packages\Package;

function actingAsAdmin(): User
{
    return User::factory()->create([
        'role' => Role::Admin,
        'email_verified_at' => now(),
    ]);
}

// -- API write → log round-trips ---------------------------------------------

test('api package create writes an audit-log entry with source=api', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['packages:write']);

    $this->postJson(route('api.v1.packages.store'), [
        'name' => 'Audit Package',
        'slug' => 'audit-package',
        'wiki_url' => 'https://github.com/artisanpack-ui/audit/wiki',
        'changelog_url' => 'https://github.com/artisanpack-ui/audit/blob/main/CHANGELOG.md',
        'package_registry' => 'packagist',
    ])->assertCreated();

    $log = AuditLog::query()->latest('id')->first();

    expect($log)->not->toBeNull()
        ->and($log->action)->toBe(AuditLogger::ACTION_CREATED)
        ->and($log->source)->toBe(AuditLogger::SOURCE_API)
        ->and($log->resource_type)->toBe(Package::class)
        ->and($log->user_id)->toBe($user->id)
        ->and($log->actor_name)->toContain($user->name)
        ->and($log->changes)->toBeArray()
        ->and($log->changes['slug'])->toEqual([null, 'audit-package']);
});

test('api package update writes an audit-log diff of only changed fields', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['packages:write']);
    $package = Package::factory()->create(['name' => 'Old Name']);

    $this->patchJson(route('api.v1.packages.update', $package), [
        'name' => 'New Name',
        'slug' => $package->slug,
        'wiki_url' => $package->wiki_url,
        'changelog_url' => $package->changelog_url,
    ])->assertOk();

    $log = AuditLog::query()->latest('id')->first();

    expect($log->action)->toBe(AuditLogger::ACTION_UPDATED)
        ->and($log->resource_id)->toBe($package->id)
        ->and($log->changes)->toHaveKey('name')
        ->and($log->changes['name'])->toEqual(['Old Name', 'New Name'])
        ->and($log->changes)->not->toHaveKey('slug');
});

test('api package delete writes an audit-log entry with the pre-delete snapshot', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['packages:write']);
    $package = Package::factory()->create();

    $this->deleteJson(route('api.v1.packages.destroy', $package))->assertNoContent();

    $log = AuditLog::query()->latest('id')->first();

    expect($log->action)->toBe(AuditLogger::ACTION_DELETED)
        ->and($log->resource_id)->toBe($package->id)
        ->and($log->changes)->toHaveKey('id')
        ->and($log->changes['id'][0])->toBe($package->id);
});

test('api documentation create + update + delete each log one entry', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['docs:write']);
    $package = Package::factory()->create();

    $this->postJson(route('api.v1.packages.documentation.store', $package), [
        'title' => 'Getting Started',
        'slug' => 'getting-started',
        'parent' => 0,
        'menu_order' => 0,
        'content' => 'Hello world',
    ])->assertCreated();

    $doc = Documentation::query()->latest('id')->first();

    $this->patchJson(route('api.v1.documentation.update', $doc), [
        'title' => 'Updated Title',
        'slug' => $doc->slug,
        'parent' => 0,
        'menu_order' => 0,
        'content' => $doc->content,
    ])->assertOk();

    $this->deleteJson(route('api.v1.documentation.destroy', $doc))->assertNoContent();

    $entries = AuditLog::query()
        ->where('resource_type', Documentation::class)
        ->orderBy('id')
        ->get();

    expect($entries)->toHaveCount(3)
        ->and($entries->pluck('action')->all())->toEqual([
            AuditLogger::ACTION_CREATED,
            AuditLogger::ACTION_UPDATED,
            AuditLogger::ACTION_DELETED,
        ]);
});

test('api documentation reorder writes a reordered entry with the order payload', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['docs:write']);
    $package = Package::factory()->create();
    $doc = Documentation::factory()->for($package)->create(['menu_order' => 0]);

    $this->postJson(route('api.v1.packages.documentation.reorder', $package), [
        'items' => [['id' => $doc->id, 'menu_order' => 5]],
    ])->assertOk();

    $log = AuditLog::query()
        ->where('resource_type', Package::class)
        ->where('action', AuditLogger::ACTION_REORDERED)
        ->latest('id')
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->source)->toBe(AuditLogger::SOURCE_API)
        ->and($log->changes['order'][0])->toEqual(['id' => $doc->id, 'menu_order' => 5]);
});

test('api changelog create writes an audit-log entry', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['changelogs:write']);
    $package = Package::factory()->create();

    $this->postJson(route('api.v1.packages.changelogs.store', $package), [
        'title' => 'v1.0.0',
        'content' => 'Initial release.',
    ])->assertCreated();

    $log = AuditLog::query()->latest('id')->first();

    expect($log->action)->toBe(AuditLogger::ACTION_CREATED)
        ->and($log->resource_type)->toBe(Changelog::class);
});

test('api actor name includes the token name', function () {
    $user = User::factory()->create();
    $newToken = $user->createToken('artisanpackui.dev prod', ['packages:write']);

    $this->withHeader('Authorization', 'Bearer '.$newToken->plainTextToken)
        ->postJson(route('api.v1.packages.store'), [
            'name' => 'Named',
            'slug' => 'named',
            'wiki_url' => 'https://github.com/artisanpack-ui/named/wiki',
            'changelog_url' => 'https://github.com/artisanpack-ui/named/blob/main/CHANGELOG.md',
            'package_registry' => 'packagist',
        ])->assertCreated();

    $log = AuditLog::query()->latest('id')->first();

    expect($log->actor_name)->toContain('artisanpackui.dev prod');
});

// -- Web write → log round-trips ---------------------------------------------

test('web package create writes an audit-log entry with source=web', function () {
    $admin = actingAsAdmin();

    $this->actingAs($admin)->post(route('dashboard.packages.store'), [
        'name' => 'Web Package',
        'slug' => 'web-package',
        'wiki_url' => 'https://github.com/artisanpack-ui/web/wiki',
        'changelog_url' => 'https://github.com/artisanpack-ui/web/blob/main/CHANGELOG.md',
        'package_registry' => 'packagist',
    ])->assertRedirect();

    $log = AuditLog::query()->latest('id')->first();

    expect($log->source)->toBe(AuditLogger::SOURCE_WEB)
        ->and($log->action)->toBe(AuditLogger::ACTION_CREATED)
        ->and($log->user_id)->toBe($admin->id);
});

test('web package delete writes an audit-log entry with source=web', function () {
    $admin = actingAsAdmin();
    $package = Package::factory()->create();

    $this->actingAs($admin)->delete(route('dashboard.packages.destroy', $package))
        ->assertRedirect();

    $log = AuditLog::query()->latest('id')->first();

    expect($log->source)->toBe(AuditLogger::SOURCE_WEB)
        ->and($log->action)->toBe(AuditLogger::ACTION_DELETED)
        ->and($log->resource_id)->toBe($package->id);
});

// -- Viewer ------------------------------------------------------------------

test('the audit-log viewer requires admin', function () {
    $editor = User::factory()->create([
        'role' => Role::Editor,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($editor)->get(route('dashboard.audit-log'))->assertForbidden();
});

test('the audit-log viewer renders recent entries newest-first', function () {
    $admin = actingAsAdmin();

    AuditLog::create([
        'user_id' => $admin->id,
        'actor_name' => 'older',
        'source' => 'web',
        'resource_type' => Package::class,
        'resource_id' => 1,
        'action' => 'created',
        'changes' => null,
        'ip_address' => '127.0.0.1',
        'created_at' => now()->subHour(),
    ]);

    AuditLog::create([
        'user_id' => $admin->id,
        'actor_name' => 'newer',
        'source' => 'api',
        'resource_type' => Documentation::class,
        'resource_id' => 2,
        'action' => 'updated',
        'changes' => ['title' => ['a', 'b']],
        'ip_address' => '10.0.0.1',
        'created_at' => now(),
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard.audit-log'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Dashboard/AuditLog')
        ->has('entries.data', 2)
        ->where('entries.data.0.actor_name', 'newer')
        ->where('entries.data.1.actor_name', 'older')
        ->where('entries.data.0.resource_label', 'documentation')
    );
});

test('the audit-log viewer filters by resource', function () {
    $admin = actingAsAdmin();

    AuditLog::create([
        'user_id' => $admin->id, 'actor_name' => 'a', 'source' => 'web',
        'resource_type' => Package::class, 'resource_id' => 1,
        'action' => 'created', 'created_at' => now(),
    ]);
    AuditLog::create([
        'user_id' => $admin->id, 'actor_name' => 'b', 'source' => 'web',
        'resource_type' => Documentation::class, 'resource_id' => 1,
        'action' => 'created', 'created_at' => now(),
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard.audit-log', ['resource' => 'package']));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Dashboard/AuditLog')
        ->has('entries.data', 1)
        ->where('entries.data.0.resource_type', Package::class)
    );
});

test('the audit-log viewer filters by source', function () {
    $admin = actingAsAdmin();

    AuditLog::create([
        'user_id' => $admin->id, 'actor_name' => 'w', 'source' => 'web',
        'resource_type' => Package::class, 'resource_id' => 1,
        'action' => 'created', 'created_at' => now(),
    ]);
    AuditLog::create([
        'user_id' => $admin->id, 'actor_name' => 'a', 'source' => 'api',
        'resource_type' => Package::class, 'resource_id' => 2,
        'action' => 'created', 'created_at' => now(),
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard.audit-log', ['source' => 'api']));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('entries.data', 1)
        ->where('entries.data.0.source', 'api')
    );
});

test('the audit-log viewer filters by actor and date range', function () {
    $admin = actingAsAdmin();

    AuditLog::create([
        'user_id' => $admin->id, 'actor_name' => 'Alice', 'source' => 'web',
        'resource_type' => Package::class, 'resource_id' => 1,
        'action' => 'created', 'created_at' => now()->subDays(10),
    ]);
    AuditLog::create([
        'user_id' => $admin->id, 'actor_name' => 'Bob', 'source' => 'web',
        'resource_type' => Package::class, 'resource_id' => 2,
        'action' => 'created', 'created_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('dashboard.audit-log', ['actor' => 'Ali']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('entries.data', 1)
            ->where('entries.data.0.actor_name', 'Alice')
        );

    $this->actingAs($admin)
        ->get(route('dashboard.audit-log', [
            'from' => now()->subDays(2)->toDateString(),
        ]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('entries.data', 1)
            ->where('entries.data.0.actor_name', 'Bob')
        );
});
