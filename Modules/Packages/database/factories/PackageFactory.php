<?php

declare(strict_types=1);

namespace Modules\Packages\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Modules\Packages\Package;

class PackageFactory extends Factory
{
    protected $model = Package::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $slug = $this->faker->unique()->slug(2);

        return [
            'name' => $this->faker->words(2, true),
            'slug' => $slug,
            'homepage' => null,
            'wiki_url' => "https://github.com/artisanpack-ui/{$slug}/wiki",
            'docs_url' => null,
            'changelog_url' => "https://github.com/artisanpack-ui/{$slug}/blob/main/CHANGELOG.md",
            'icon' => null,
            'version' => '1.0.0',
            'package_registry' => 'packagist',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
