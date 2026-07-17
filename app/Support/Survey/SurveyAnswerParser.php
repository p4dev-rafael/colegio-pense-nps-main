<?php

declare(strict_types=1);

namespace App\Support\Survey;

/**
 * Shared helpers for reading persisted survey answer JSON payloads.
 */
final class SurveyAnswerParser
{
    /**
     * @return array<string, mixed>
     */
    public static function answersToArray(mixed $state): array
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

    /**
     * @param  array<string, mixed>  $answersRoot
     * @return array<string, mixed>
     */
    public static function extractSectionsPayload(array $answersRoot): array
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
     * @param  array<string, mixed>  $payloadQuestions
     */
    public static function extractRawValue(array $payloadQuestions, string $code): mixed
    {
        if (! array_key_exists($code, $payloadQuestions)) {
            return null;
        }

        $payload = $payloadQuestions[$code];

        return is_array($payload) ? ($payload['value'] ?? null) : $payload;
    }

    public static function formatDisplayValue(mixed $value): string
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

    public static function isNumericScore(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        if (is_string($value) && strcasecmp($value, 'nsa') === 0) {
            return false;
        }

        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    public static function numericScore(mixed $value): ?float
    {
        if (! self::isNumericScore($value)) {
            return null;
        }

        return (float) filter_var($value, FILTER_VALIDATE_INT);
    }

    /**
     * @param  array<string, mixed>  $teachersPayload
     * @return list<array<string, mixed>>
     */
    public static function normalizeTeacherEntries(array $teachersPayload): array
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

        return $entries;
    }
}
