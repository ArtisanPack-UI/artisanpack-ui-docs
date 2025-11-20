<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Packages\Package;

class SetDocsImportedAt extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'packages:set-docs-imported {--days=1 : Number of days ago to set the import date}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the docs_imported_at date for all packages';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $date = now()->subDays($days);

        $count = Package::query()->update(['docs_imported_at' => $date]);

        $this->info("Updated {$count} packages with docs_imported_at set to {$date->toDateTimeString()}");

        return Command::SUCCESS;
    }
}
