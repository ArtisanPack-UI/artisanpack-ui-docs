<?php

namespace Modules\Packages\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Modules\Packages\Changelog;
use Modules\Packages\Package;

class ChangelogFactory extends Factory
{
	protected $model = Changelog::class;

	public function definition(): array
	{
		return [
			'content'    => $this->faker->word(),
			'created_at' => Carbon::now(),
			'updated_at' => Carbon::now(),

			'package_id' => Package::factory(),
		];
	}
}
