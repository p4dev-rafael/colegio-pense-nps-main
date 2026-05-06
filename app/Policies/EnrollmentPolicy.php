<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\User;
use App\Policies\Concerns\AuthorizesPhase2Cadastros;

final class EnrollmentPolicy
{
    use AuthorizesPhase2Cadastros;

    public function viewAny(User $user): bool
    {
        return $this->canManageCadastros($user);
    }

    public function view(User $user, Enrollment $enrollment): bool
    {
        return $this->canManageCadastros($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageCadastros($user);
    }

    public function update(User $user, Enrollment $enrollment): bool
    {
        return $this->canManageCadastros($user);
    }

    public function delete(User $user, Enrollment $enrollment): bool
    {
        return $this->canManageCadastros($user);
    }

    public function restore(User $user, Enrollment $enrollment): bool
    {
        return $this->canManageCadastros($user);
    }

    public function forceDelete(User $user, Enrollment $enrollment): bool
    {
        return $this->canManageCadastros($user);
    }
}
