<?php

declare(strict_types=1);

use App\Enums\TokenAbility;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

function confirmPasswordForApiTokens(): void
{
    session()->put('auth.password_confirmed_at', time());
}

it('renders the api tokens page with the user\'s tokens', function () {
    $user = User::factory()->create();
    confirmPasswordForApiTokens();
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

it('sends no-store cache headers so the freshly issued token cannot be cached', function () {
    $user = User::factory()->create();
    confirmPasswordForApiTokens();

    $response = $this->actingAs($user)->get(route('dashboard.settings.api-tokens'));

    $response->assertOk();
    expect($response->headers->get('Cache-Control'))->toContain('no-store');
});

it('redirects guests away from the api tokens page', function () {
    $response = $this->get(route('dashboard.settings.api-tokens'));

    $response->assertRedirect(route('login'));
});

it('redirects to the password-confirm screen when the password has not been confirmed', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard.settings.api-tokens'));

    $response->assertRedirect(route('password.confirm'));
});

it('issues a new token and flashes it once for display', function () {
    $user = User::factory()->create();
    confirmPasswordForApiTokens();

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

it('persists only the sha-256 hash of the token, never the plain text', function () {
    $user = User::factory()->create();
    confirmPasswordForApiTokens();

    $this->actingAs($user)
        ->post(route('dashboard.settings.api-tokens.store'), [
            'name' => 'ci-bot',
            'abilities' => ['packages:read'],
        ])
        ->assertRedirect();

    /** @var array{name: string, plain_text: string} $flashed */
    $flashed = session('new_api_token');
    $plain = $flashed['plain_text'];
    $stored = $user->refresh()->tokens()->firstWhere('name', 'ci-bot');

    expect($stored)->not->toBeNull()
        ->and($stored->token)->not->toBe($plain)
        ->and($stored->token)->not->toContain(explode('|', $plain, 2)[1] ?? $plain)
        ->and($stored->token)->toHaveLength(64)
        ->and($stored->token)->toBe(hash('sha256', explode('|', $plain, 2)[1]));
});

it('rejects tokens with abilities outside the allow-list', function () {
    $user = User::factory()->create();
    confirmPasswordForApiTokens();

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
    confirmPasswordForApiTokens();

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
    confirmPasswordForApiTokens();
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
    confirmPasswordForApiTokens();
    $token = $owner->createToken('owned', [TokenAbility::PackagesRead->value])->accessToken;

    $response = $this->actingAs($intruder)
        ->delete(route('dashboard.settings.api-tokens.destroy', ['token' => $token->id]));

    $response->assertNotFound();
    expect($owner->refresh()->tokens()->count())->toBe(1);
});

it('flags tokens older than 90 days for rotation', function () {
    $user = User::factory()->create();
    confirmPasswordForApiTokens();

    $stale = $user->createToken('stale', [TokenAbility::PackagesRead->value])->accessToken;
    $stale->forceFill(['created_at' => now()->subDays(120)])->save();

    $user->createToken('fresh', [TokenAbility::PackagesRead->value]);

    $response = $this->actingAs($user)->get(route('dashboard.settings.api-tokens'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Settings/ApiTokens')
        ->where('rotationAgeDays', 90)
        ->has('tokens', 2)
        ->where('tokens', fn ($tokens) => collect($tokens)
            ->firstWhere('name', 'stale')['needs_rotation'] === true
            && collect($tokens)->firstWhere('name', 'fresh')['needs_rotation'] === false)
    );
});

it('rotates a stale token: revokes the old one, mints a new one with the same abilities', function () {
    $user = User::factory()->create();
    confirmPasswordForApiTokens();

    $original = $user->createToken('artisanpackui.dev', [
        TokenAbility::PackagesRead->value,
        TokenAbility::DocsWrite->value,
    ])->accessToken;
    $original->forceFill(['created_at' => now()->subDays(120)])->save();

    $response = $this->actingAs($user)
        ->from(route('dashboard.settings.api-tokens'))
        ->post(route('dashboard.settings.api-tokens.rotate', ['token' => $original->id]));

    $response->assertRedirect(route('dashboard.settings.api-tokens'));
    $response->assertSessionHas('status', 'api-token-rotated');

    $flashed = session('new_api_token');
    expect($flashed['name'])->toBe('artisanpackui.dev')
        ->and($flashed['plain_text'])->toBeString()
        ->and($flashed['plain_text'])->not->toBe('');

    $tokens = $user->refresh()->tokens()->get();
    expect($tokens)->toHaveCount(1)
        ->and($tokens->first()->id)->not->toBe($original->id)
        ->and($tokens->first()->name)->toBe('artisanpackui.dev')
        ->and(array_values((array) $tokens->first()->abilities))
        ->toEqual(['packages:read', 'docs:write']);
});

it('does not let a user rotate another user\'s token', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    confirmPasswordForApiTokens();
    $token = $owner->createToken('owned', [TokenAbility::PackagesRead->value])->accessToken;

    $response = $this->actingAs($intruder)
        ->post(route('dashboard.settings.api-tokens.rotate', ['token' => $token->id]));

    $response->assertNotFound();
    expect($owner->refresh()->tokens()->count())->toBe(1);
});
