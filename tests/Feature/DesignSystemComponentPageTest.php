<?php

declare(strict_types=1);

use ArtisanPackUI\DesignSystem\Manifest\Examples;
use ArtisanPackUI\DesignSystem\Manifest\ManifestRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;
use Modules\Packages\Documentation;
use Modules\Packages\Package;

/**
 * Point the page at fixture manifests and an examples directory.
 *
 * - `Widget` ships in Blade and React but not Vue (D-003). Its `basic`
 *   Blade example is plain markup, so it renders without any
 *   `x-artisanpack-*` components registered; its `unrenderable` example
 *   uses an unregistered component and has no React file.
 * - `Gadget` ships in React and Vue but not Blade, although a Blade file
 *   sits next to its example.
 * - `Doohickey` has no examples directory at all.
 */
function useWidgetFixture(): string
{
    $directory = sys_get_temp_dir().'/design-system-examples-'.uniqid();
    test()->fixtureDirectory = $directory;
    File::ensureDirectoryExists("{$directory}/Widget");
    File::ensureDirectoryExists("{$directory}/Gadget");

    File::put("{$directory}/Widget/examples.json", json_encode([
        ['id' => 'basic', 'title' => 'Basic', 'description' => 'A plain widget.'],
        ['id' => 'unrenderable', 'title' => 'Unrenderable', 'description' => 'Uses a component the docs app lacks.'],
    ]));
    File::put("{$directory}/Widget/basic.blade.php", '<span class="widget">{{ __( \'Hello widget\' ) }}</span>');
    File::put("{$directory}/Widget/basic.tsx", "export default function Basic() {\n  return <span>Hello widget</span>;\n}\n");
    File::put("{$directory}/Widget/basic.vue", "<template><span>Hello widget</span></template>\n");
    File::put("{$directory}/Widget/unrenderable.blade.php", "<x-artisanpack-widget>Hello</x-artisanpack-widget>\n");

    File::put("{$directory}/Gadget/examples.json", json_encode([
        ['id' => 'basic', 'title' => 'Basic', 'description' => 'A plain gadget.'],
    ]));
    File::put("{$directory}/Gadget/basic.blade.php", '<span>Hello gadget</span>');
    File::put("{$directory}/Gadget/basic.tsx", "export default function Basic() {\n  return <span>Hello gadget</span>;\n}\n");
    File::put("{$directory}/Gadget/basic.vue", "<template><span>Hello gadget</span></template>\n");

    app()->instance(ManifestRepository::class, ManifestRepository::fromArray([
        'Widget' => [
            'name' => 'Widget',
            'description' => 'A fixture component.',
            'status' => 'planned',
            'rootClass' => 'apui-widget',
            'stacks' => [
                'livewire' => ['name' => 'widget'],
                'react' => ['name' => 'Widget'],
                'vue' => false,
            ],
            'props' => [
                ['name' => 'variant', 'type' => 'enum', 'values' => ['solid', 'ghost'], 'default' => 'solid', 'description' => 'The look.'],
                ['name' => 'isOpen', 'type' => 'boolean', 'default' => false, 'description' => 'Opens it.'],
            ],
            'parts' => [
                ['name' => 'root', 'element' => 'span', 'description' => 'The root.'],
                ['name' => 'icon', 'element' => 'span', 'description' => 'The icon.'],
                ['name' => 'label', 'element' => 'span', 'description' => 'The label.'],
            ],
            'events' => [
                ['name' => 'row-click', 'description' => 'A row was clicked.'],
            ],
            'a11y' => [],
        ],
        'Gadget' => [
            'name' => 'Gadget',
            'description' => 'A fixture component with no Blade stack.',
            'status' => 'planned',
            'rootClass' => 'apui-gadget',
            'stacks' => [
                'livewire' => false,
                'react' => ['name' => 'Gadget'],
                'vue' => ['name' => 'Gadget'],
            ],
            'props' => [],
            'parts' => [],
            'events' => [],
            'a11y' => [],
        ],
        'Doohickey' => [
            'name' => 'Doohickey',
            'description' => 'A fixture component without examples.',
            'status' => 'planned',
            'rootClass' => 'apui-doohickey',
            'stacks' => [
                'livewire' => ['name' => 'doohickey'],
                'react' => false,
                'vue' => false,
            ],
            'props' => [],
            'parts' => [],
            'events' => [],
            'a11y' => [],
        ],
    ]));
    app()->instance(Examples::class, new Examples($directory));

    return $directory;
}

afterEach(function () {
    if (isset($this->fixtureDirectory)) {
        File::deleteDirectory($this->fixtureDirectory);
    }
});

it('renders a page from the real design-system package', function () {
    $this->get('/documentation/design-system/components/button')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Packages::DesignSystem/Component')
            ->where('component.name', 'Button')
            ->where('component.slug', 'button')
            ->where('component.stacks', fn ($stacks) => count($stacks) > 0)
            ->has('component.tables')
            ->has('examples')
            ->has('navigation.pages')
            ->has('navigation.packages')
            ->where('seo.title', fn ($title) => str_contains((string) $title, 'Button'))
        );
});

it('is reachable by its route name', function () {
    expect(route('documentation.design-system.component', ['component' => 'button'], false))
        ->toBe('/documentation/design-system/components/button');
});

it('spells the tables the way each stack writes them', function () {
    useWidgetFixture();

    $this->get('/documentation/design-system/components/widget')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('component.tables.livewire.props.1.name', 'is-open')
            ->where('component.tables.react.props.1.name', 'isOpen')
            ->where('component.tables.react.props.0.typeLabel', "'solid' | 'ghost'")
            ->where('component.tables.react.parts', fn ($parts) => collect($parts)->pluck('name')->all() === ['root', 'icon', 'label'])
            ->where('component.tables.livewire.events.0.name', 'row-click')
            ->where('component.tables.react.events.0.name', 'onRowClick')
        );
});

