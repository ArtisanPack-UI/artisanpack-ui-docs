<?php

declare(strict_types=1);

use ArtisanPackUI\Analytics\Models\Site;
use Illuminate\Database\Migrations\Migration;

/**
 * Idempotently ensures a Site row exists for the app's own domain.
 *
 * The vendor `DomainResolver` reads the request host and looks up an
 * `analytics_sites` row with that domain; a miss returns null and the
 * beacon is stored with `site_id: null` and never rolls up under a
 * per-site view. Running only via `AnalyticsSiteSeeder` covered the
 * `db:seed` path, but `php artisan migrate` alone (typical CI/prod
 * deploy) skipped it, so every fresh environment silently accumulated
 * unattributed rows until someone noticed.
 *
 * Uses the vendor `Site` model rather than raw INSERT so the model's
 * `creating` boot hook can populate `uuid` (and any future columns
 * the package adds via later migrations) without this migration
 * having to track the vendor schema. Safe to re-run — the domain-
 * match guard short-circuits when a row already exists. Ordered after
 * every shipped `analytics_sites` migration by timestamp so those
 * tables + columns already exist when we hit up().
 */
return new class extends Migration
{
    public function up(): void
    {
        $host = $this->hostFromAppUrl();
        $normalized = preg_replace('/^www\./i', '', $host);

        $existing = Site::query()
            ->where(function ($query) use ($host, $normalized): void {
                $query->where('domain', $host)
                    ->orWhere('domain', $normalized)
                    ->orWhere('domain', 'www.'.$normalized);
            })
            ->exists();

        if ($existing) {
            return;
        }

        Site::query()->create([
            'name' => (string) config('app.name', 'ArtisanPack UI Docs'),
            'domain' => $host,
            'timezone' => (string) config('app.timezone', 'UTC'),
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        // Intentionally no-op: rolling this back would only delete the
        // Site row for the current domain, which would orphan every
        // pageview attributed to it. If you truly want it gone, drop
        // the row manually.
    }

    private function hostFromAppUrl(): string
    {
        $url = (string) config('app.url', 'http://localhost');
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? $host : 'localhost';
    }
};
