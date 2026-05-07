<?php

declare(strict_types=1);

namespace App\Support\Survey;

use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Models\SurveySection;
use Illuminate\Support\HtmlString;

/**
 * Builds a readable HTML representation of persisted survey answers using the batch survey template.
 */
final class SurveyResponseAnswersDisplay
{
    public static function toHtml(SurveyResponse $response): HtmlString
    {
        $response->loadMissing([
            'surveyBatch.survey.surveySections' => fn ($q) => $q
                ->where('is_active', true)
                ->orderBy('sort_order'),
            'surveyBatch.survey.surveySections.surveyQuestions' => fn ($q) => $q
                ->where('is_active', true)
                ->orderBy('sort_order'),
        ]);

        $survey = $response->surveyBatch?->survey;
        if ($survey === null) {
            return new HtmlString(self::fallbackPreformattedJson($response->answers));
        }

        $sectionsPayload = self::extractSectionsPayload(self::answersToArray($response->answers));

        if ($sectionsPayload === []) {
            return new HtmlString(
                '<p class="text-sm text-gray-500 dark:text-gray-400">'.e(__('survey_responses.display.no_answers')).'</p>'
            );
        }

        $knownKeys = [];
        foreach ($survey->surveySections as $section) {
            $knownKeys[sprintf('S%d', $section->sort_order)] = true;
        }

        $html = '<div class="space-y-6 text-sm">';

        foreach ($survey->surveySections as $section) {
            $sectionKey = sprintf('S%d', $section->sort_order);
            if (! isset($sectionsPayload[$sectionKey]) || ! is_array($sectionsPayload[$sectionKey])) {
                continue;
            }

            $html .= self::renderTemplateSection($section, $sectionsPayload[$sectionKey]);
        }

        foreach ($sectionsPayload as $rawKey => $sectionData) {
            if (! is_string($rawKey) || isset($knownKeys[$rawKey]) || ! is_array($sectionData)) {
                continue;
            }

            $html .= self::renderUnknownSectionKey($rawKey, $sectionData);
        }

        $html .= '</div>';

        return new HtmlString($html);
    }

    private static function renderTemplateSection(SurveySection $section, array $sectionData): string
    {
        $heading = e($section->title);
        $html = '<section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900/40">';
        $html .= '<h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">'.$heading.'</h3>';

        if (filled($section->description)) {
            $html .= '<p class="mt-1 text-xs leading-5 text-gray-600 dark:text-gray-400">'.e($section->description).'</p>';
        }

        if (isset($sectionData['teachers']) && is_array($sectionData['teachers'])) {
            $html .= self::renderTeachersBlocks($section, $sectionData['teachers']);
        } elseif (isset($sectionData['questions']) && is_array($sectionData['questions'])) {
            $html .= self::renderQuestionsDefinitionList($section->surveyQuestions, $sectionData['questions'], false);
        }

        $html .= '</section>';

        return $html;
    }

    /**
     * @param  iterable<int, SurveyQuestion>  $templateQuestions
     * @param  array<string, mixed>  $payloadQuestions
     */
    private static function renderQuestionsDefinitionList(iterable $templateQuestions, array $payloadQuestions, bool $compact): string
    {
        $marginClass = $compact ? 'mt-3 space-y-3' : 'mt-4 space-y-3';

        $html = '<dl class="'.$marginClass.'">';
        $seenCodes = [];

        foreach ($templateQuestions as $question) {
            $seenCodes[$question->code] = true;
            $html .= self::renderQuestionRow($question->text, self::normalizedAnswerValue($payloadQuestions, $question->code));
        }

        foreach ($payloadQuestions as $code => $_) {
            if (! is_string($code) || isset($seenCodes[$code])) {
                continue;
            }

            $html .= self::renderQuestionRow($code, self::normalizedAnswerValue($payloadQuestions, $code));
        }

        $html .= '</dl>';

        return $html;
    }

    private static function renderQuestionRow(string $label, string $displayValue): string
    {
        return '<div class="grid gap-1 border-b border-gray-100 pb-3 last:border-0 last:pb-0 dark:border-white/5 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-start sm:gap-4">'
            .'<dt class="text-gray-600 dark:text-gray-400">'.e($label).'</dt>'
            .'<dd class="font-medium text-gray-950 dark:text-white sm:text-right">'.e($displayValue).'</dd>'
            .'</div>';
    }

