<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Testing\AssertableInertia;
use Modules\Pages\Page;

function verifiedPagesUser(): User
{
    return User::factory()->create(['email_verified_at' => now()]);
}

it('lists pages for verified users', function (): void {
    $pages = Page::factory()->count(3)->create();

    $this->actingAs(verifiedPagesUser())
        ->get(route('dashboard.pages'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Pages::Admin/Index')
            ->has('pages', 3)
            ->where('pages.0.title', $pages->sortBy('title')->first()->title)
            ->where('create_url', route('dashboard.pages.add'))
            ->where('menu_order_url', route('dashboard.pages.menu-order'))
        );
});

it('redirects guests from the pages list to login', function (): void {
    $this->get(route('dashboard.pages'))->assertRedirect(route('login'));
});

it('redirects unverified users from the pages list', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('dashboard.pages'))
        ->assertRedirect(route('verification.notice'));
});

it('renders the create form', function (): void {
    Page::factory()->create(['title' => 'Existing']);

    $this->actingAs(verifiedPagesUser())
        ->get(route('dashboard.pages.add'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Pages::Admin/Create')
            ->has('parent_options', 1)
        );
});

it('creates a page with valid data and redirects to edit', function (): void {
    $response = $this->actingAs(verifiedPagesUser())
        ->post(route('dashboard.pages.store'), [
            'title' => 'About Us',
            'slug' => 'about-us',
            'content' => 'This is the about page.',
            'meta_description' => 'About us page.',
            'parent' => '',
            'menu_order' => 5,
            'icon' => 'fas.info',
        ]);

    $page = Page::where('slug', 'about-us')->firstOrFail();

    $response->assertRedirect(route('dashboard.pages.edit', $page));
    expect($page->title)->toBe('About Us');
    expect($page->meta_description)->toBe('About us page.');
    expect($page->parent)->toBeNull();
    expect($page->menu_order)->toBe(5);
    expect($page->icon)->toBe('fas.info');
});

it('requires title, slug, and content', function (): void {
    $this->actingAs(verifiedPagesUser())
        ->post(route('dashboard.pages.store'), [])
        ->assertSessionHasErrors(['title', 'slug', 'content']);
});

it('rejects duplicate slugs on create', function (): void {
    Page::factory()->create(['slug' => 'taken']);

    $this->actingAs(verifiedPagesUser())
        ->post(route('dashboard.pages.store'), [
            'title' => 'Copycat',
            'slug' => 'taken',
            'content' => 'Content.',
        ])
        ->assertSessionHasErrors(['slug']);
});

it('rejects meta descriptions longer than 160 characters', function (): void {
    $this->actingAs(verifiedPagesUser())
        ->post(route('dashboard.pages.store'), [
            'title' => 'Long Meta',
            'slug' => 'long-meta',
            'content' => 'Content.',
            'meta_description' => str_repeat('a', 161),
        ])
        ->assertSessionHasErrors(['meta_description']);
});

it('rejects a parent that does not exist', function (): void {
    $this->actingAs(verifiedPagesUser())
        ->post(route('dashboard.pages.store'), [
            'title' => 'Orphan',
            'slug' => 'orphan',
            'content' => 'Content.',
            'parent' => 9999,
        ])
        ->assertSessionHasErrors(['parent']);
});

it('renders the edit form with the page payload', function (): void {
    $parent = Page::factory()->create(['title' => 'Parent']);
    $page = Page::factory()->create([
        'title' => 'Edit Me',
        'parent' => $parent->id,
    ]);

    $this->actingAs(verifiedPagesUser())
        ->get(route('dashboard.pages.edit', $page))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $view) => $view
            ->component('Pages::Admin/Edit')
            ->where('page.id', $page->id)
            ->where('page.title', 'Edit Me')
            ->where('page.parent', $parent->id)
            ->where('update_url', route('dashboard.pages.update', $page))
            ->where('destroy_url', route('dashboard.pages.destroy', $page))
            ->has('parent_options', 1)
            ->where('parent_options.0.id', $parent->id)
        );
});

it('updates a page', function (): void {
    $page = Page::factory()->create([
        'title' => 'Original',
        'slug' => 'original',
    ]);

    $this->actingAs(verifiedPagesUser())
        ->patch(route('dashboard.pages.update', $page), [
            'title' => 'Renamed',
            'slug' => 'renamed',
            'content' => 'New content.',
            'meta_description' => '',
            'parent' => '',
            'menu_order' => 2,
            'icon' => '',
        ])
        ->assertRedirect(route('dashboard.pages.edit', $page));

    expect($page->fresh()->title)->toBe('Renamed');
    expect($page->fresh()->slug)->toBe('renamed');
    expect($page->fresh()->meta_description)->toBeNull();
    expect($page->fresh()->menu_order)->toBe(2);
});

it('allows updating a page without changing its slug', function (): void {
    $page = Page::factory()->create(['slug' => 'keeper']);

    $this->actingAs(verifiedPagesUser())
        ->patch(route('dashboard.pages.update', $page), [
            'title' => 'Keeper',
            'slug' => 'keeper',
            'content' => 'Content.',
        ])
        ->assertSessionHasNoErrors();
});

it('prevents a page from being its own parent', function (): void {
    $page = Page::factory()->create();

    $this->actingAs(verifiedPagesUser())
        ->patch(route('dashboard.pages.update', $page), [
            'title' => $page->title,
            'slug' => $page->slug,
            'content' => 'Content.',
            'parent' => $page->id,
        ])
        ->assertSessionHasErrors(['parent']);
});

it('deletes a page and redirects back to the list', function (): void {
    $page = Page::factory()->create();

    $this->actingAs(verifiedPagesUser())
        ->delete(route('dashboard.pages.destroy', $page))
        ->assertRedirect(route('dashboard.pages'));

    expect(Page::find($page->id))->toBeNull();
});

it('rejects unauthenticated writes', function (): void {
    $page = Page::factory()->create();

    $this->post(route('dashboard.pages.store'), [])->assertRedirect(route('login'));
    $this->patch(route('dashboard.pages.update', $page), [])->assertRedirect(route('login'));
    $this->delete(route('dashboard.pages.destroy', $page))->assertRedirect(route('login'));
});
