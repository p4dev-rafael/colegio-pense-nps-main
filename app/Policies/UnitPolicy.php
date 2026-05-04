<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Unit;
use App\Models\User;

final class UnitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function view(User $user, Unit $unit): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function update(User $user, Unit $unit): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function delete(User $user, Unit $unit): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function restore(User $user, Unit $unit): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function forceDelete(User $user, Unit $unit): bool
    {
        return $user->role === UserRole::Admin;
    }
}
