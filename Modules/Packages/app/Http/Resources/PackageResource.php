<?php

declare(strict_types=1);

namespace Modules\Packages\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Core\Services\IconResolverService;
use Modules\Packages\Package;

/** @mixin Package */
class PackageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'homepage' => $this->homepage,
            'wiki_url' => $this->wiki_url,
            'docs_url' => $this->docs_url,
            'changelog_url' => $this->changelog_url,
            'icon' => app(IconResolverService::class)->reference($this->icon),
            'version' => $this->version,
            'package_registry' => $this->package_registry,
            'docs_imported_at' => $this->docs_imported_at,
            'changelog_imported_at' => $this->changelog_imported_at,
            'imports' => [
                'docs' => [
                    'status' => $this->docs_import_status,
                    'error' => $this->docs_import_error,
                    'imported_at' => $this->docs_imported_at,
                ],
                'changelog' => [
                    'status' => $this->changelog_import_status,
                    'error' => $this->changelog_import_error,
                    'imported_at' => $this->changelog_imported_at,
                ],
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
