<?php

declare(strict_types=1);

namespace App\Listeners\Survey;

use App\Events\Survey\SurveyBatchClosed;
use Illuminate\Support\Facades\Log;

final class LogBatchClosure
{
    public function handle(SurveyBatchClosed $event): void
    {
        Log::info('survey.batch.closed', [
            'batch_id' => $event->batch->id,
            'unit_id' => $event->batch->unit_id,
            'is_automatic' => $event->isAutomatic,
            'closed_at' => $event->batch->closed_at?->toIso8601String(),
            'total_responses' => $event->batch->surveyResponses()->count(),
        ]);
    }
}
