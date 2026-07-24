<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;

function verifiedUsersUser(): User
{
    return User::factory()->admin()->create(['email_verified_at' => now()]);
}

function verifiedUsersEditor(): User
{
    return User::factory()->editor()->create(['email_verified_at' => now()]);
}

it('lists users for verified users', function (): void {
    $current = verifiedUsersUser();
    User::factory()->count(2)->create();

    $this->actingAs($current)
        ->get(route('dashboard.users'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Users::Admin/Index')
            ->has('users', 3)
            ->where('create_url', route('dashboard.users.add'))
            ->where('current_user_id', $current->id)
        );
});

it('redirects guests from the users list to login', function (): void {
    $this->get(route('dashboard.users'))->assertRedirect(route('login'));
});

it('redirects unverified users from the users list', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('dashboard.users'))
        ->assertRedirect(route('verification.notice'));
});

it('renders the create form', function (): void {
    $this->actingAs(verifiedUsersUser())
        ->get(route('dashboard.users.add'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Users::Admin/Create')
            ->where('store_url', route('dashboard.users.store'))
            ->where('cancel_url', route('dashboard.users'))
        );
});

it('creates a user with valid data and redirects to edit', function (): void {
    $response = $this->actingAs(verifiedUsersUser())
        ->post(route('dashboard.users.store'), [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'editor',
        ]);

    $user = User::where('email', 'ada@example.com')->firstOrFail();

    $response->assertRedirect(route('dashboard.users.edit', $user));
    expect($user->name)->toBe('Ada Lovelace');
    expect($user->role->value)->toBe('editor');
    expect(Hash::check('Password123!', $user->password))->toBeTrue();
    expect($user->email_verified_at)->not->toBeNull();
});

it('requires name, email, password, and role on create', function (): void {
    $this->actingAs(verifiedUsersUser())
        ->post(route('dashboard.users.store'), [])
        ->assertSessionHasErrors(['name', 'email', 'password', 'role']);
});

it('rejects duplicate emails on create', function (): void {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->actingAs(verifiedUsersUser())
        ->post(route('dashboard.users.store'), [
            'name' => 'Copy',
            'email' => 'taken@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'editor',
        ])
        ->assertSessionHasErrors(['email']);
});

it('rejects unconfirmed passwords on create', function (): void {
    $this->actingAs(verifiedUsersUser())
        ->post(route('dashboard.users.store'), [
            'name' => 'Ada',
            'email' => 'ada@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Different123!',
            'role' => 'editor',
        ])
        ->assertSessionHasErrors(['password']);
});

it('renders the edit form with the user payload', function (): void {
    $current = verifiedUsersUser();
    $target = User::factory()->create(['name' => 'Edit Me']);

    $this->actingAs($current)
        ->get(route('dashboard.users.edit', $target))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $view) => $view
            ->component('Users::Admin/Edit')
            ->where('user.id', $target->id)
            ->where('user.name', 'Edit Me')
            ->where('is_current_user', false)
            ->where('update_url', route('dashboard.users.update', $target))
            ->where('destroy_url', route('dashboard.users.destroy', $target))
        );
});

it('flags the current user in the edit payload', function (): void {
    $current = verifiedUsersUser();

    $this->actingAs($current)
        ->get(route('dashboard.users.edit', $current))
        ->assertInertia(fn (AssertableInertia $view) => $view
            ->where('is_current_user', true)
        );
});

it('updates a user without changing password when blank', function (): void {
    $user = User::factory()->create(['name' => 'Original']);
    $originalPassword = $user->password;

    $this->actingAs(verifiedUsersUser())
        ->patch(route('dashboard.users.update', $user), [
            'name' => 'Renamed',
            'email' => $user->email,
            'role' => $user->role->value,
        ])
        ->assertRedirect(route('dashboard.users.edit', $user));

    $fresh = $user->fresh();
    expect($fresh->name)->toBe('Renamed');
    expect($fresh->password)->toBe($originalPassword);
});

it('updates a user password when provided', function (): void {
    $user = User::factory()->create();

    $this->actingAs(verifiedUsersUser())
        ->patch(route('dashboard.users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
            'role' => $user->role->value,
        ])
        ->assertSessionHasNoErrors();

    expect(Hash::check('NewPassword123!', $user->fresh()->password))->toBeTrue();
});

it('allows updating a user without changing their email', function (): void {
    $user = User::factory()->create(['email' => 'keeper@example.com']);

    $this->actingAs(verifiedUsersUser())
        ->patch(route('dashboard.users.update', $user), [
            'name' => 'Keeper',
            'email' => 'keeper@example.com',
            'role' => $user->role->value,
        ])
        ->assertSessionHasNoErrors();
});

it('rejects updating to an email that is taken by another user', function (): void {
    User::factory()->create(['email' => 'taken@example.com']);
    $user = User::factory()->create(['email' => 'mine@example.com']);

    $this->actingAs(verifiedUsersUser())
        ->patch(route('dashboard.users.update', $user), [
            'name' => $user->name,
            'email' => 'taken@example.com',
            'role' => $user->role->value,
        ])
        ->assertSessionHasErrors(['email']);
});

it('deletes a user and redirects back to the list', function (): void {
    $current = verifiedUsersUser();
    $target = User::factory()->create();

    $this->actingAs($current)
        ->delete(route('dashboard.users.destroy', $target))
        ->assertRedirect(route('dashboard.users'));

    expect(User::find($target->id))->toBeNull();
});

it('prevents deleting your own account', function (): void {
    $current = verifiedUsersUser();

    $this->actingAs($current)
        ->delete(route('dashboard.users.destroy', $current))
        ->assertForbidden();

    expect(User::find($current->id))->not->toBeNull();
});

it('prevents an admin from changing their own role', function (): void {
    $current = verifiedUsersUser();

    $this->actingAs($current)
        ->patch(route('dashboard.users.update', $current), [
            'name' => $current->name,
            'email' => $current->email,
            'role' => 'editor',
        ])
        ->assertSessionHasNoErrors();

    expect($current->fresh()->role->value)->toBe('admin');
});

it('forbids editors from accessing user management', function (): void {
    $editor = verifiedUsersEditor();

    $this->actingAs($editor)->get(route('dashboard.users'))->assertForbidden();
    $this->actingAs($editor)->get(route('dashboard.users.add'))->assertForbidden();
    $this->actingAs($editor)->post(route('dashboard.users.store'), [])->assertForbidden();

    $target = User::factory()->create();
    $this->actingAs($editor)->get(route('dashboard.users.edit', $target))->assertForbidden();
    $this->actingAs($editor)->patch(route('dashboard.users.update', $target), [])->assertForbidden();
    $this->actingAs($editor)->delete(route('dashboard.users.destroy', $target))->assertForbidden();
});

it('rejects unauthenticated writes', function (): void {
    $user = User::factory()->create();

    $this->post(route('dashboard.users.store'), [])->assertRedirect(route('login'));
    $this->patch(route('dashboard.users.update', $user), [])->assertRedirect(route('login'));
    $this->delete(route('dashboard.users.destroy', $user))->assertRedirect(route('login'));
});
