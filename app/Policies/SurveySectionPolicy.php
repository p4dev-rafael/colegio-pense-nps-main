<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SurveySection;
use App\Models\User;
use App\Policies\Concerns\AuthorizesSurveyTemplate;

final class SurveySectionPolicy
{
    use AuthorizesSurveyTemplate;

    public function viewAny(User $user): bool
    {
        return $this->canViewSurveyTemplate($user);
    }

    public function view(User $user, SurveySection $surveySection): bool
    {
        return $this->canViewSurveyTemplate($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageSurveyTemplate($user);
    }

    public function update(User $user, SurveySection $surveySection): bool
    {
        return $this->canManageSurveyTemplate($user);
    }

    public function delete(User $user, SurveySection $surveySection): bool
    {
        return $this->canManageSurveyTemplate($user);
    }

    public function restore(User $user, SurveySection $surveySection): bool
    {
        return $this->canManageSurveyTemplate($user);
    }

    public function forceDelete(User $user, SurveySection $surveySection): bool
    {
        return $this->canManageSurveyTemplate($user);
    }
}
