<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Users\Http\Controllers\Admin\UsersController as AdminUsersController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard/users', [AdminUsersController::class, 'index'])->name('dashboard.users');
    Route::get('/dashboard/users/add-user', [AdminUsersController::class, 'create'])->name('dashboard.users.add');
    Route::post('/dashboard/users', [AdminUsersController::class, 'store'])->name('dashboard.users.store');
    Route::get('/dashboard/users/{user}', [AdminUsersController::class, 'edit'])->name('dashboard.users.edit');
    Route::patch('/dashboard/users/{user}', [AdminUsersController::class, 'update'])->name('dashboard.users.update');
    Route::delete('/dashboard/users/{user}', [AdminUsersController::class, 'destroy'])->name('dashboard.users.destroy');
});
