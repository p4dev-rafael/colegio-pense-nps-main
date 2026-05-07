<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SurveyBatch;
use App\Models\User;
use App\Policies\Concerns\AuthorizesPhase4Core;

final class SurveyBatchPolicy
{
    use AuthorizesPhase4Core;

    public function viewAny(User $user): bool
    {
        return $this->canViewSurveyOperation($user);
    }

    public function view(User $user, SurveyBatch $batch): bool
    {
        return $this->canViewSurveyOperation($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageSurveyBatch($user);
    }

    public function update(User $user, SurveyBatch $batch): bool
    {
        return $this->canManageSurveyBatch($user);
    }

    public function delete(User $user, SurveyBatch $batch): bool
    {
        return $this->canManageSurveyBatch($user);
    }

    public function restore(User $user, SurveyBatch $batch): bool
    {
        return $this->canManageSurveyBatch($user);
    }

    public function forceDelete(User $user, SurveyBatch $batch): bool
    {
        return $this->canManageSurveyBatch($user);
    }

    public function reopen(User $user, SurveyBatch $batch): bool
    {
        return $this->canReopenSurveyBatch($user);
    }
}
