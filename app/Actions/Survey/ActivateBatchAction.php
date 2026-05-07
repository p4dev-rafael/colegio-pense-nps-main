<?php

declare(strict_types=1);

namespace App\Actions\Survey;

use App\Enums\SurveyBatchStatus;
use App\Enums\UserRole;
use App\Events\Survey\SurveyBatchActivated;
use App\Exceptions\Survey\SurveyException;
use App\Models\SurveyBatch;
use App\Models\User;
use App\Services\SurveyBatchLinkService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class ActivateBatchAction
{
    public function __construct(private readonly SurveyBatchLinkService $linkService) {}

    public function execute(SurveyBatch $batch, ?User $user = null): SurveyBatch
    {
        $current = $batch->status;

        if (! $current->canTransitionTo(SurveyBatchStatus::Active)) {
            throw SurveyException::invalidBatchTransition($current->value, SurveyBatchStatus::Active->value);
        }

        if ($current === SurveyBatchStatus::Closed && ($user === null || $user->role !== UserRole::Admin)) {
            throw SurveyException::unauthorizedBatchReopen($batch->id);
        }

        $batch = DB::transaction(function () use ($batch): SurveyBatch {
            $batch->forceFill([
                'status' => SurveyBatchStatus::Active,
                'activated_at' => Carbon::now(),
                'closed_at' => null,
            ])->save();

            $this->linkService->ensurePublicToken($batch);

            return $batch->refresh();
        });

        SurveyBatchActivated::dispatch($batch);

        return $batch;
    }
}
