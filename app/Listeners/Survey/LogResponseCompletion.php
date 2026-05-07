<?php

declare(strict_types=1);

namespace App\Listeners\Survey;

use App\Events\Survey\SurveyResponseCompleted;
use Illuminate\Support\Facades\Log;

final class LogResponseCompletion
{
    public function handle(SurveyResponseCompleted $event): void
    {
        Log::info('survey.response.completed', [
            'response_id' => $event->response->id,
            'survey_batch_id' => $event->response->survey_batch_id,
            'enrollment_id' => $event->response->enrollment_id,
            'unit_id' => $event->response->unit_id,
            'segment_id' => $event->response->segment_id,
            'respondent_type' => $event->response->respondent_type->value,
        ]);
    }
}
