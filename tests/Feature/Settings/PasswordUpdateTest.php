<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;

test('password can be updated', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $this->actingAs($user);

    $response = Volt::test('settings.password')
        ->set('current_password', 'password')
        ->set('password', 'Str0ng-Passw0rd!')
        ->set('password_confirmation', 'Str0ng-Passw0rd!')
        ->call('updatePassword');

    $response->assertHasNoErrors();

    expect(Hash::check('Str0ng-Passw0rd!', $user->refresh()->password))->toBeTrue();
});

test('correct password must be provided to update password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $this->actingAs($user);

    $response = Volt::test('settings.password')
        ->set('current_password', 'wrong-password')
        ->set('password', 'Str0ng-Passw0rd!')
        ->set('password_confirmation', 'Str0ng-Passw0rd!')
        ->call('updatePassword');

    $response->assertHasErrors(['current_password']);
});
