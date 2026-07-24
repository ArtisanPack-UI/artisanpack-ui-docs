<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seed the initial published privacy policy so the /policy route has
 * content to render out of the box. Editors can amend the body or
 * publish new versions from the dashboard afterwards.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('privacy_policies')->exists()) {
            return;
        }

        DB::table('privacy_policies')->insert([
            'version' => '1.0.0',
            'regulation' => null,
            'locale' => 'en',
            'content' => $this->defaultContent(),
            'sections' => null,
            'active' => true,
            'requires_reconsent' => false,
            'published_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Only remove the seed row if it still matches the exact content
        // this migration inserted — editors may have amended v1.0.0 in
        // place, and we don't want a rollback to nuke their work.
        DB::table('privacy_policies')
            ->where('version', '1.0.0')
            ->whereNull('regulation')
            ->where('locale', 'en')
            ->where('content', $this->defaultContent())
            ->delete();
    }

    protected function defaultContent(): string
    {
        return <<<'MARKDOWN'
## Overview

We respect your privacy. This policy explains what data ArtisanPack UI
collects when you browse the documentation site and how we use it.

## Data we collect

- Standard web server logs (IP address, user agent, requested URL, timestamp)
  retained for 30 days for security and abuse investigation.
- Cookie consent preferences you set through the banner on this site.
- Aggregated, anonymized usage analytics for pages that opt-in via the
  cookie consent banner.

## How we use your data

- Operate and secure the documentation site.
- Understand which docs pages and code examples are most useful.
- Debug issues you report through GitHub or email.

We do not sell your personal data and we do not share it with third
parties for advertising.

## Your rights

You can request access, export, correction, or deletion of any personal
data we hold about you at any time — no account required. Submit a
request from your account settings, or by emailing us directly. We
respond within 30 days.

## Contact

Reach us at [me@jacobmartella.com](mailto:me@jacobmartella.com) for any
privacy question or request.
MARKDOWN;
    }
};
