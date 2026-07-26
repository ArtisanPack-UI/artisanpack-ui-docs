<?php

declare(strict_types=1);

use App\Jobs\ImportWikiDocumentation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\Packages\Package;

uses(RefreshDatabase::class);

function verifiedAdmin(): User
{
    return User::factory()->create(['email_verified_at' => now()]);
}

it('queues an import when a docs url is provided', function (): void {
    Queue::fake();

    $package = Package::factory()->create([
        'docs_url' => 'https://github.com/owner/repo',
        'wiki_url' => null,
    ]);

    $response = $this->actingAs(verifiedAdmin())
        ->from(route('dashboard.packages.edit', $package))
        ->post(route('dashboard.packages.import-documentation', $package), [
            'docs_url' => 'https://github.com/owner/repo',
            'wiki_url' => '',
        ]);

    $response->assertRedirect(route('dashboard.packages.edit', $package))
        ->assertSessionHas('success');

    Queue::assertPushed(
        ImportWikiDocumentation::class,
        fn ($job) => $job->package->id === $package->id,
    );
});

it('persists the current form url before dispatching', function (): void {
    Queue::fake();

    $package = Package::factory()->create([
        'docs_url' => 'https://github.com/owner/old',
        'wiki_url' => null,
    ]);

    $this->actingAs(verifiedAdmin())
        ->from(route('dashboard.packages.edit', $package))
        ->post(route('dashboard.packages.import-documentation', $package), [
            'docs_url' => 'https://github.com/owner/new',
            'wiki_url' => '',
        ])
        ->assertRedirect();

    expect($package->fresh()->docs_url)->toBe('https://github.com/owner/new');

    Queue::assertPushed(ImportWikiDocumentation::class);
});

it('rejects the import when both urls are missing', function (): void {
    Queue::fake();

    $package = Package::factory()->create([
        'docs_url' => null,
        'wiki_url' => null,
    ]);

    $this->actingAs(verifiedAdmin())
        ->from(route('dashboard.packages.edit', $package))
        ->post(route('dashboard.packages.import-documentation', $package), [
            'docs_url' => '',
            'wiki_url' => '',
        ])
        ->assertSessionHasErrors(['wiki_url', 'docs_url']);

    Queue::assertNothingPushed();
});

it('imports when docs url is valid even if wiki url is a stale non-GitHub value', function (): void {
    Queue::fake();

    $package = Package::factory()->create([
        'docs_url' => 'https://github.com/owner/repo/tree/main/docs',
        'wiki_url' => 'https://gitlab.com/legacy/repo.wiki.git',
    ]);

    $this->actingAs(verifiedAdmin())
        ->from(route('dashboard.packages.edit', $package))
        ->post(route('dashboard.packages.import-documentation', $package), [
            'docs_url' => 'https://github.com/owner/repo/tree/main/docs',
            'wiki_url' => 'https://gitlab.com/legacy/repo.wiki.git',
        ])
        ->assertRedirect(route('dashboard.packages.edit', $package))
        ->assertSessionHas('success')
        ->assertSessionHasNoErrors();

    expect($package->fresh()->wiki_url)->toBe('https://gitlab.com/legacy/repo.wiki.git');

    Queue::assertPushed(ImportWikiDocumentation::class);
});

it('rejects a non-GitHub docs url', function (): void {
    Queue::fake();

    $package = Package::factory()->create();

    $this->actingAs(verifiedAdmin())
        ->from(route('dashboard.packages.edit', $package))
        ->post(route('dashboard.packages.import-documentation', $package), [
            'docs_url' => 'https://gitlab.com/owner/repo',
            'wiki_url' => '',
        ])
        ->assertSessionHasErrors('docs_url');

    Queue::assertNothingPushed();
});

it('redirects guests to login', function (): void {
    Queue::fake();

    $package = Package::factory()->create();

    $this->post(route('dashboard.packages.import-documentation', $package), [
        'docs_url' => 'https://github.com/owner/repo',
    ])->assertRedirect(route('login'));

    Queue::assertNothingPushed();
});
