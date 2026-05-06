<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Student;
use App\Models\User;
use App\Policies\Concerns\AuthorizesPhase2Cadastros;

final class StudentPolicy
{
    use AuthorizesPhase2Cadastros;

    public function viewAny(User $user): bool
    {
        return $this->canManageCadastros($user);
    }

    public function view(User $user, Student $student): bool
    {
        return $this->canManageCadastros($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageCadastros($user);
    }

    public function update(User $user, Student $student): bool
    {
        return $this->canManageCadastros($user);
    }

    public function delete(User $user, Student $student): bool
    {
        return $this->canManageCadastros($user);
    }

    public function restore(User $user, Student $student): bool
    {
        return $this->canManageCadastros($user);
    }

    public function forceDelete(User $user, Student $student): bool
    {
        return $this->canManageCadastros($user);
    }
}
