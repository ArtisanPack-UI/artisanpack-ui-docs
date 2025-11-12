<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ThemeSetupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'artisanpack:theme-setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set up the theme by asking for primary, secondary, and accent colors.
';

    /**
     * Execute the console command.
     */
    public function handle()
    {
		$primaryColor = $this->ask('What is your primary color? (e.g., #FFFFFF)');
		$secondaryColor = $this->ask('What is your secondary color? (e.g., #000000)');
		$accentColor = $this->ask('What is your accent color? (e.g., #FF0000)');

		$this->info('Generating theme with your selected colors...');

		Artisan::call('artisanpack:generate-theme', [
			'--primary' => $primaryColor,
			'--secondary' => $secondaryColor,
			'--accent' => $accentColor,
		]);

		$this->info('Theme generated successfully!');

		return 0;
	}
}
