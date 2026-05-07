<?php

declare(strict_types=1);

namespace App\Events\Survey;

use App\Models\SurveyResponse;
use Illuminate\Foundation\Events\Dispatchable;

final class SurveyResponseCompleted
{
    use Dispatchable;

    public function __construct(public readonly SurveyResponse $response) {}
}
