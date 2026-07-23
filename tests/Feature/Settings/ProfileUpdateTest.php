<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Testing\AssertableInertia;

it('renders the Inertia profile page for authenticated users', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard.settings.profile'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Settings/Profile')
        ->where('user.name', $user->name)
        ->where('user.email', $user->email)
        ->has('mustVerifyEmail')
        ->has('isVerified')
    );
});

it('redirects guests away from the profile page', function () {
    $this->get(route('dashboard.settings.profile'))->assertRedirect(route('login'));
});

it('updates profile information via Fortify PUT /user/profile-information', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from(route('dashboard.settings.profile'))
        ->put('/user/profile-information', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response->assertRedirect(route('dashboard.settings.profile'));

    $user->refresh();

    expect($user->name)->toEqual('Test User');
    expect($user->email)->toEqual('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

it('keeps the email verified state when the email is unchanged', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('dashboard.settings.profile'))
        ->put('/user/profile-information', [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

it('validates profile updates', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from(route('dashboard.settings.profile'))
        ->put('/user/profile-information', [
            'name' => '',
            'email' => 'not-an-email',
        ]);

    $response->assertSessionHasErrors(['name', 'email'], null, 'updateProfileInformation');
});
