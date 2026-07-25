<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Testing\AssertableInertia;
use Modules\Packages\Database\Factories\ChangelogFactory;
use Modules\Packages\Database\Factories\DocumentationFactory;
use Modules\Packages\Package;
use Modules\Pages\Database\Factories\PageFactory;

/**
 * URL parity harness for V2_REFACTOR_PLAN.md §4.1.
 *
 * Every path in §4.1 must return the documented status and — for the
 * Inertia routes — render the documented page component. The component
 * itself owns its `<Head title>` in the React tree, so pinning the
 * component is the strongest parity guarantee reachable in a feature
 * test without paying for a browser render (tests/Browser/UrlMapSmokeTest
 * already covers the JS side).
 *
 * When you add, move, or rename a route in §4.1, update this test in the
 * same PR.
 */
beforeEach(function () {
    $this->package = Package::factory()->create([
        'slug' => 'parity-package',
        'version' => '1.0.0',
    ]);

    DocumentationFactory::new()->create([
        'package_id' => $this->package->id,
        'slug' => 'quickstart',
        'title' => 'Quickstart',
        'parent' => 0,
    ]);

    ChangelogFactory::new()->create([
        'package_id' => $this->package->id,
        'title' => '1.0.0',
        'content' => '# 1.0.0',
    ]);

    $this->parentPage = PageFactory::new()->create([
        'slug' => 'guides',
        'title' => 'Guides',
        'parent' => 0,
    ]);

    PageFactory::new()->create([
        'slug' => 'writing',
        'title' => 'Writing Docs',
        'parent' => $this->parentPage->id,
    ]);

    PageFactory::new()->create([
        'slug' => 'about',
        'title' => 'About',
        'parent' => 0,
    ]);
});

it('serves each §4.1 guest route with the documented Inertia component', function (string $path, string $component) {
    $this->get($path)
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component($component));
})->with([
    'home' => ['/',                                            'Core::Home'],
    'nested docs viewer' => ['/documentation/parity-package/quickstart',     'Packages::Documentation/Show'],
    'changelog viewer' => ['/changelogs/parity-package',                   'Packages::Changelog/Show'],
    'top-level page' => ['/about',                                       'Pages::Show'],
    'child page' => ['/guides/writing',                              'Pages::Show'],
    'login' => ['/login',                                       'Auth/Login'],
]);

it('serves each §4.1 authed route with the documented Inertia component', function (string $path, string $component) {
    $this->actingAs(User::factory()->create());

    $this->get($path)
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component($component));
})->with([
    'dashboard home' => ['/dashboard',                             'Dashboard/Index'],
    'site settings' => ['/dashboard/settings',                    'Core::Admin/Settings'],
    'packages list' => ['/dashboard/packages',                    'Packages::Admin/Index'],
    'packages add' => ['/dashboard/packages/add-package',        'Packages::Admin/Create'],
    'pages list' => ['/dashboard/pages',                       'Pages::Admin/Index'],
    'pages add' => ['/dashboard/pages/add-page',              'Pages::Admin/Create'],
    'page menu order' => ['/dashboard/pages/menu-order',            'Pages::Admin/MenuOrder'],
    'profile settings' => ['/dashboard/settings/profile',            'Settings/Profile'],
    'password settings' => ['/dashboard/settings/password',           'Settings/Password'],
    'appearance settings' => ['/dashboard/settings/appearance',         'Settings/Appearance'],
]);

it('serves §4.1 authed routes that need model-bound URLs with the documented component', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $packageId = $this->package->id;
    $pageId = $this->parentPage->id;

    $this->get("/dashboard/packages/{$packageId}")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('Packages::Admin/Edit'));

    $this->get("/dashboard/packages/{$packageId}/documentation")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('Packages::Admin/Documentation/Manage'));

    $this->get("/dashboard/pages/{$pageId}")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('Pages::Admin/Edit'));
});

it('301-redirects each §4.1 legacy documentation-changelog path to /changelogs/{package}', function (string $path, string $target) {
    $this->get($path)
        ->assertStatus(301)
        ->assertRedirect($target);
})->with([
    'singular slug' => ['/documentation/parity-package/changelog',  '/changelogs/parity-package'],
    'plural slug' => ['/documentation/parity-package/changelogs', '/changelogs/parity-package'],
]);

/*
 * §4.1 originally listed `/settings/{profile,password,appearance}` as
 * page routes. The 3.0 admin port moved them under `/dashboard/settings/*`
 * and left redirects at the legacy paths so bookmarks keep working. The
 * redirects are Laravel's `Route::redirect()` default (302); asserting
 * the current status locks the parity down without upgrading the semantic
 * — that's a separate call to make in the plan.
 */
it('redirects each legacy /settings/* path to its /dashboard/settings/* replacement', function (string $path, string $target) {
    $this->actingAs(User::factory()->create());

    $this->get($path)->assertRedirect($target);
})->with([
    'settings root' => ['/settings',            '/dashboard/settings/profile'],
    'settings profile' => ['/settings/profile',    '/dashboard/settings/profile'],
    'settings password' => ['/settings/password',   '/dashboard/settings/password'],
    'settings appearance' => ['/settings/appearance', '/dashboard/settings/appearance'],
]);

it('serves /sitemap.xml as XML', function () {
    $response = $this->get('/sitemap.xml');

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toStartWith('application/xml');
});
