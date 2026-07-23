<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;

it('renders the Inertia password page for authenticated users', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard.settings.password'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Settings/Password')
        ->has('status')
    );
});

it('updates the password via Fortify PUT /user/password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $response = $this->actingAs($user)
        ->from(route('dashboard.settings.password'))
        ->put('/user/password', [
            'current_password' => 'password',
            'password' => 'Str0ng-Passw0rd!',
            'password_confirmation' => 'Str0ng-Passw0rd!',
        ]);

    $response->assertRedirect(route('dashboard.settings.password'));

    expect(Hash::check('Str0ng-Passw0rd!', $user->refresh()->password))->toBeTrue();
});

it('rejects password updates when current password is wrong', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $response = $this->actingAs($user)
        ->from(route('dashboard.settings.password'))
        ->put('/user/password', [
            'current_password' => 'wrong-password',
            'password' => 'Str0ng-Passw0rd!',
            'password_confirmation' => 'Str0ng-Passw0rd!',
        ]);

    $response->assertSessionHasErrors(['current_password'], null, 'updatePassword');
});
