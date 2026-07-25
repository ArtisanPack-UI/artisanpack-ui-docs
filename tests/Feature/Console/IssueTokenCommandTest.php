<?php

declare(strict_types=1);

use App\Models\User;

it('issues a token when all options are supplied non-interactively', function () {
    $user = User::factory()->create(['email' => 'admin@example.com']);

    $this->artisan('artisanpack:issue-token', [
        '--user' => 'admin@example.com',
        '--name' => 'artisanpackui.dev',
        '--ability' => ['packages:read', 'docs:write'],
    ])
        ->expectsOutputToContain('Token generated.')
        ->expectsOutputToContain('Issued to: admin@example.com')
        ->expectsOutputToContain('Name: artisanpackui.dev')
        ->expectsOutputToContain('Abilities: packages:read, docs:write')
        ->assertSuccessful();

    $token = $user->refresh()->tokens()->firstWhere('name', 'artisanpackui.dev');
    expect($token)->not->toBeNull()
        ->and($token->abilities)->toBe(['packages:read', 'docs:write']);
});

it('fails when the user email does not resolve to a user', function () {
    $this->artisan('artisanpack:issue-token', [
        '--user' => 'nobody@example.com',
        '--name' => 'x',
        '--ability' => ['packages:read'],
    ])
        ->expectsOutputToContain('No user found')
        ->assertFailed();
});

it('rejects abilities that are not in the allow-list', function () {
    $user = User::factory()->create(['email' => 'admin@example.com']);

    $this->artisan('artisanpack:issue-token', [
        '--user' => 'admin@example.com',
        '--name' => 'x',
        '--ability' => ['admin:*'],
    ])
        ->expectsOutputToContain('Unknown ability: admin:*')
        ->assertFailed();

    expect($user->refresh()->tokens()->count())->toBe(0);
});
