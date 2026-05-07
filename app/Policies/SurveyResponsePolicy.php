<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SurveyResponse;
use App\Models\User;
use App\Policies\Concerns\AuthorizesPhase4Core;

final class SurveyResponsePolicy
{
    use AuthorizesPhase4Core;

    public function viewAny(User $user): bool
    {
        return $this->canViewSurveyOperation($user);
    }

    public function view(User $user, SurveyResponse $response): bool
    {
        return $this->canViewSurveyOperation($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, SurveyResponse $response): bool
    {
        return false;
    }

    public function delete(User $user, SurveyResponse $response): bool
    {
        return $this->canManageSurveyBatch($user);
    }

    public function restore(User $user, SurveyResponse $response): bool
    {
        return $this->canManageSurveyBatch($user);
    }

    public function forceDelete(User $user, SurveyResponse $response): bool
    {
        return $this->canManageSurveyBatch($user);
    }
}
