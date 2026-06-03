<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\NpsDashboardFiltersData;
use App\Enums\QuestionType;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\Unit;
use App\Support\Nps\NpsAggregationResult;
use App\Support\Nps\NpsBuckets;

/**
 * Aggregates persisted survey JSON payloads into doubled NPS views (RN02 + RN03; RN08 for NSA exclusions).
 */
final class NpsAggregationService
{
    /**
     * @var array<string, array{section_key: string, type: QuestionType}>|null
     */
    private ?array $questionMetaByCode = null;

    public function aggregate(Unit $tenant, NpsDashboardFiltersData $filters): NpsAggregationResult
    {
        $this->bootQuestionMetaLookup();

        $query = SurveyResponse::query()
            ->completed()
            ->where('unit_id', $tenant->getKey());

        if ($filters->surveyBatchId !== null) {
            $query->where('survey_batch_id', $filters->surveyBatchId);
        }

        if ($filters->segmentId !== null) {
            $query->where('segment_id', $filters->segmentId);
        }

        $overall15 = new NpsBuckets;
        $overall010 = new NpsBuckets;

        /** @var array<string, NpsBuckets> $bySection15 */
        $bySection15 = [];
        foreach (['S1', 'S2', 'S3', 'S4', 'S5', 'S6', 'S7', 'S8'] as $sectionKey15) {
            $bySection15[$sectionKey15] = new NpsBuckets;
        }

        $count = 0;

        foreach ($query->lazyById(column: 'id', chunkSize: 100) as $response) {
            $count++;

            /** @var array<string, mixed> $answersRoot */
            $answersRoot = is_array($response->answers) ? $response->answers : [];
            $sections = isset($answersRoot['sections']) && is_array($answersRoot['sections'])
                ? $answersRoot['sections']
                : [];

            foreach ($sections as $sectionKey => $sectionPayload) {
                $sectionPayload = is_array($sectionPayload) ? $sectionPayload : [];
                $this->collectSectionAnswers(
                    (string) $sectionKey,
                    $sectionPayload,
                    $overall15,
                    $overall010,
                    $bySection15,
                    $filters,
                );
            }
        }

        return new NpsAggregationResult(
            responsesCount: $count,
            overallScale15: $overall15,
            overallScale010: $overall010,
            scale15BySection: $bySection15,
        );
    }

    /**
     * @param  array<string, mixed>  $sectionPayload
     * @param  array<string, NpsBuckets>  $bySection15
     */
    private function collectSectionAnswers(
        string $sectionKey,
        array $sectionPayload,
        NpsBuckets $overall15,
        NpsBuckets $overall010,
        array &$bySection15,
        NpsDashboardFiltersData $filters,
    ): void {
        if ($this->isTeachersStructure($sectionPayload)) {
            $this->collectTeachersSectionAnswers(
                $sectionPayload,
                $overall15,
                $overall010,
                $bySection15,
                $filters,
            );

            return;
        }

        $questions = $sectionPayload['questions'] ?? null;
        if (! is_array($questions)) {
            return;
        }

        $sectionBucket = $bySection15[$sectionKey] ?? null;

        foreach ($questions as $code => $entry) {
            $meta = $this->questionMetaByCode[(string) $code] ?? null;
            if ($meta === null) {
                continue;
            }

            $value = null;
            if (is_array($entry) && array_key_exists('value', $entry)) {
                $value = $entry['value'];
            }

            match ($meta['type']) {
                QuestionType::Scale1to5 => $this->applyScale15($overall15, $sectionBucket, $value),
                QuestionType::Scale0to10 => $overall010->tallyScale010($value),
                QuestionType::FreeText => null,
            };
        }
    }

    /**
     * @param  array<string, mixed>  $sectionPayload
     */
    private function collectTeachersSectionAnswers(
        array $sectionPayload,
        NpsBuckets $overall15,
        NpsBuckets $overall010,
        array &$bySection15,
        NpsDashboardFiltersData $filters,
    ): void {
        $teachers = $sectionPayload['teachers'] ?? null;
        if (! is_array($teachers)) {
            return;
        }

        $sectionBucket = $bySection15['S1'] ?? null;

        foreach ($teachers as $teacherId => $teacherPayload) {
            if ($filters->teacherId !== null && $filters->teacherId !== (string) $teacherId) {
                continue;
            }

            $teacherPayload = is_array($teacherPayload) ? $teacherPayload : [];
            $subjectId = isset($teacherPayload['subject_id']) ? (string) $teacherPayload['subject_id'] : null;

            if ($filters->subjectId !== null) {
                if ($subjectId === null || $subjectId !== $filters->subjectId) {
                    continue;
                }
            }

            $questions = $teacherPayload['questions'] ?? null;
            if (! is_array($questions)) {
                continue;
            }

            foreach ($questions as $code => $entry) {
                $meta = $this->questionMetaByCode[(string) $code] ?? null;
                if ($meta === null) {
                    continue;
                }

                $value = null;
                if (is_array($entry) && array_key_exists('value', $entry)) {
                    $value = $entry['value'];
                }

                match ($meta['type']) {
                    QuestionType::Scale1to5 => $this->applyScale15($overall15, $sectionBucket, $value),
                    QuestionType::Scale0to10 => $overall010->tallyScale010($value),
                    QuestionType::FreeText => null,
                };
            }
        }
    }

    /**
     * @param  array<string, mixed>  $sectionPayload
     */
    private function isTeachersStructure(array $sectionPayload): bool
    {
        return isset($sectionPayload['teachers']) && is_array($sectionPayload['teachers']);
    }

    private function applyScale15(NpsBuckets $overall, ?NpsBuckets $sectionBucket, mixed $value): void
    {
        $overall->tallyScale15($value);
        $sectionBucket?->tallyScale15($value);
    }

    /**
     * @return array<string, array{section_key: string, type: QuestionType}>
     */
    private function bootQuestionMetaLookup(): array
    {
        if ($this->questionMetaByCode !== null) {
            return $this->questionMetaByCode;
        }

        /** @var array<string, array{section_key: string, type: QuestionType}> $map */
        $map = [];

        $survey = Survey::query()->active()->first();
        if ($survey === null) {
            return $this->questionMetaByCode = $map;
        }

        $survey->load([
            'surveySections' => fn ($q) => $q->orderBy('sort_order'),
            'surveySections.surveyQuestions' => fn ($q) => $q->orderBy('sort_order'),
        ]);

        foreach ($survey->surveySections as $section) {
            foreach ($section->surveyQuestions as $question) {
                $map[(string) $question->code] = [
                    'section_key' => sprintf('S%d', $section->sort_order),
                    'type' => $question->type,
                ];
            }
        }

        return $this->questionMetaByCode = $map;
    }
}
