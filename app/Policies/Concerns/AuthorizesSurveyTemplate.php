<?php

declare(strict_types=1);

namespace App\Policies\Concerns;

use App\Enums\UserRole;
use App\Models\User;

/**
 * Survey template (Phase 3) authorization rules.
 *
 * Both Admin and Operator can read; only Admin can mutate or restore.
 */
trait AuthorizesSurveyTemplate
{
    protected function canViewSurveyTemplate(User $user): bool
    {
        return $user->role === UserRole::Admin || $user->role === UserRole::Operator;
    }

    protected function canManageSurveyTemplate(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }
}