    /**
     * @param  array<string, mixed>  $teachersPayload
     */
    private static function renderTeachersBlocks(SurveySection $section, array $teachersPayload): string
    {
        $entries = [];
        foreach ($teachersPayload as $entry) {
            if (is_array($entry)) {
                $entries[] = $entry;
            }
        }

        usort(
            $entries,
            fn (array $a, array $b): int => strcmp(
                (string) ($a['teacher_name'] ?? ''),
                (string) ($b['teacher_name'] ?? ''),
            ),
        );

        $html = '';
        foreach ($entries as $entry) {
            $name = (string) ($entry['teacher_name'] ?? '—');
            $html .= '<div class="mt-4 rounded-lg bg-gray-50 p-3 dark:bg-white/5">';
            $html .= '<h4 class="font-medium text-gray-900 dark:text-white">'.e($name).'</h4>';

            $questions = isset($entry['questions']) && is_array($entry['questions']) ? $entry['questions'] : [];
            $html .= self::renderQuestionsDefinitionList($section->surveyQuestions, $questions, true);
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * @param  array<string, mixed>  $sectionData
     */
    private static function renderUnknownSectionKey(string $sectionKey, array $sectionData): string
    {
        $title = e(__('survey_responses.display.unknown_section', ['code' => $sectionKey]));
        $html = '<section class="rounded-xl border border-dashed border-amber-200 bg-amber-50/50 p-4 dark:border-amber-500/30 dark:bg-amber-500/10">';
        $html .= '<h3 class="text-base font-semibold text-amber-950 dark:text-amber-100">'.$title.'</h3>';

        if (isset($sectionData['questions']) && is_array($sectionData['questions'])) {
            $html .= self::renderQuestionsDefinitionList([], $sectionData['questions'], false);
        } elseif (isset($sectionData['teachers']) && is_array($sectionData['teachers'])) {
            $html .= self::renderUnknownTeachersBlocks($sectionData['teachers']);
        }

        $html .= '</section>';

        return $html;
    }

    /**
     * @param  array<string, mixed>  $teachersPayload
     */
    private static function renderUnknownTeachersBlocks(array $teachersPayload): string
    {
        $entries = [];
        foreach ($teachersPayload as $entry) {
            if (is_array($entry)) {
                $entries[] = $entry;
            }
        }

        usort(
            $entries,
            fn (array $a, array $b): int => strcmp(
                (string) ($a['teacher_name'] ?? ''),
                (string) ($b['teacher_name'] ?? ''),
            ),
        );

        $html = '';
        foreach ($entries as $entry) {
            $name = (string) ($entry['teacher_name'] ?? '—');
            $html .= '<div class="mt-3 rounded-lg bg-white/60 p-3 dark:bg-black/20">';
            $html .= '<h4 class="font-medium text-amber-950 dark:text-amber-50">'.e($name).'</h4>';
            $questions = isset($entry['questions']) && is_array($entry['questions']) ? $entry['questions'] : [];
            $html .= self::renderQuestionsDefinitionList([], $questions, true);
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * @param  array<string, mixed>  $payloadQuestions
     */
    private static function normalizedAnswerValue(array $payloadQuestions, string $code): string
    {
        if (! array_key_exists($code, $payloadQuestions)) {
            return '—';
        }

        $payload = $payloadQuestions[$code];
        $raw = is_array($payload) ? ($payload['value'] ?? null) : $payload;

        return self::formatAnswerValue($raw);
    }

    private static function formatAnswerValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_string($value) && strcasecmp($value, 'nsa') === 0) {
            return __('survey.public.form.nsa_option');
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE);

        return ($encoded !== false && $encoded !== '') ? $encoded : '—';
    }

    /**
     * @param  array<string, mixed>  $answersRoot
     * @return array<string, mixed>
     */
    private static function extractSectionsPayload(array $answersRoot): array
    {
        if (isset($answersRoot['sections']) && is_array($answersRoot['sections'])) {
            return $answersRoot['sections'];
        }

        if (isset($answersRoot['version'])) {
            return [];
        }

        return $answersRoot;
    }

    /**
     * @return array<string, mixed>
     */
    private static function answersToArray(mixed $state): array
    {
        if ($state === null) {
            return [];
        }

        if (is_array($state)) {
            return $state;
        }

        if (is_string($state) && $state !== '') {
            $decoded = json_decode($state, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    private static function fallbackPreformattedJson(mixed $answers): string
    {
        $encoded = json_encode(self::answersToArray($answers), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '—';
        $intro = e(__('survey_responses.display.survey_unavailable'));

        return '<div class="space-y-2">'
            .'<p class="text-sm text-gray-600 dark:text-gray-400">'.$intro.'</p>'
            .'<pre class="overflow-x-auto rounded-lg bg-gray-50 p-3 text-xs dark:bg-black/20">'.e($encoded).'</pre>'
            .'</div>';
    }
}
