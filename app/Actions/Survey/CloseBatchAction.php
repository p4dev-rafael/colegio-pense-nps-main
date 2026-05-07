<?php

declare(strict_types=1);

namespace App\Actions\Survey;

use App\Enums\SurveyBatchStatus;
use App\Events\Survey\SurveyBatchClosed;
use App\Exceptions\Survey\SurveyException;
use App\Models\SurveyBatch;
use App\Models\User;
use Illuminate\Support\Carbon;

final class CloseBatchAction
{
    public function execute(SurveyBatch $batch, ?User $user = null, bool $isAutomatic = false): SurveyBatch
    {
        if ($batch->status !== SurveyBatchStatus::Active) {
            throw SurveyException::invalidBatchTransition($batch->status->value, SurveyBatchStatus::Closed->value);
        }

        $batch->forceFill([
            'status' => SurveyBatchStatus::Closed,
            'closed_at' => Carbon::now(),
        ])->save();

        SurveyBatchClosed::dispatch($batch->refresh(), $isAutomatic);

        return $batch;
    }
}
