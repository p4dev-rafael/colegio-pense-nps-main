<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\NpsDashboardFiltersData;
use App\Enums\QuestionType;
use App\Enums\RespondentType;
use App\Models\SurveyBatch;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Models\SurveySection;
use App\Support\Survey\Pdf\SurveyBatchPdfFreeTextEntry;
use App\Support\Survey\Pdf\SurveyBatchPdfQuestionAverage;
use App\Support\Survey\Pdf\SurveyBatchPdfReportData;
use App\Support\Survey\Pdf\SurveyBatchPdfResponseEntry;
use App\Support\Survey\Pdf\SurveyBatchPdfResponseQuestion;
use App\Support\Survey\Pdf\SurveyBatchPdfResponseSection;
use App\Support\Survey\Pdf\SurveyBatchPdfResponseTeacher;
use App\Support\Survey\Pdf\SurveyBatchPdfTeacherAverage;
use App\Support\Survey\SurveyAnswerParser;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class SurveyBatchPdfReportBuilder
{
    public function __construct(private readonly NpsAggregationService $aggregationService) {}

    public function build(SurveyBatch $batch): SurveyBatchPdfReportData
    {
        $batch->loadMissing([
            'unit',
            'survey.surveySections' => fn ($query) => $query
                ->where('is_active', true)
                ->orderBy('sort_order'),
            'survey.surveySections.surveyQuestions' => fn ($query) => $query
                ->where('is_active', true)
                ->orderBy('sort_order'),
        ]);

        $unit = $batch->unit;
        $survey = $batch->survey;

        $aggregation = $this->aggregationService->aggregate(
            $unit,
            new NpsDashboardFiltersData(surveyBatchId: $batch->id),
        );

        $sectionTitleByKey = [];
        if ($survey !== null) {
            foreach ($survey->surveySections as $section) {
                $sectionTitleByKey[sprintf('S%d', $section->sort_order)] = $section->title;
            }
        }

        $sectionSummaries = $aggregation->sectionSummaries(
            fn (string $key): string => $sectionTitleByKey[$key] ?? $key,
        );

        /** @var Collection<int, SurveyResponse> $responses */
        $responses = SurveyResponse::query()
            ->where('survey_batch_id', $batch->id)
            ->completed()
            ->with(['segment', 'enrollment'])
            ->orderBy('completed_at')
            ->orderBy('id')
            ->get();

        /** @var array<string, array{sum: float, count: int, section_title: string, question_text: string}> $questionAccumulator */
        $questionAccumulator = [];

        /** @var array<string, array{sum: float, count: int, teacher_name: string, question_text: string, question_code: string}> $teacherAccumulator */
        $teacherAccumulator = [];

        /** @var list<SurveyBatchPdfFreeTextEntry> $freeTextComments */
        $freeTextComments = [];

        /** @var list<SurveyBatchPdfResponseEntry> $responseEntries */
        $responseEntries = [];

        foreach ($responses as $response) {
            $respondentLabel = $this->resolveRespondentLabel($response);
            $sectionsPayload = SurveyAnswerParser::extractSectionsPayload(
                SurveyAnswerParser::answersToArray($response->answers),
            );

            if ($survey !== null) {
                $this->collectAggregates(
                    $survey->surveySections,
                    $sectionsPayload,
                    $respondentLabel,
                    $questionAccumulator,
                    $teacherAccumulator,
                    $freeTextComments,
                );

                $responseEntries[] = $this->buildResponseEntry($response, $survey->surveySections, $sectionsPayload);
            }
        }

        return new SurveyBatchPdfReportData(
            batch: $batch,
            aggregation: $aggregation,
            sectionSummaries: $sectionSummaries,
            questionAverages: $this->mapQuestionAverages($questionAccumulator),
            teacherAverages: $this->mapTeacherAverages($teacherAccumulator),
            freeTextComments: $freeTextComments,
            responses: $responseEntries,
            generatedAt: Carbon::now()->translatedFormat('d/m/Y H:i'),
        );
    }

    /**
     * @param  iterable<int, SurveySection>  $sections
     * @param  array<string, mixed>  $sectionsPayload
     * @param  array<string, array{sum: float, count: int, section_title: string, question_text: string}>  $questionAccumulator
     * @param  array<string, array{sum: float, count: int, teacher_name: string, question_text: string, question_code: string}>  $teacherAccumulator
     * @param  list<SurveyBatchPdfFreeTextEntry>  $freeTextComments
     */
    private function collectAggregates(
        iterable $sections,
        array $sectionsPayload,
        string $respondentLabel,
        array &$questionAccumulator,
        array &$teacherAccumulator,
        array &$freeTextComments,
    ): void {
        foreach ($sections as $section) {
            $sectionKey = sprintf('S%d', $section->sort_order);
            $sectionData = $sectionsPayload[$sectionKey] ?? null;

            if (! is_array($sectionData)) {
                continue;
            }

            if (isset($sectionData['teachers']) && is_array($sectionData['teachers'])) {
                $this->collectTeacherAggregates($section, $sectionData['teachers'], $teacherAccumulator);

                continue;
            }

            $questionsPayload = $sectionData['questions'] ?? null;
            if (! is_array($questionsPayload)) {
                continue;
            }

            $this->collectQuestionAggregates(
                $section,
                $questionsPayload,
                $respondentLabel,
                $questionAccumulator,
                $freeTextComments,
            );
        }
    }

    /**
     * @param  array<string, mixed>  $questionsPayload
     * @param  array<string, array{sum: float, count: int, section_title: string, question_text: string}>  $questionAccumulator
     * @param  list<SurveyBatchPdfFreeTextEntry>  $freeTextComments
     */
    private function collectQuestionAggregates(
        SurveySection $section,
        array $questionsPayload,
        string $respondentLabel,
        array &$questionAccumulator,
        array &$freeTextComments,
    ): void {
        foreach ($section->surveyQuestions as $question) {
            $raw = SurveyAnswerParser::extractRawValue($questionsPayload, $question->code);

            if ($question->type === QuestionType::FreeText) {
                if (is_string($raw) && filled(trim($raw))) {
                    $freeTextComments[] = new SurveyBatchPdfFreeTextEntry(
                        questionText: $question->text,
                        questionCode: $question->code,
                        text: trim($raw),
                        respondentLabel: $respondentLabel,
                    );
                }

                continue;
            }

            $score = SurveyAnswerParser::numericScore($raw);
            if ($score === null) {
                continue;
            }

            $key = $question->code;
            if (! isset($questionAccumulator[$key])) {
                $questionAccumulator[$key] = [
                    'sum' => 0.0,
                    'count' => 0,
                    'section_title' => $section->title,
                    'question_text' => $question->text,
                ];
            }

            $questionAccumulator[$key]['sum'] += $score;
            $questionAccumulator[$key]['count']++;
        }
    }

    /**
     * @param  array<string, mixed>  $teachersPayload
     * @param  array<string, array{sum: float, count: int, teacher_name: string, question_text: string, question_code: string}>  $teacherAccumulator
     */
    private function collectTeacherAggregates(
        SurveySection $section,
        array $teachersPayload,
        array &$teacherAccumulator,
    ): void {
        foreach (SurveyAnswerParser::normalizeTeacherEntries($teachersPayload) as $entry) {
            $teacherName = (string) ($entry['teacher_name'] ?? '—');
            $questionsPayload = isset($entry['questions']) && is_array($entry['questions'])
                ? $entry['questions']
                : [];

            foreach ($section->surveyQuestions as $question) {
                if ($question->type !== QuestionType::Scale1to5) {
                    continue;
                }

                $score = SurveyAnswerParser::numericScore(
                    SurveyAnswerParser::extractRawValue($questionsPayload, $question->code),
                );

                if ($score === null) {
                    continue;
                }

                $key = $teacherName.'::'.$question->code;
                if (! isset($teacherAccumulator[$key])) {
                    $teacherAccumulator[$key] = [
                        'sum' => 0.0,
                        'count' => 0,
                        'teacher_name' => $teacherName,
                        'question_text' => $question->text,
                        'question_code' => $question->code,
                    ];
                }

                $teacherAccumulator[$key]['sum'] += $score;
                $teacherAccumulator[$key]['count']++;
            }
        }
    }

    /**
     * @param  iterable<int, SurveySection>  $sections
     * @param  array<string, mixed>  $sectionsPayload
     */
    private function buildResponseEntry(
        SurveyResponse $response,
        iterable $sections,
        array $sectionsPayload,
    ): SurveyBatchPdfResponseEntry {
        /** @var list<SurveyBatchPdfResponseSection> $responseSections */
        $responseSections = [];

        foreach ($sections as $section) {
            $sectionKey = sprintf('S%d', $section->sort_order);
            $sectionData = $sectionsPayload[$sectionKey] ?? null;

            if (! is_array($sectionData)) {
                continue;
            }

            $questions = [];
            $teachers = [];

            if (isset($sectionData['teachers']) && is_array($sectionData['teachers'])) {
                foreach (SurveyAnswerParser::normalizeTeacherEntries($sectionData['teachers']) as $entry) {
                    $teacherQuestionsPayload = isset($entry['questions']) && is_array($entry['questions'])
                        ? $entry['questions']
                        : [];

                    $teachers[] = new SurveyBatchPdfResponseTeacher(
                        name: (string) ($entry['teacher_name'] ?? '—'),
                        questions: $this->mapResponseQuestions($section->surveyQuestions, $teacherQuestionsPayload),
                    );
                }
            } elseif (isset($sectionData['questions']) && is_array($sectionData['questions'])) {
                $questions = $this->mapResponseQuestions($section->surveyQuestions, $sectionData['questions']);
            }

            if ($questions === [] && $teachers === []) {
                continue;
            }

            $responseSections[] = new SurveyBatchPdfResponseSection(
                title: $section->title,
                description: $section->description,
                questions: $questions,
                teachers: $teachers,
            );
        }

        return new SurveyBatchPdfResponseEntry(
            id: $response->id,
            respondentLabel: $this->resolveRespondentLabel($response),
            respondentTypeLabel: $response->respondent_type?->getLabel(),
            segmentName: $response->segment?->name,
            registrationCode: $response->enrollment?->registration_code,
            completedAt: $response->completed_at?->translatedFormat('d/m/Y H:i'),
            sections: $responseSections,
        );
    }

    /**
     * @param  iterable<int, SurveyQuestion>  $templateQuestions
     * @param  array<string, mixed>  $payloadQuestions
     * @return list<SurveyBatchPdfResponseQuestion>
     */
    private function mapResponseQuestions(iterable $templateQuestions, array $payloadQuestions): array
    {
        $mapped = [];
        $seenCodes = [];

        foreach ($templateQuestions as $question) {
            $seenCodes[$question->code] = true;
            $raw = SurveyAnswerParser::extractRawValue($payloadQuestions, $question->code);

            if ($raw === null && $question->type !== QuestionType::FreeText) {
                continue;
            }

            $mapped[] = new SurveyBatchPdfResponseQuestion(
                label: $question->text,
                value: SurveyAnswerParser::formatDisplayValue($raw),
                isFreeText: $question->type === QuestionType::FreeText,
            );
        }

        foreach ($payloadQuestions as $code => $_) {
            if (! is_string($code) || isset($seenCodes[$code])) {
                continue;
            }

            $mapped[] = new SurveyBatchPdfResponseQuestion(
                label: $code,
                value: SurveyAnswerParser::formatDisplayValue(
                    SurveyAnswerParser::extractRawValue($payloadQuestions, $code),
                ),
                isFreeText: false,
            );
        }

        return $mapped;
    }

    /**
     * @param  array<string, array{sum: float, count: int, section_title: string, question_text: string}>  $questionAccumulator
     * @return list<SurveyBatchPdfQuestionAverage>
     */
    private function mapQuestionAverages(array $questionAccumulator): array
    {
        $rows = [];

        foreach ($questionAccumulator as $code => $data) {
            $rows[] = new SurveyBatchPdfQuestionAverage(
                sectionTitle: $data['section_title'],
                questionText: $data['question_text'],
                questionCode: $code,
                average: $data['count'] > 0 ? round($data['sum'] / $data['count'], 2) : null,
                count: $data['count'],
            );
        }

        usort($rows, fn (SurveyBatchPdfQuestionAverage $a, SurveyBatchPdfQuestionAverage $b): int => strcmp(
            $a->sectionTitle.$a->questionText,
            $b->sectionTitle.$b->questionText,
        ));

        return $rows;
    }

    /**
     * @param  array<string, array{sum: float, count: int, teacher_name: string, question_text: string, question_code: string}>  $teacherAccumulator
     * @return list<SurveyBatchPdfTeacherAverage>
     */
    private function mapTeacherAverages(array $teacherAccumulator): array
    {
        $rows = [];

        foreach ($teacherAccumulator as $data) {
            $rows[] = new SurveyBatchPdfTeacherAverage(
                teacherName: $data['teacher_name'],
                questionText: $data['question_text'],
                questionCode: $data['question_code'],
                average: $data['count'] > 0 ? round($data['sum'] / $data['count'], 2) : null,
                count: $data['count'],
            );
        }

        usort($rows, fn (SurveyBatchPdfTeacherAverage $a, SurveyBatchPdfTeacherAverage $b): int => strcmp(
            $a->teacherName.$a->questionText,
            $b->teacherName.$b->questionText,
        ));

        return $rows;
    }

    private function resolveRespondentLabel(SurveyResponse $response): string
    {
        if ($response->respondent_type === RespondentType::Anonymous) {
            return __('survey_batches.pdf.anonymous');
        }

        if (filled($response->respondent_name)) {
            return $response->respondent_name;
        }

        return __('survey_batches.pdf.anonymous');
    }
}