it('includes every example with its source for each shipping stack', function () {
    $directory = useWidgetFixture();

    $this->get('/documentation/design-system/components/widget')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('examples', 2)
            ->where('examples.0.id', 'basic')
            ->where('examples.0.title', 'Basic')
            ->where('examples.0.description', 'A plain widget.')
            ->where('examples.0.sources.livewire.file', 'Widget/basic.blade.php')
            ->where('examples.0.sources.livewire.code', file_get_contents("{$directory}/Widget/basic.blade.php"))
            ->where('examples.0.sources.react.file', 'Widget/basic.tsx')
            ->where('examples.0.sources.react.code', file_get_contents("{$directory}/Widget/basic.tsx"))
            ->missing('examples.0.sources.react.html')
        );
});

it('renders Blade examples server-side when they can render', function () {
    useWidgetFixture();

    $this->get('/documentation/design-system/components/widget')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('examples.0.sources.livewire.html', '<span class="widget">Hello widget</span>')
        );
});

it('falls back to the source when a Blade example cannot render here', function () {
    // The docs app doesn't register `x-artisanpack-*` components (until the
    // 3.0 components exist), so such an example has no server preview.
    useWidgetFixture();

    $this->get('/documentation/design-system/components/widget')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('examples.1.sources.livewire.html', null)
            ->where('examples.1.sources.livewire.code', fn ($code) => str_contains($code, '<x-artisanpack-widget>'))
        );
});

it('renders each Blade example once and caches the result, failures included', function () {
    useWidgetFixture();
    Log::spy();

    $this->get('/documentation/design-system/components/widget')->assertOk();
    $this->get('/documentation/design-system/components/widget')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('examples.0.sources.livewire.html', '<span class="widget">Hello widget</span>')
            ->where('examples.1.sources.livewire.html', null)
        );

    Log::shouldHaveReceived('debug')
        ->withArgs(fn (string $message) => str_contains($message, 'could not be rendered'))
        ->once();
});

it('only builds stacks the component ships in', function () {
    useWidgetFixture();

    $this->get('/documentation/design-system/components/widget')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('component.stacks', [
                ['key' => 'livewire', 'name' => 'x-artisanpack-widget'],
                ['key' => 'react', 'name' => 'Widget'],
            ])
            ->has('component.tables', 2)
            ->missing('component.tables.vue')
            ->has('examples.0.sources', 2)
            ->missing('examples.0.sources.vue')
        );
});

it('leaves out a stack whose example file is missing', function () {
    useWidgetFixture();

    $this->get('/documentation/design-system/components/widget')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('examples.1.id', 'unrenderable')
            ->has('examples.1.sources', 1)
            ->has('examples.1.sources.livewire')
            ->missing('examples.1.sources.react')
        );
});

it('has no Blade tab or Blade sources for a component without a livewire stack', function () {
    useWidgetFixture();

    $this->get('/documentation/design-system/components/gadget')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('component.stacks', [
                ['key' => 'react', 'name' => 'Gadget'],
                ['key' => 'vue', 'name' => 'Gadget'],
            ])
            ->missing('component.tables.livewire')
            ->has('examples', 1)
            ->has('examples.0.sources', 2)
            ->missing('examples.0.sources.livewire')
        );
});

it('renders a component that has no examples', function () {
    useWidgetFixture();

    $this->get('/documentation/design-system/components/doohickey')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Packages::DesignSystem/Component')
            ->where('component.name', 'Doohickey')
            ->where('examples', [])
        );
});

it('returns 404 for an unknown component', function () {
    $this->get('/documentation/design-system/components/not-a-component')->assertNotFound();
});

it('hands a components path with no manifest to the package docs viewer', function () {
    $package = Package::factory()->create(['slug' => 'design-system', 'homepage' => null]);
    Documentation::create([
        'title' => 'Components overview',
        'slug' => 'components/overview',
        'content' => '# Components overview',
        'package_id' => $package->id,
        'menu_order' => 0,
    ]);

    $this->get('/documentation/design-system/components/overview')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Packages::Documentation/Show')
            ->where('doc.slug', 'components/overview')
        );
});

it('leaves other design-system doc paths to the package docs viewer', function () {
    $package = Package::factory()->create(['slug' => 'design-system', 'homepage' => null]);
    Documentation::create([
        'title' => 'Tokens',
        'slug' => 'tokens',
        'content' => '# Tokens',
        'package_id' => $package->id,
        'menu_order' => 0,
    ]);

    $this->get('/documentation/design-system/tokens')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('Packages::Documentation/Show'));
});

it('only matches kebab-case component slugs', function (string $path) {
    $route = Route::getRoutes()->match(Request::create($path));

    expect($route->getName())->toBe('documentation.show');
})->with([
    'uppercase' => '/documentation/design-system/components/Button',
    'nested' => '/documentation/design-system/components/button/extra',
    'trailing hyphen' => '/documentation/design-system/components/button-',
]);

it('matches a kebab-case component slug', function () {
    $route = Route::getRoutes()->match(Request::create('/documentation/design-system/components/date-picker'));

    expect($route->getName())->toBe('documentation.design-system.component');
});

it('wins over a package doc at the same path', function () {
    $package = Package::factory()->create(['slug' => 'design-system', 'homepage' => null]);
    Documentation::create([
        'title' => 'Button',
        'slug' => 'components/button',
        'content' => '# Button',
        'package_id' => $package->id,
        'menu_order' => 0,
    ]);

    $this->get('/documentation/design-system/components/button')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Packages::DesignSystem/Component')
        );
});
