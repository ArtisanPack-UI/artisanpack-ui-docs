<?php

declare(strict_types=1);

namespace Modules\Packages\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\InertiaSeo;
use ArtisanPackUI\DesignSystem\Manifest\ComponentManifest;
use ArtisanPackUI\DesignSystem\Manifest\Examples;
use ArtisanPackUI\DesignSystem\Manifest\ManifestDocs;
use ArtisanPackUI\DesignSystem\Manifest\ManifestRepository;
use Composer\InstalledVersions;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Services\NavigationService;
use Throwable;

/**
 * Design-system component pages (design-system#43).
 *
 * Everything on the page comes from the `artisanpack-ui/design-system`
 * package: the props/parts/slots/events tables from the component's
 * manifest, and the examples from `examples/<Name>/` next to it. Each
 * stack the component ships in (D-003) gets its own tables and sources.
 * Blade examples are rendered here; React and Vue examples are mounted
 * client-side as Vite islands, so only their source is sent.
 *
 * A `components/*` path with no matching manifest belongs to the regular
 * docs viewer, so imported docs such as `components/overview` still work.
 */
class ComponentDocsController extends Controller
{
    /**
     * Stacks in tab order.
     *
     * @var list<string>
     */
    public const STACKS = ['livewire', 'react', 'vue'];

    public function __construct(
        protected ManifestRepository $manifests,
        protected Examples $examples,
        protected NavigationService $navigation,
        protected InertiaSeo $seo,
    ) {}

    public function show(string $component): Response
    {
        $manifest = $this->resolve($component);

        if ($manifest === null) {
            return app(DocumentationViewerController::class)->show('design-system', "components/{$component}");
        }

        $stacks = array_values(array_filter(
            self::STACKS,
            fn (string $stack): bool => $manifest->shipsIn($stack),
        ));

        $tables = [];
        foreach ($stacks as $stack) {
            $tables[$stack] = ManifestDocs::tables($manifest, $stack);
        }

        return Inertia::render('Packages::DesignSystem/Component', [
            'component' => [
                'name' => $manifest->name,
                'slug' => ComponentManifest::kebab($manifest->name),
                'description' => $manifest->description,
                'status' => $manifest->status,
                'stacks' => array_map(fn (string $stack): array => [
                    'key' => $stack,
                    'name' => $this->stackName($manifest, $stack),
                ], $stacks),
                'tables' => $tables,
            ],
            'examples' => $this->buildExamples($manifest, $stacks),
            'navigation' => [
                'pages' => $this->navigation->buildPages(),
                'packages' => $this->navigation->buildPackages(),
            ],
            'seo' => $this->seo->forData($manifest->name, $manifest->description),
        ]);
    }

    /**
     * Find the manifest whose kebab-case name matches the URL segment.
     */
    protected function resolve(string $slug): ?ComponentManifest
    {
        foreach ($this->manifests->all() as $manifest) {
            if (ComponentManifest::kebab($manifest->name) === $slug) {
                return $manifest;
            }
        }

        return null;
    }

    /**
     * How the component is written in a stack: `x-artisanpack-button` in
     * Blade, the export name in React and Vue.
     */
    protected function stackName(ComponentManifest $manifest, string $stack): string
    {
        if ($stack === 'livewire') {
            return (string) $manifest->bladeTag();
        }

        $entry = $manifest->stacks[$stack] ?? [];

        return is_array($entry) ? (string) ($entry['name'] ?? $manifest->name) : $manifest->name;
    }

    /**
     * The component's examples with each shipping stack's source.
     *
     * `file` is the source's path relative to the examples directory
     * (`Button/basic.tsx`); the page uses it to find the island module.
     *
     * @param  list<string>  $stacks
     * @return list<array{id: string, title: string, description: string, sources: array<string, array{file: string, code: string, html?: string|null}>}>
     */
    protected function buildExamples(ComponentManifest $manifest, array $stacks): array
    {
        return array_map(function (array $example) use ($manifest, $stacks): array {
            $sources = [];

            foreach ($stacks as $stack) {
                $path = $example['files'][$stack] ?? null;
                if ($path === null) {
                    continue;
                }

                $code = (string) file_get_contents($path);
                $source = [
                    'file' => $manifest->name.'/'.basename($path),
                    'code' => $code,
                ];

                if ($stack === 'livewire') {
                    $source['html'] = $this->renderBlade($code, $path);
                }

                $sources[$stack] = $source;
            }

            return [
                'id' => $example['id'],
                'title' => $example['title'],
                'description' => $example['description'],
                'sources' => $sources,
            ];
        }, $this->examples->for($manifest->name));
    }

    /**
     * Render a Blade example, or null when it can't be rendered here.
     *
     * The docs app doesn't register the `x-artisanpack-*` components, so
     * an example that uses one fails to compile. The page then shows the
     * source with a "preview unavailable" note instead of erroring.
     *
     * The result, failures included, is cached by the file's contents, so
     * each version of an example is compiled once rather than per view.
     * The result is wrapped in an array because the cache treats a bare
     * null as a miss.
     */
    protected function renderBlade(string $code, string $path): ?string
    {
        // The result depends on the example and on which x-artisanpack-*
        // components are installed, so a component that ships later replaces
        // a cached "preview unavailable" without clearing the cache.
        $components = InstalledVersions::isInstalled('artisanpack-ui/livewire-ui-components')
            ? (string) InstalledVersions::getReference('artisanpack-ui/livewire-ui-components')
            : 'none';
        $key = 'design-system:blade-example:'.md5_file($path).':'.md5($components);

        return Cache::rememberForever($key, function () use ($code, $path): array {
            try {
                // The compiled temp view is never reused once the result
                // is cached, so don't leave it behind.
                return ['html' => Blade::render($code, deleteCachedView: true)];
            } catch (Throwable $exception) {
                Log::debug('Design-system Blade example could not be rendered.', [
                    'example' => $path,
                    'error' => $exception->getMessage(),
                ]);

                return ['html' => null];
            }
        })['html'];
    }
}
