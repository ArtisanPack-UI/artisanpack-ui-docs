<?php

declare(strict_types=1);

namespace App\Analytics;

use ArtisanPackUI\AnalyticsGoogle\Reporting\Ga4DataClient;
use ArtisanPackUI\AnalyticsGoogle\Support\GoogleConnectionResolver;
use ArtisanPackUI\Google\Models\GoogleConnection;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Modules\Core\Setting;

class AnalyticsSourceResolver
{
    private ?AnalyticsSource $cachedSource = null;

    public function __construct(
        private readonly AuthFactory $auth,
        private readonly GoogleConnectionResolver $connections,
        private readonly Ga4DataClient $ga4Client,
    ) {}

    public function active(): AnalyticsSource
    {
        if ($this->cachedSource !== null) {
            return $this->cachedSource;
        }

        $raw = Setting::query()->where('key', AnalyticsSource::SETTING_KEY)->value('value');
        $source = is_string($raw) ? (AnalyticsSource::tryFrom($raw) ?? AnalyticsSource::default()) : AnalyticsSource::default();

        return $this->cachedSource = $source;
    }

    public function set(AnalyticsSource $source): void
    {
        Setting::updateOrCreate(
            ['key' => AnalyticsSource::SETTING_KEY],
            ['value' => $source->value],
        );

        $this->cachedSource = $source;
    }

    public function googleConnection(): ?GoogleConnection
    {
        return $this->connections->forUser($this->auth->guard()->user());
    }

    public function isGoogleAvailable(): bool
    {
        return $this->ga4Client->isAvailable() && $this->googleConnection() !== null;
    }
}
