<?php

declare(strict_types=1);

use App\Models\User;

it('renders the admin dashboard for verified users', function (): void {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk();
});
