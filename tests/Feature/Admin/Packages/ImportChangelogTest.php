<?php

declare(strict_types=1);

use App\Jobs\ImportChangelog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\Packages\Package;

uses(RefreshDatabase::class);

function verifiedChangelogAdmin(): User
{
    return User::factory()->create(['email_verified_at' => now()]);
}

it('queues a changelog import when a valid url is provided', function (): void {
    Queue::fake();

    $package = Package::factory()->create([
        'changelog_url' => 'https://github.com/owner/repo/blob/main/CHANGELOG.md',
    ]);

    $response = $this->actingAs(verifiedChangelogAdmin())
        ->from(route('dashboard.packages.edit', $package))
        ->post(route('dashboard.packages.import-changelog', $package), [
            'changelog_url' => 'https://github.com/owner/repo/blob/main/CHANGELOG.md',
        ]);

    $response->assertRedirect(route('dashboard.packages.edit', $package))
        ->assertSessionHas('success');

    Queue::assertPushed(
        ImportChangelog::class,
        fn ($job) => $job->package->id === $package->id,
    );
});

it('persists the current form changelog url before dispatching', function (): void {
    Queue::fake();

    $package = Package::factory()->create([
        'changelog_url' => 'https://github.com/owner/old/blob/main/CHANGELOG.md',
    ]);

    $this->actingAs(verifiedChangelogAdmin())
        ->from(route('dashboard.packages.edit', $package))
        ->post(route('dashboard.packages.import-changelog', $package), [
            'changelog_url' => 'https://github.com/owner/new/blob/main/CHANGELOG.md',
        ])
        ->assertRedirect();

    expect($package->fresh()->changelog_url)
        ->toBe('https://github.com/owner/new/blob/main/CHANGELOG.md');

    Queue::assertPushed(ImportChangelog::class);
});

it('rejects the import when the changelog url is missing', function (): void {
    Queue::fake();

    $package = Package::factory()->create();

    $this->actingAs(verifiedChangelogAdmin())
        ->from(route('dashboard.packages.edit', $package))
        ->post(route('dashboard.packages.import-changelog', $package), [
            'changelog_url' => '',
        ])
        ->assertSessionHasErrors('changelog_url');

    Queue::assertNothingPushed();
});

it('rejects a non-GitHub changelog url', function (): void {
    Queue::fake();

    $package = Package::factory()->create();

    $this->actingAs(verifiedChangelogAdmin())
        ->from(route('dashboard.packages.edit', $package))
        ->post(route('dashboard.packages.import-changelog', $package), [
            'changelog_url' => 'https://gitlab.com/owner/repo/-/blob/main/CHANGELOG.md',
        ])
        ->assertSessionHasErrors('changelog_url');

    Queue::assertNothingPushed();
});

it('redirects guests to login', function (): void {
    Queue::fake();

    $package = Package::factory()->create();

    $this->post(route('dashboard.packages.import-changelog', $package), [
        'changelog_url' => 'https://github.com/owner/repo/blob/main/CHANGELOG.md',
    ])->assertRedirect(route('login'));

    Queue::assertNothingPushed();
});
