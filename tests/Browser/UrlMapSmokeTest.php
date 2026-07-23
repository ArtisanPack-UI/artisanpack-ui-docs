<?php

declare(strict_types=1);

use App\Models\User;
use Modules\Packages\Database\Factories\DocumentationFactory;
use Modules\Packages\Package;
use Modules\Pages\Database\Factories\PageFactory;

/**
 * Pest v4 browser smoke harness for every route in V2_REFACTOR_PLAN.md §4.1.
 *
 * As the 3.0 refactor incrementally ports Livewire pages to Inertia + React,
 * this single test hits every public and admin URL and asserts the browser
 * saw no JavaScript errors and no console logs. It's the safety net that
 * catches regressions on every port.
 *
 * `POST /logout` is intentionally excluded — smoke visits are GET-only.
 */
it('visits every route in §4.1 without JS errors or console logs', function () {
    $user = User::factory()->create();

    $package = Package::factory()->create([
        'slug' => 'test-package',
        'version' => '1.0.0',
    ]);

    DocumentationFactory::new()->create([
        'package_id' => $package->id,
        'slug' => 'getting-started',
        'parent' => 0,
        'content' => '# Getting Started',
    ]);

    $parentPage = PageFactory::new()->create([
        'slug' => 'guides',
        'parent' => 0,
    ]);

    PageFactory::new()->create([
        'slug' => 'quickstart',
        'parent' => $parentPage->id,
    ]);

    PageFactory::new()->create([
        'slug' => 'about',
        'parent' => 0,
    ]);

    $pageForEdit = PageFactory::new()->create([
        'slug' => 'edit-me',
        'parent' => 0,
    ]);

    $guestPages = visit([
        '/',
        '/sitemap.xml',
        '/documentation/test-package/getting-started',
        '/documentation/test-package/changelog',
        '/documentation/test-package/changelogs',
        '/changelogs/test-package',
        '/guides/quickstart',
        '/about',
        '/login',
    ]);

    $guestPages
        ->assertNoJavascriptErrors()
        ->assertNoConsoleLogs();

    $this->actingAs($user);

    $authedPages = visit([
        '/settings/profile',
        '/settings/password',
        '/settings/appearance',
        '/dashboard',
        '/dashboard/settings',
        '/dashboard/packages',
        '/dashboard/packages/add-package',
        "/dashboard/packages/{$package->id}",
        "/dashboard/packages/{$package->id}/documentation",
        '/dashboard/pages',
        '/dashboard/pages/add-page',
        '/dashboard/pages/menu-order',
        "/dashboard/pages/{$pageForEdit->id}",
    ]);

    // The full admin panel still boots the vendor TinyMCE Alpine loader (a
    // `console.log` from `public/vendor/artisanpack-ui/js/tinymce-editor.js`).
    // That loader disappears with §9.4's Inertia port; until then we assert
    // only the JS-error half of the smoke on authed routes and tighten to
    // `assertNoConsoleLogs()` once the port is done.
    $authedPages->assertNoJavascriptErrors();
});
