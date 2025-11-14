<?php

namespace Modules\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Modules\Core\Setting;

class SettingFactory extends Factory
{
	protected $model = Setting::class;

	public function definition(): array
	{
		return [
			'key'        => $this->faker->word(),
			'value'      => $this->faker->word(),
			'created_at' => Carbon::now(),
			'updated_at' => Carbon::now(),
		];
	}
}
