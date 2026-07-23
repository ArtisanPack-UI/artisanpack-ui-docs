<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia;

it('renders the Inertia verify-email notice page for unverified users', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get(route('verification.notice'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Auth/VerifyEmail')
        ->has('status')
    );
});

it('redirects guests away from the verification notice page', function () {
    $response = $this->get(route('verification.notice'));

    $response->assertRedirect(route('login'));
});

it('resends the verification email via Fortify POST /email/verification-notification', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)
        ->from(route('verification.notice'))
        ->post('/email/verification-notification');

    $response->assertRedirect(route('verification.notice'));
    $response->assertSessionHas('status', 'verification-link-sent');
    Notification::assertSentTo($user, VerifyEmail::class);
});

it('marks the user as verified when the signed verification link is visited', function () {
    Event::fake();

    $user = User::factory()->unverified()->create();

    $verifyUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->getKey(), 'hash' => sha1($user->getEmailForVerification())]
    );

    $response = $this->actingAs($user)->get($verifyUrl);

    $response->assertRedirect(route('verification.verified'));
    Event::assertDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('renders the Inertia verified callback page after verification succeeds', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('verification.verified'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Auth/Verified')
    );
});

it('blocks access to the verified callback page for unverified users', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get(route('verification.verified'));

    $response->assertRedirect(route('verification.notice'));
});
