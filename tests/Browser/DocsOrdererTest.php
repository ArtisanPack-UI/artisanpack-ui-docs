<?php

declare(strict_types=1);

use App\Models\User;
use Modules\Packages\Database\Factories\DocumentationFactory;
use Modules\Packages\Package;

/**
 * Browser coverage for the DocsOrderer flow (§9.4 item #28).
 *
 * HTML5 drag events are unreliable to simulate cross-browser, so this test
 * boots the real ManageDocumentation Inertia page in a real browser, verifies
 * the two levels render, and drives the reorder POST via the same route the
 * component's `router.post` would hit. That exercises the end-to-end code path
 * (page boot + Inertia round-trip + controller + DB) without depending on
 * flaky drag simulation.
 */
function reorderViaBrowser($page, string $url, array $items): void
{
    $payload = json_encode(['items' => $items], JSON_THROW_ON_ERROR);
    $encodedUrl = json_encode($url, JSON_THROW_ON_ERROR);
    $page->script(<<<JS
        (async () => {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            await fetch({$encodedUrl}, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                },
                body: JSON.stringify({$payload}),
                credentials: 'same-origin',
            });
        })();
    JS);
    $page->wait(1);
}

it('renders the docs manager and reorders both root pages and children', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $package = Package::factory()->create(['slug' => 'docs-orderer-'.uniqid()]);

    $parentA = DocumentationFactory::new()->create([
        'package_id' => $package->id,
        'title' => 'Parent A',
        'slug' => 'parent-a-'.uniqid(),
        'parent' => 0,
        'menu_order' => 0,
    ]);
    $parentB = DocumentationFactory::new()->create([
        'package_id' => $package->id,
        'title' => 'Parent B',
        'slug' => 'parent-b-'.uniqid(),
        'parent' => 0,
        'menu_order' => 1,
    ]);
    $childOne = DocumentationFactory::new()->create([
        'package_id' => $package->id,
        'title' => 'Child One',
        'slug' => 'child-one-'.uniqid(),
        'parent' => $parentA->id,
        'menu_order' => 0,
    ]);
    $childTwo = DocumentationFactory::new()->create([
        'package_id' => $package->id,
        'title' => 'Child Two',
        'slug' => 'child-two-'.uniqid(),
        'parent' => $parentA->id,
        'menu_order' => 1,
    ]);

    $this->actingAs($user);

    $page = visit("/dashboard/packages/{$package->id}/documentation");

    $page
        ->assertNoJavascriptErrors()
        ->assertSee('Documentation Order')
        ->assertSee('Parent A')
        ->assertSee('Parent B')
        ->assertSee('Child One')
        ->assertSee('Child Two');

    $reorderUrl = route('dashboard.packages.documentation.reorder', $package);

    // Root reorder — Parent B first, Parent A second.
    reorderViaBrowser($page, $reorderUrl, [
        ['id' => $parentB->id, 'menu_order' => 0],
        ['id' => $parentA->id, 'menu_order' => 1],
    ]);
    expect($parentB->fresh()->menu_order)->toBe(0);
    expect($parentA->fresh()->menu_order)->toBe(1);

    // Child reorder — swap Child One and Child Two under Parent A.
    reorderViaBrowser($page, $reorderUrl, [
        ['id' => $childTwo->id, 'menu_order' => 0],
        ['id' => $childOne->id, 'menu_order' => 1],
    ]);
    expect($childTwo->fresh()->menu_order)->toBe(0);
    expect($childOne->fresh()->menu_order)->toBe(1);
    // Parent/child relationships stay untouched.
    expect($childOne->fresh()->parent)->toBe($parentA->id);
    expect($childTwo->fresh()->parent)->toBe($parentA->id);
});
