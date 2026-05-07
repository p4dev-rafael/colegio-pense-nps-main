<?php

declare(strict_types=1);

namespace App\Listeners\Survey;

use App\Events\Survey\SurveyBatchActivated;
use Illuminate\Support\Facades\Log;

final class LogBatchActivation
{
    public function handle(SurveyBatchActivated $event): void
    {
        Log::info('survey.batch.activated', [
            'batch_id' => $event->batch->id,
            'unit_id' => $event->batch->unit_id,
            'survey_id' => $event->batch->survey_id,
            'activated_at' => $event->batch->activated_at?->toIso8601String(),
        ]);
    }
}
