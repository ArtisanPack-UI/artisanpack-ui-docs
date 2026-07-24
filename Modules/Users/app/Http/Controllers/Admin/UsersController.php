<?php

declare(strict_types=1);

namespace Modules\Users\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Users\Http\Requests\UserRequest;

class UsersController extends Controller
{
    public function index(): Response
    {
        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'email_verified_at'])
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at?->toDateTimeString(),
                'edit_url' => route('dashboard.users.edit', $user),
                'destroy_url' => route('dashboard.users.destroy', $user),
            ])
            ->all();

        return Inertia::render('Users::Admin/Index', [
            'users' => $users,
            'create_url' => route('dashboard.users.add'),
            'current_user_id' => $this->currentUserId(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Users::Admin/Create', [
            'store_url' => route('dashboard.users.store'),
            'cancel_url' => route('dashboard.users'),
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = new User;
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->password = Hash::make($validated['password']);
        $user->email_verified_at = now();
        $user->save();

        return redirect()
            ->route('dashboard.users.edit', $user)
            ->with('success', 'User created successfully!');
    }

    public function edit(User $user): Response
    {
        return Inertia::render('Users::Admin/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at?->toDateTimeString(),
            ],
            'update_url' => route('dashboard.users.update', $user),
            'destroy_url' => route('dashboard.users.destroy', $user),
            'index_url' => route('dashboard.users'),
            'is_current_user' => $user->id === $this->currentUserId(),
        ]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()
            ->route('dashboard.users.edit', $user)
            ->with('success', 'User updated successfully!');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === $this->currentUserId()) {
            return redirect()
                ->route('dashboard.users')
                ->withErrors(['user' => 'You cannot delete your own account.']);
        }

        $user->delete();

        return redirect()
            ->route('dashboard.users')
            ->with('success', 'User deleted.');
    }

    protected function currentUserId(): ?int
    {
        $user = request()->user();

        return $user instanceof User ? $user->id : null;
    }
}
