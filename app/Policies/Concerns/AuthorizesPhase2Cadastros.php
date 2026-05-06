<?php

declare(strict_types=1);

namespace App\Policies\Concerns;

use App\Enums\UserRole;
use App\Models\User;

trait AuthorizesPhase2Cadastros
{
    protected function canManageCadastros(User $user): bool
    {
        return $user->role === UserRole::Admin || $user->role === UserRole::Operator;
    }
}
