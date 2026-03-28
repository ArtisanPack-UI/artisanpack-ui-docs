<?php

use Livewire\Livewire;
use Modules\Auth\Livewire\Auth\Register as RegisterComponent;

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
