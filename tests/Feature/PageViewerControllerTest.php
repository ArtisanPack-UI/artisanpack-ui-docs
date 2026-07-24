<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;
use Modules\Core\Setting;
use Modules\Pages\Page;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

it('renders a top-level page at /{slug}', function () {
    Page::create([
        'title' => 'About',
        'slug' => 'about',
        'content' => '<p>About us.</p>',
        'meta_description' => 'Who we are.',
    ]);

    $response = $this->get('/about');

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Pages::Show')
        ->where('page.title', 'About')
        ->where('page.slug', 'about')
        ->where('page.parentSlug', null)
        ->where('page.parentTitle', null)
        ->where('page.metaDescription', 'Who we are.')
        ->where('page.content', fn ($content) => str_contains($content, 'About us.'))
    );
});

it('renders a nested page at /{parentSlug}/{slug} and exposes parent metadata', function () {
    $parent = Page::create([
        'title' => 'Docs',
        'slug' => 'docs',
        'content' => '<p>Docs root.</p>',
    ]);
    Page::create([
        'title' => 'Getting Started',
        'slug' => 'getting-started',
        'content' => '<p>Start here.</p>',
        'parent' => $parent->id,
    ]);

    $response = $this->get('/docs/getting-started');

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Pages::Show')
        ->where('page.title', 'Getting Started')
        ->where('page.slug', 'getting-started')
        ->where('page.parentSlug', 'docs')
        ->where('page.parentTitle', 'Docs')
    );
});

it('scopes /{slug} to top-level pages so a nested-only slug 404s', function () {
    $parent = Page::create([
        'title' => 'Docs',
        'slug' => 'docs',
        'content' => '',
    ]);
    Page::create([
        'title' => 'Nested Only',
        'slug' => 'nested-only',
        'content' => '',
        'parent' => $parent->id,
    ]);

    $this->get('/nested-only')->assertNotFound();
});

it('returns 404 when the page does not exist', function () {
    $this->get('/does-not-exist')->assertNotFound();
});

it('returns 404 for a nested slug that does not belong to the requested parent', function () {
    $docs = Page::create(['title' => 'Docs', 'slug' => 'docs', 'content' => '']);
    $other = Page::create(['title' => 'Other', 'slug' => 'other', 'content' => '']);
    Page::create([
        'title' => 'Child',
        'slug' => 'child',
        'content' => '',
        'parent' => $other->id,
    ]);

    $this->get('/docs/child')->assertNotFound();
});

it('redirects to home when the requested page is the configured home page', function () {
    $home = Page::create([
        'title' => 'Home',
        'slug' => 'home',
        'content' => '<p>Home body.</p>',
    ]);
    Setting::create(['key' => 'homePage', 'value' => (string) $home->id]);

    $this->get('/home')->assertRedirect(route('home'));
});

it('runs page content through kses and builds a nested table of contents', function () {
    Page::create([
        'title' => 'Guide',
        'slug' => 'guide',
        'content' => '<h2>Section</h2><p>Body</p><h3>Detail</h3><p>More</p><h2>Wrap Up</h2><p>End</p>',
    ]);

    $response = $this->get('/guide');

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Pages::Show')
        ->where('page.content', fn ($content) => str_contains($content, 'id="section"'))
        ->has('page.tableOfContents', 2)
        ->where('page.tableOfContents.0.text', 'Section')
        ->where('page.tableOfContents.0.level', 2)
        ->has('page.tableOfContents.0.children', 1)
        ->where('page.tableOfContents.0.children.0.text', 'Detail')
        ->where('page.tableOfContents.1.text', 'Wrap Up')
    );
});

it('exposes the shared sidebar navigation prop', function () {
    Page::create(['title' => 'About', 'slug' => 'about', 'content' => '']);

    $response = $this->get('/about');

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('navigation.pages')
        ->has('navigation.packages')
    );
});

it('refuses to shadow the login route even if a page with that slug exists', function () {
    Page::create(['title' => 'Login', 'slug' => 'login', 'content' => '<p>page</p>']);

    $response = $this->get('/login');

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Auth/Login')
    );
});

it('excludes reserved first segments from the pages viewer routes', function (string $reserved) {
    Page::create([
        'title' => "Reserved {$reserved}",
        'slug' => $reserved,
        'content' => '<p>page</p>',
    ]);

    // If the pages viewer picks up any of these URLs, the constraint
    // regex has regressed — even a route that returns 200 from another
    // handler (e.g. /login → Auth/Login) must not resolve to page.show.
    // If nothing matches at all, that's the strongest form of exclusion.
    $matchedName = null;
    try {
        $matchedName = Route::getRoutes()->match(
            Request::create("/{$reserved}", 'GET')
        )->getName();
    } catch (NotFoundHttpException|\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
        $matchedName = null;
    }

    expect($matchedName)
        ->not->toBe('page.show', "/{$reserved} must not resolve to page.show")
        ->not->toBe('page.child', "/{$reserved} must not resolve to page.child");
})->with([
    'dashboard', 'documentation', 'changelogs',
    'login', 'logout', 'forgot-password', 'reset-password',
    'two-factor-challenge', 'email', 'verified', 'user',
    'settings', 'api', 'admins', 'packages', 'pages', 'users',
    'sitemap', 'up', 'cores', 'livewire', 'storage',
]);

it('does not leave behind the Livewire public page component or its view', function () {
    expect(file_exists(base_path('Modules/Pages/app/Livewire/Public/Page.php')))->toBeFalse();
    expect(file_exists(base_path('Modules/Pages/resources/views/livewire/public/page.blade.php')))->toBeFalse();

    $routes = (string) file_get_contents(base_path('Modules/Pages/routes/web.php'));
    expect($routes)
        ->not->toContain('Modules\\Pages\\Livewire\\Public\\Page')
        ->toContain('PageViewerController');
});
