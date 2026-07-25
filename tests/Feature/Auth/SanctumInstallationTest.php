<?php

declare(strict_types=1);

use App\Enums\TokenAbility;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;

it('creates the personal_access_tokens table', function () {
    expect(Schema::hasTable('personal_access_tokens'))->toBeTrue();
    expect(Schema::hasColumns('personal_access_tokens', [
        'tokenable_type',
        'tokenable_id',
        'name',
        'token',
        'abilities',
        'last_used_at',
        'expires_at',
    ]))->toBeTrue();
});

it('gives the user model Sanctum token issuance', function () {
    $user = new User;

    expect(class_uses_recursive($user))->toContain(HasApiTokens::class);
});

it('lets a user mint a token scoped to allow-listed abilities', function () {
    $user = User::factory()->create();

    $newToken = $user->createToken('artisanpackui.dev', [
        TokenAbility::PackagesRead->value,
        TokenAbility::DocsWrite->value,
    ]);

    expect($newToken->plainTextToken)->toBeString()->not->toBeEmpty();
    expect($newToken->accessToken)->toBeInstanceOf(PersonalAccessToken::class);
    expect($newToken->accessToken->can(TokenAbility::PackagesRead->value))->toBeTrue();
    expect($newToken->accessToken->can(TokenAbility::DocsWrite->value))->toBeTrue();
    expect($newToken->accessToken->can(TokenAbility::PackagesWrite->value))->toBeFalse();
});

it('registers the bounded ability allow-list', function () {
    expect(TokenAbility::values())->toEqual([
        'packages:read',
        'packages:write',
        'docs:read',
        'docs:write',
        'changelogs:read',
        'changelogs:write',
    ]);

    expect(config('sanctum.abilities'))->toEqual(TokenAbility::values());
});

it('recognizes allow-listed abilities and rejects unknown ones', function () {
    expect(TokenAbility::isAllowed('packages:read'))->toBeTrue();
    expect(TokenAbility::isAllowed('docs:write'))->toBeTrue();
    expect(TokenAbility::isAllowed('changelogs:read'))->toBeTrue();

    expect(TokenAbility::isAllowed('*'))->toBeFalse();
    expect(TokenAbility::isAllowed('users:write'))->toBeFalse();
    expect(TokenAbility::isAllowed('packages:delete'))->toBeFalse();
});

it('exposes options suitable for the token issuance UI checklist', function () {
    $options = TokenAbility::options();

    expect($options)->toHaveCount(6);
    expect($options[0])->toEqual(['value' => 'packages:read', 'label' => 'Packages: Read']);
    expect($options[5])->toEqual(['value' => 'changelogs:write', 'label' => 'Changelogs: Write']);
});
