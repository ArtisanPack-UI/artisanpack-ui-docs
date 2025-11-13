<?php

use Livewire\Livewire;
use Modules\Auth\Livewire\Auth\Register as RegisterComponent;

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertStatus(200);
});

test('new users can register', function () {
    Livewire::test(RegisterComponent::class)
        ->set('name', 'John Doe')
        ->set('email', 'test@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});
