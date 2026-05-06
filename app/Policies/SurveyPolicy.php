<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Survey;
use App\Models\User;
use App\Policies\Concerns\AuthorizesSurveyTemplate;

final class SurveyPolicy
{
    use AuthorizesSurveyTemplate;

    public function viewAny(User $user): bool
    {
        return $this->canViewSurveyTemplate($user);
    }

    public function view(User $user, Survey $survey): bool
    {
        return $this->canViewSurveyTemplate($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageSurveyTemplate($user);
    }

    public function update(User $user, Survey $survey): bool
    {
        return $this->canManageSurveyTemplate($user);
    }

    public function delete(User $user, Survey $survey): bool
    {
        return $this->canManageSurveyTemplate($user);
    }

    public function restore(User $user, Survey $survey): bool
    {
        return $this->canManageSurveyTemplate($user);
    }

    public function forceDelete(User $user, Survey $survey): bool
    {
        return $this->canManageSurveyTemplate($user);
    }
}
