<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RespondentType;
use App\Models\Enrollment;
use App\Models\SurveyBatch;
use App\Models\SurveyResponse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Survey responses must reflect a coherent batch + enrollment (same tenant).
 *
 * Prefer: {@see self::paired()}
 *
 * @extends Factory<SurveyResponse>
 */
final class SurveyResponseFactory extends Factory
{
    protected $model = SurveyResponse::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>|null  $answers  Omit for empty template; otherwise full JSON payload.
     */
    public function paired(
        SurveyBatch $batch,
        Enrollment $enrollment,
        ?array $answers = null,
        bool $isCompleted = false,
    ): static {
        return $this->state(function () use ($batch, $enrollment, $answers, $isCompleted): array {
            $enrollment->loadMissing('student', 'segment');

            $student = $enrollment->student;
            $segment = $enrollment->segment;

            if ($student === null || $segment === null) {
                throw new \InvalidArgumentException('Enrollment must have student and segment.');
            }

            $respondentType = RespondentType::fromSegmentGroup($segment->group);
            $respondentName = $respondentType === RespondentType::Guardian
                ? ($student->guardian_name ?? $student->name)
                : $student->name;

            $answersPayload = $answers ?? [
                'version' => '1.0',
                'sections' => [],
            ];

            return [
                'survey_batch_id' => $batch->id,
                'enrollment_id' => $enrollment->id,
                'unit_id' => $batch->unit_id,
                'segment_id' => $segment->id,
                'respondent_type' => $respondentType->value,
                'respondent_name' => $respondentName,
                'answers' => $answersPayload,
                'is_completed' => $isCompleted,
                'completed_at' => $isCompleted ? now() : null,
            ];
        });
    }

    /**
     * Anonymous response (no enrollment), for batches with optional identification.
     *
     * @param  array<string, mixed>|null  $answers
     */
    public function anonymous(
        SurveyBatch $batch,
        ?array $answers = null,
        bool $isCompleted = true,
    ): static {
        return $this->state(function () use ($batch, $answers, $isCompleted): array {
            $answersPayload = $answers ?? [
                'version' => '1.0',
                'sections' => [],
            ];

            return [
                'survey_batch_id' => $batch->id,
                'enrollment_id' => null,
                'unit_id' => $batch->unit_id,
                'segment_id' => null,
                'respondent_type' => RespondentType::Anonymous,
                'respondent_name' => 'Anonymous (factory)',
                'answers' => $answersPayload,
                'is_completed' => $isCompleted,
                'completed_at' => $isCompleted ? now() : null,
            ];
        });
    }

    public function incomplete(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_completed' => false,
            'completed_at' => null,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_completed' => true,
            'completed_at' => now(),
        ]);
    }
}
