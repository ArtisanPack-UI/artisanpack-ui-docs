<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Testing\AssertableInertia;
use Modules\Pages\Page;

function verifiedMenuOrderUser(): User
{
    return User::factory()->create(['email_verified_at' => now()]);
}

it('renders the menu order editor for verified users', function (): void {
    $parent = Page::factory()->create(['title' => 'Parent', 'menu_order' => 0]);
    $child = Page::factory()->create([
        'title' => 'Child',
        'parent' => $parent->id,
        'menu_order' => 0,
    ]);
    Page::factory()->create(['title' => 'Sibling', 'menu_order' => 1]);

    $this->actingAs(verifiedMenuOrderUser())
        ->get(route('dashboard.pages.menu-order'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Pages::Admin/MenuOrder')
            ->has('pages', 3)
            ->where('reorder_url', route('dashboard.pages.menu-order.reorder'))
            ->where('back_url', route('dashboard.pages'))
            // Root parent sits first (menu_order 0 + smallest id) and exposes
            // `parent === 0` so `PageOrderer` groups it as a root.
            ->where('pages.0.id', $parent->id)
            ->where('pages.0.parent', 0)
            // The child follows (menu_order 0 + next id) and keeps its parent id.
            ->where('pages.1.id', $child->id)
            ->where('pages.1.parent', $parent->id)
        );
});

it('redirects guests from the menu order editor to login', function (): void {
    $this->get(route('dashboard.pages.menu-order'))->assertRedirect(route('login'));
});

it('redirects unverified users from the menu order editor', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('dashboard.pages.menu-order'))
        ->assertRedirect(route('verification.notice'));
});

it('reorders root pages and redirects back to the editor', function (): void {
    $first = Page::factory()->create(['menu_order' => 0]);
    $second = Page::factory()->create(['menu_order' => 1]);
    $third = Page::factory()->create(['menu_order' => 2]);

    $this->actingAs(verifiedMenuOrderUser())
        ->post(route('dashboard.pages.menu-order.reorder'), [
            'items' => [
                ['id' => $third->id, 'menu_order' => 0],
                ['id' => $first->id, 'menu_order' => 1],
                ['id' => $second->id, 'menu_order' => 2],
            ],
        ])
        ->assertRedirect(route('dashboard.pages.menu-order'));

    expect($third->fresh()->menu_order)->toBe(0);
    expect($first->fresh()->menu_order)->toBe(1);
    expect($second->fresh()->menu_order)->toBe(2);
});

it('reorders children of a single parent without touching the parent/child relationship', function (): void {
    $parent = Page::factory()->create(['menu_order' => 0]);
    $childA = Page::factory()->create(['parent' => $parent->id, 'menu_order' => 0]);
    $childB = Page::factory()->create(['parent' => $parent->id, 'menu_order' => 1]);

    $this->actingAs(verifiedMenuOrderUser())
        ->post(route('dashboard.pages.menu-order.reorder'), [
            'items' => [
                ['id' => $parent->id, 'menu_order' => 0],
                ['id' => $childB->id, 'menu_order' => 0],
                ['id' => $childA->id, 'menu_order' => 1],
            ],
        ])
        ->assertRedirect();

    expect($childB->fresh()->menu_order)->toBe(0);
    expect($childA->fresh()->menu_order)->toBe(1);
    expect($childA->fresh()->parent)->toBe($parent->id);
    expect($childB->fresh()->parent)->toBe($parent->id);
});

it('rejects reordering when any submitted page id does not exist', function (): void {
    $real = Page::factory()->create(['menu_order' => 0]);
    $ghostId = $real->id + 999;

    $this->actingAs(verifiedMenuOrderUser())
        ->post(route('dashboard.pages.menu-order.reorder'), [
            'items' => [
                ['id' => $real->id, 'menu_order' => 0],
                ['id' => $ghostId, 'menu_order' => 1],
            ],
        ])
        ->assertStatus(422);

    expect($real->fresh()->menu_order)->toBe(0);
});

it('rejects reorder payloads larger than the 500-item cap', function (): void {
    $items = [];
    for ($i = 0; $i < 501; $i++) {
        $items[] = ['id' => $i + 1, 'menu_order' => $i];
    }

    $this->actingAs(verifiedMenuOrderUser())
        ->postJson(route('dashboard.pages.menu-order.reorder'), [
            'items' => $items,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('items');
});

it('requires authentication to reorder pages', function (): void {
    $this->post(route('dashboard.pages.menu-order.reorder'), [
        'items' => [],
    ])->assertRedirect(route('login'));
});

it('returns json for xhr reorder requests', function (): void {
    $page = Page::factory()->create(['menu_order' => 2]);

    $this->actingAs(verifiedMenuOrderUser())
        ->postJson(route('dashboard.pages.menu-order.reorder'), [
            'items' => [
                ['id' => $page->id, 'menu_order' => 0],
            ],
        ])
        ->assertOk()
        ->assertJson(['message' => 'Page order updated.']);

    expect($page->fresh()->menu_order)->toBe(0);
});
