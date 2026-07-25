<?php

declare(strict_types=1);

use App\Enums\TokenAbility;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

it('renders the api tokens page with the user\'s tokens', function () {
    $user = User::factory()->create();
    $user->createToken('artisanpackui.dev', [TokenAbility::PackagesRead->value, TokenAbility::DocsWrite->value]);

    $response = $this->actingAs($user)->get(route('dashboard.settings.api-tokens'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Settings/ApiTokens')
        ->has('tokens', 1, fn (AssertableInertia $token) => $token
            ->where('name', 'artisanpackui.dev')
            ->where('abilities', ['packages:read', 'docs:write'])
            ->etc()
        )
        ->has('availableAbilities', count(TokenAbility::cases()))
        ->where('newToken', null)
    );
});

it('redirects guests away from the api tokens page', function () {
    $response = $this->get(route('dashboard.settings.api-tokens'));

    $response->assertRedirect(route('login'));
});

it('issues a new token and flashes it once for display', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from(route('dashboard.settings.api-tokens'))
        ->post(route('dashboard.settings.api-tokens.store'), [
            'name' => 'ci-bot',
            'abilities' => ['packages:read', 'docs:read'],
        ]);

    $response->assertRedirect(route('dashboard.settings.api-tokens'));
    $response->assertSessionHas('status', 'api-token-created');
    $response->assertSessionHas('new_api_token', fn (array $token) => $token['name'] === 'ci-bot'
        && is_string($token['plain_text'])
        && $token['plain_text'] !== '');

    expect($user->refresh()->tokens()->where('name', 'ci-bot')->exists())->toBeTrue();
});

it('rejects tokens with abilities outside the allow-list', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from(route('dashboard.settings.api-tokens'))
        ->post(route('dashboard.settings.api-tokens.store'), [
            'name' => 'ci-bot',
            'abilities' => ['packages:read', 'admin:*'],
        ]);

    $response->assertRedirect(route('dashboard.settings.api-tokens'));
    $response->assertSessionHasErrors('abilities.1');
    expect($user->refresh()->tokens()->count())->toBe(0);
});

it('rejects tokens with no abilities', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from(route('dashboard.settings.api-tokens'))
        ->post(route('dashboard.settings.api-tokens.store'), [
            'name' => 'ci-bot',
            'abilities' => [],
        ]);

    $response->assertSessionHasErrors('abilities');
    expect($user->refresh()->tokens()->count())->toBe(0);
});

it('revokes a token that belongs to the current user', function () {
    $user = User::factory()->create();
    $token = $user->createToken('to-revoke', [TokenAbility::PackagesRead->value])->accessToken;

    $response = $this->actingAs($user)
        ->from(route('dashboard.settings.api-tokens'))
        ->delete(route('dashboard.settings.api-tokens.destroy', ['token' => $token->id]));

    $response->assertRedirect(route('dashboard.settings.api-tokens'));
    $response->assertSessionHas('status', 'api-token-revoked');
    expect($user->refresh()->tokens()->count())->toBe(0);
});

it('does not let a user revoke another user\'s token', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $token = $owner->createToken('owned', [TokenAbility::PackagesRead->value])->accessToken;

    $response = $this->actingAs($intruder)
        ->delete(route('dashboard.settings.api-tokens.destroy', ['token' => $token->id]));

    $response->assertNotFound();
    expect($owner->refresh()->tokens()->count())->toBe(1);
});
