<?php

use App\Models\User;
use Livewire\Livewire;
use Modules\Auth\Livewire\Auth\Login as LoginComponent;

it('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertStatus(200);
});

it('users can log in', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password'),
    ]);

    Livewire::test(LoginComponent::class)
        ->set('email', $user->email)
        ->set('password', 'password')
        ->set('remember', false)
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});
