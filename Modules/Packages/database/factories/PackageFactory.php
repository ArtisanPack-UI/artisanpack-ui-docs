<?php

namespace Modules\Packages\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Modules\Packages\Package;

class PackageFactory extends Factory
{
	protected $model = Package::class;

	public function definition(): array
	{
		return [
			'name'          => $this->faker->name(),
			'slug'          => $this->faker->slug(),
			'homepage'      => $this->faker->randomNumber(),
			'wiki_url'      => $this->faker->url(),
			'changelog_url' => $this->faker->url(),
			'created_at'    => Carbon::now(),
			'updated_at'    => Carbon::now(),
		];
	}
}
