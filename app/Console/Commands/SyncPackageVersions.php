<?php

namespace App\Console\Commands;

use App\Services\NpmService;
use App\Services\PackagistService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Packages\Package;

class SyncPackageVersions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'packages:sync-versions {--dry-run : Report version changes without saving them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update each package version to its latest stable release on Packagist or npm';

    /**
     * Execute the console command.
     */
    public function handle(PackagistService $packagist, NpmService $npm): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $updated = 0;

        $packages = Package::query()->whereNotNull('package_registry')->orderBy('name')->get();

        foreach ($packages as $package) {
            $registryName = $package->getRegistryPackageName();

            if ($registryName === null) {
                continue;
            }

            $latest = match (true) {
                $package->isPackagist() => $packagist->getLatestStableVersion($registryName),
                $package->isNpm() => $npm->getLatestStableVersion($registryName),
                default => null,
            };

            if ($latest === null) {
                $this->warn("Could not determine the latest stable version of {$registryName}.");

                continue;
            }

            if ($latest === $package->version) {
                continue;
            }

            $this->line("{$package->name}: ".($package->version ?? 'none')." → {$latest}");

            if ($isDryRun) {
                continue;
            }

            Log::info('Synced package {package} version from {old} to {new}', [
                'package' => $package->name,
                'old' => $package->version,
                'new' => $latest,
            ]);

            $package->update(['version' => $latest]);
            $updated++;
        }

        $this->info($isDryRun ? 'Dry run complete; no versions were saved.' : "Updated {$updated} package version(s).");

        return Command::SUCCESS;
    }
}
