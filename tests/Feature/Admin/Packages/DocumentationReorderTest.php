<?php

declare(strict_types=1);

use App\Models\User;
use Modules\Packages\Database\Factories\DocumentationFactory;
use Modules\Packages\Documentation;
use Modules\Packages\Package;

function verifiedUserForReorder(): User
{
    return User::factory()->create(['email_verified_at' => now()]);
}

function makeDoc(Package $package, int $parent, int $menuOrder, string $title = 'Doc'): Documentation
{
    return DocumentationFactory::new()->create([
        'package_id' => $package->id,
        'title' => $title,
        'slug' => strtolower($title).'-'.uniqid(),
        'parent' => $parent,
        'menu_order' => $menuOrder,
    ]);
}

it('reorders root documentation entries', function (): void {
    $package = Package::factory()->create();
    $first = makeDoc($package, 0, 0, 'First');
    $second = makeDoc($package, 0, 1, 'Second');
    $third = makeDoc($package, 0, 2, 'Third');

    $this->actingAs(verifiedUserForReorder())
        ->post(route('dashboard.packages.documentation.reorder', $package), [
            'items' => [
                ['id' => $third->id, 'menu_order' => 0],
                ['id' => $first->id, 'menu_order' => 1],
                ['id' => $second->id, 'menu_order' => 2],
            ],
        ])
        ->assertRedirect(route('dashboard.packages.documentation', $package));

    expect($third->fresh()->menu_order)->toBe(0);
    expect($first->fresh()->menu_order)->toBe(1);
    expect($second->fresh()->menu_order)->toBe(2);
});

it('reorders children of a single parent without touching the parent/child relationship', function (): void {
    $package = Package::factory()->create();
    $parent = makeDoc($package, 0, 0, 'Parent');
    $childA = makeDoc($package, $parent->id, 0, 'ChildA');
    $childB = makeDoc($package, $parent->id, 1, 'ChildB');

    $this->actingAs(verifiedUserForReorder())
        ->post(route('dashboard.packages.documentation.reorder', $package), [
            'items' => [
                ['id' => $parent->id, 'menu_order' => 0],
                ['id' => $childB->id, 'menu_order' => 0],
                ['id' => $childA->id, 'menu_order' => 1],
            ],
        ])
        ->assertRedirect();

    expect($childB->fresh()->menu_order)->toBe(0);
    expect($childA->fresh()->menu_order)->toBe(1);
    // Parent relationship is untouched by the reorder endpoint.
    expect($childA->fresh()->parent)->toBe($parent->id);
    expect($childB->fresh()->parent)->toBe($parent->id);
});

it('rejects reordering documentation from another package', function (): void {
    $packageA = Package::factory()->create();
    $packageB = Package::factory()->create();
    $foreignDoc = makeDoc($packageB, 0, 0, 'Foreign');

    $this->actingAs(verifiedUserForReorder())
        ->post(route('dashboard.packages.documentation.reorder', $packageA), [
            'items' => [
                ['id' => $foreignDoc->id, 'menu_order' => 0],
            ],
        ])
        ->assertStatus(422);

    expect($foreignDoc->fresh()->menu_order)->toBe(0);
});

it('rejects reorder payloads larger than the 500-item cap', function (): void {
    $package = Package::factory()->create();

    $items = [];
    for ($i = 0; $i < 501; $i++) {
        $items[] = ['id' => $i + 1, 'menu_order' => $i];
    }

    $this->actingAs(verifiedUserForReorder())
        ->postJson(route('dashboard.packages.documentation.reorder', $package), [
            'items' => $items,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('items');
});

it('requires authentication to reorder', function (): void {
    $package = Package::factory()->create();

    $this->post(route('dashboard.packages.documentation.reorder', $package), [
        'items' => [],
    ])->assertRedirect(route('login'));
});
