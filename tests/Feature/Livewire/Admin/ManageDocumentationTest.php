<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Packages\Documentation;
use Modules\Packages\Livewire\Admin\ManageDocumentation;
use Modules\Packages\Package;

uses(RefreshDatabase::class);

test('authenticated user can access manage documentation page', function () {
    $user = User::factory()->create();
    $package = Package::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard.packages.documentation', $package))
        ->assertSuccessful()
        ->assertSeeLivewire(ManageDocumentation::class);
});

test('unauthenticated user cannot access manage documentation page', function () {
    $package = Package::factory()->create();

    $this->get(route('dashboard.packages.documentation', $package))
        ->assertRedirect(route('login'));
});

test('documentation is loaded in hierarchical structure', function () {
    $user = User::factory()->create();
    $package = Package::factory()->create();

    $parent = Documentation::create([
        'title' => 'Parent Documentation',
        'slug' => 'parent',
        'package_id' => $package->id,
        'content' => 'Parent content',
        'menu_order' => 0,
    ]);

    $child1 = Documentation::create([
        'title' => 'Child 1',
        'slug' => 'child-1',
        'parent' => $parent->id,
        'package_id' => $package->id,
        'content' => 'Child 1 content',
        'menu_order' => 0,
    ]);

    $child2 = Documentation::create([
        'title' => 'Child 2',
        'slug' => 'child-2',
        'parent' => $parent->id,
        'package_id' => $package->id,
        'content' => 'Child 2 content',
        'menu_order' => 1,
    ]);

    Livewire::actingAs($user)
        ->test(ManageDocumentation::class, ['package' => $package])
        ->assertSet('documentation', function ($docs) use ($parent, $child1, $child2) {
            return count($docs) === 1 &&
                   $docs[0]['id'] === $parent->id &&
                   count($docs[0]['children']) === 2 &&
                   $docs[0]['children'][0]['id'] === $child1->id &&
                   $docs[0]['children'][1]['id'] === $child2->id;
        });
});

test('reorderDocumentation updates menu order correctly', function () {
    $user = User::factory()->create();
    $package = Package::factory()->create();

    $doc1 = Documentation::create([
        'title' => 'Doc 1',
        'slug' => 'doc-1',
        'package_id' => $package->id,
        'content' => 'Content 1',
        'menu_order' => 0,
    ]);

    $doc2 = Documentation::create([
        'title' => 'Doc 2',
        'slug' => 'doc-2',
        'package_id' => $package->id,
        'content' => 'Content 2',
        'menu_order' => 1,
    ]);

    Livewire::actingAs($user)
        ->test(ManageDocumentation::class, ['package' => $package])
        ->call('reorderDocumentation', 1, 0)
        ->assertHasNoErrors();

    expect(Documentation::find($doc2->id)->menu_order)->toBe(0);
    expect(Documentation::find($doc1->id)->menu_order)->toBe(1);
});

test('reorderDocumentation only reorders top level items', function () {
    $user = User::factory()->create();
    $package = Package::factory()->create();

    $parent1 = Documentation::create([
        'title' => 'Parent 1',
        'slug' => 'parent-1',
        'package_id' => $package->id,
        'content' => 'Parent 1 content',
        'menu_order' => 0,
    ]);

    $parent2 = Documentation::create([
        'title' => 'Parent 2',
        'slug' => 'parent-2',
        'package_id' => $package->id,
        'content' => 'Parent 2 content',
        'menu_order' => 1,
    ]);

    $child1 = Documentation::create([
        'title' => 'Child 1',
        'slug' => 'child-1',
        'parent' => $parent1->id,
        'package_id' => $package->id,
        'content' => 'Child 1 content',
        'menu_order' => 0,
    ]);

    Livewire::actingAs($user)
        ->test(ManageDocumentation::class, ['package' => $package])
        ->call('reorderDocumentation', 1, 0)
        ->assertHasNoErrors();

    $updatedParent1 = Documentation::find($parent1->id);
    $updatedParent2 = Documentation::find($parent2->id);
    $updatedChild1 = Documentation::find($child1->id);

    expect($updatedParent2->menu_order)->toBe(0);
    expect($updatedParent1->menu_order)->toBe(1);
    expect($updatedChild1->parent)->toBe($parent1->id);
});

test('component displays no documentation message when package has no docs', function () {
    $user = User::factory()->create();
    $package = Package::factory()->create();

    Livewire::actingAs($user)
        ->test(ManageDocumentation::class, ['package' => $package])
        ->assertSee('No documentation pages found for this package.');
});
