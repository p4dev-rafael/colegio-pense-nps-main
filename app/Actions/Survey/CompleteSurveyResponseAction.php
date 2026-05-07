<?php

declare(strict_types=1);

namespace App\Actions\Survey;

use App\DTOs\SurveyResponseData;
use App\Enums\RespondentType;
use App\Events\Survey\SurveyResponseCompleted;
use App\Exceptions\Survey\SurveyException;
use App\Models\Enrollment;
use App\Models\SurveyBatch;
use App\Models\SurveyResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class CompleteSurveyResponseAction
{
    public function execute(SurveyResponseData $data, ?Enrollment $enrollment, SurveyBatch $batch): SurveyResponse
    {
        if (! $batch->isAcceptingResponses()) {
            throw SurveyException::batchNotAcceptingResponses($batch->id);
        }

        if ($enrollment === null) {
            if ($batch->requires_identification) {
                throw SurveyException::identificationRequired($batch->id);
            }

            return $this->completeAnonymous($data, $batch);
        }

        if ($enrollment->unit_id !== $batch->unit_id) {
            throw SurveyException::batchNotAcceptingResponses($batch->id);
        }

        $existing = SurveyResponse::query()
            ->where('enrollment_id', $enrollment->id)
            ->where('survey_batch_id', $batch->id)
            ->first();

        if ($existing !== null && $existing->is_completed) {
            throw SurveyException::duplicateResponse($enrollment->id, $batch->id);
        }

        $segment = $enrollment->segment()->firstOrFail();
        $student = $enrollment->student()->firstOrFail();

        $respondentType = RespondentType::fromSegmentGroup($segment->group);
        $respondentName = $respondentType === RespondentType::Guardian
            ? ($student->guardian_name ?? $student->name)
            : $student->name;

        $response = DB::transaction(function () use ($data, $batch, $enrollment, $segment, $respondentType, $respondentName, $existing): SurveyResponse {
            $payload = [
                'survey_batch_id' => $batch->id,
                'enrollment_id' => $enrollment->id,
                'unit_id' => $enrollment->unit_id,
                'segment_id' => $segment->id,
                'respondent_type' => $respondentType,
                'respondent_name' => $respondentName,
                'answers' => $data->answers,
                'is_completed' => true,
                'completed_at' => Carbon::now(),
                'ip_address' => $data->ipAddress,
                'user_agent' => $data->userAgent,
            ];

            if ($existing !== null) {
                $existing->forceFill($payload)->save();

                return $existing->refresh();
            }

            return SurveyResponse::query()->create($payload);
        });

        SurveyResponseCompleted::dispatch($response);

        return $response;
    }

    private function completeAnonymous(SurveyResponseData $data, SurveyBatch $batch): SurveyResponse
    {
        $respondentName = __('survey.public.anonymous_respondent');

        $response = DB::transaction(function () use ($data, $batch, $respondentName): SurveyResponse {
            return SurveyResponse::query()->create([
                'survey_batch_id' => $batch->id,
                'enrollment_id' => null,
                'unit_id' => $batch->unit_id,
                'segment_id' => null,
                'respondent_type' => RespondentType::Anonymous,
                'respondent_name' => $respondentName,
                'answers' => $data->answers,
                'is_completed' => true,
                'completed_at' => Carbon::now(),
                'ip_address' => $data->ipAddress,
                'user_agent' => $data->userAgent,
            ]);
        });

        SurveyResponseCompleted::dispatch($response);

        return $response;
    }
}
