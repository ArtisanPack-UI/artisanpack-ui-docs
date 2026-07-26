<?php

declare(strict_types=1);

namespace Modules\Packages\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
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
            'icon' => $this->icon,
            'version' => $this->version,
            'package_registry' => $this->package_registry,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
