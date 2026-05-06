<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SurveyQuestion;
use App\Models\User;
use App\Policies\Concerns\AuthorizesSurveyTemplate;

final class SurveyQuestionPolicy
{
    use AuthorizesSurveyTemplate;

    public function viewAny(User $user): bool
    {
        return $this->canViewSurveyTemplate($user);
    }

    public function view(User $user, SurveyQuestion $surveyQuestion): bool
    {
        return $this->canViewSurveyTemplate($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageSurveyTemplate($user);
    }

    public function update(User $user, SurveyQuestion $surveyQuestion): bool
    {
        return $this->canManageSurveyTemplate($user);
    }

    public function delete(User $user, SurveyQuestion $surveyQuestion): bool
    {
        return $this->canManageSurveyTemplate($user);
    }

    public function restore(User $user, SurveyQuestion $surveyQuestion): bool
    {
        return $this->canManageSurveyTemplate($user);
    }

    public function forceDelete(User $user, SurveyQuestion $surveyQuestion): bool
    {
        return $this->canManageSurveyTemplate($user);
    }
}
