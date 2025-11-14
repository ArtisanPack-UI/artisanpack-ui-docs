<?php

namespace Modules\Packages\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Modules\Packages\Documentation;
use Modules\Packages\Package;

class DocumentationFactory extends Factory
{
	protected $model = Documentation::class;

	public function definition(): array
	{
		return [
			'title'      => $this->faker->word(),
			'slug'       => $this->faker->slug(),
			'parent'     => $this->faker->randomNumber(),
			'content'    => $this->faker->word(),
			'created_at' => Carbon::now(),
			'updated_at' => Carbon::now(),

			'package_id' => Package::factory(),
		];
	}
}
