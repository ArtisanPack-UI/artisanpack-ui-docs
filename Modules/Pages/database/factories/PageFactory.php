<?php

declare(strict_types=1);

namespace Modules\Pages\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Modules\Pages\Page;

class PageFactory extends Factory
{
    protected $model = Page::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->unique()->words(2, true),
            'slug' => $this->faker->unique()->slug(2),
            'content' => $this->faker->paragraph(),
            'meta_description' => null,
            'parent' => null,
            'menu_order' => 0,
            'icon' => null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
