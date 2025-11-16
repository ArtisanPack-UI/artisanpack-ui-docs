<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Pages\Livewire\Admin\ManagePageOrder;
use Modules\Pages\Page;

uses(RefreshDatabase::class);

test('authenticated user can access manage page order page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard.pages.menu-order'))
        ->assertSuccessful()
        ->assertSeeLivewire(ManagePageOrder::class);
});

test('unauthenticated user cannot access manage page order page', function () {
    $this->get(route('dashboard.pages.menu-order'))
        ->assertRedirect(route('login'));
});

test('pages are loaded in hierarchical structure', function () {
    $user = User::factory()->create();

    $parent = Page::create([
        'title' => 'Parent Page',
        'slug' => 'parent',
        'content' => 'Parent content',
        'menu_order' => 0,
    ]);

    $child1 = Page::create([
        'title' => 'Child 1',
        'slug' => 'child-1',
        'parent' => $parent->id,
        'content' => 'Child 1 content',
        'menu_order' => 0,
    ]);

    $child2 = Page::create([
        'title' => 'Child 2',
        'slug' => 'child-2',
        'parent' => $parent->id,
        'content' => 'Child 2 content',
        'menu_order' => 1,
    ]);

    Livewire::actingAs($user)
        ->test(ManagePageOrder::class)
        ->assertSet('pages', function ($pages) use ($parent, $child1, $child2) {
            return count($pages) === 1 &&
                   $pages[0]['id'] === $parent->id &&
                   count($pages[0]['children']) === 2 &&
                   $pages[0]['children'][0]['id'] === $child1->id &&
                   $pages[0]['children'][1]['id'] === $child2->id;
        });
});

test('reorderPages updates menu order correctly', function () {
    $user = User::factory()->create();

    $page1 = Page::create([
        'title' => 'Page 1',
        'slug' => 'page-1',
        'content' => 'Content 1',
        'menu_order' => 0,
    ]);

    $page2 = Page::create([
        'title' => 'Page 2',
        'slug' => 'page-2',
        'content' => 'Content 2',
        'menu_order' => 1,
    ]);

    Livewire::actingAs($user)
        ->test(ManagePageOrder::class)
        ->call('reorderPages', 1, 0)
        ->assertHasNoErrors();

    expect(Page::find($page2->id)->menu_order)->toBe(0);
    expect(Page::find($page1->id)->menu_order)->toBe(1);
});

test('reorderPages only reorders top level items', function () {
    $user = User::factory()->create();

    $parent1 = Page::create([
        'title' => 'Parent 1',
        'slug' => 'parent-1',
        'content' => 'Parent 1 content',
        'menu_order' => 0,
    ]);

    $parent2 = Page::create([
        'title' => 'Parent 2',
        'slug' => 'parent-2',
        'content' => 'Parent 2 content',
        'menu_order' => 1,
    ]);

    $child1 = Page::create([
        'title' => 'Child 1',
        'slug' => 'child-1',
        'parent' => $parent1->id,
        'content' => 'Child 1 content',
        'menu_order' => 0,
    ]);

    Livewire::actingAs($user)
        ->test(ManagePageOrder::class)
        ->call('reorderPages', 1, 0)
        ->assertHasNoErrors();

    $updatedParent1 = Page::find($parent1->id);
    $updatedParent2 = Page::find($parent2->id);
    $updatedChild1 = Page::find($child1->id);

    expect($updatedParent2->menu_order)->toBe(0);
    expect($updatedParent1->menu_order)->toBe(1);
    expect($updatedChild1->parent)->toBe($parent1->id);
});

test('component displays no pages message when there are no pages', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ManagePageOrder::class)
        ->assertSee('No pages found.');
});
