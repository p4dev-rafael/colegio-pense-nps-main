<?php

declare(strict_types=1);

namespace App\Policies\Concerns;

use App\Enums\UserRole;
use App\Models\User;

/**
 * Phase 4 (Core) authorization rules.
 *
 * Both Admin and Operator can view and operate batches/responses; only Admin
 * can reopen a closed batch.
 */
trait AuthorizesPhase4Core
{
    protected function canViewSurveyOperation(User $user): bool
    {
        return $user->role === UserRole::Admin || $user->role === UserRole::Operator;
    }

    protected function canManageSurveyBatch(User $user): bool
    {
        return $user->role === UserRole::Admin || $user->role === UserRole::Operator;
    }

    protected function canReopenSurveyBatch(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }
}
