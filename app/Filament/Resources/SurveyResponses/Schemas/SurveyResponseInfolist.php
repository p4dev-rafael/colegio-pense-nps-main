<?php

declare(strict_types=1);

namespace App\Filament\Resources\SurveyResponses\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;

final class SurveyResponseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('survey_responses.sections.identification'))
                    ->schema([
                        TextEntry::make('surveyBatch.title')
                            ->label(__('survey_responses.fields.survey_batch_id')),
                        TextEntry::make('respondent_name')
                            ->label(__('survey_responses.fields.respondent_name')),
                        TextEntry::make('respondent_type')
                            ->label(__('survey_responses.fields.respondent_type'))
                            ->badge(),
                        TextEntry::make('enrollment.registration_code')
                            ->label(__('survey_responses.fields.registration_code'))
                            ->placeholder('—'),
                        TextEntry::make('segment.name')
                            ->label(__('survey_responses.fields.segment_id'))
                            ->placeholder('—'),
                        TextEntry::make('is_completed')
                            ->label(__('survey_responses.fields.is_completed'))
                            ->boolean(),
                        TextEntry::make('completed_at')
                            ->label(__('survey_responses.fields.completed_at'))
                            ->dateTime(),
                    ])
                    ->columns(3),
                Section::make(__('survey_responses.sections.answers'))
                    ->schema([
                        TextEntry::make('answers')
                            ->label(__('survey_responses.fields.answers'))
                            ->columnSpanFull()
                            ->formatStateUsing(function (?array $state): string {
                                $flat = is_array($state) ? self::flattenAnswers($state) : [];

                                return json_encode($flat, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '—';
                            }),
                    ]),
                Section::make(__('survey_responses.sections.audit'))
                    ->schema([
                        TextEntry::make('ip_address')
                            ->label(__('survey_responses.fields.ip_address')),
                        TextEntry::make('user_agent')
                            ->label(__('survey_responses.fields.user_agent'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }

    /**
     * Flattens the nested answers JSON to a key-value structure suitable for display.
     *
     * @param  array<string, mixed>  $answers
     * @return array<string, string>
     */
    private static function flattenAnswers(array $answers): array
    {
        $flat = [];
        $sections = Arr::get($answers, 'sections', []);

        foreach ($sections as $sectionCode => $sectionData) {
            if (isset($sectionData['questions']) && is_array($sectionData['questions'])) {
                foreach ($sectionData['questions'] as $code => $payload) {
                    $value = is_array($payload) ? ($payload['value'] ?? null) : $payload;
                    $flat[$code] = self::stringifyValue($value);
                }
            }

            if (isset($sectionData['teachers']) && is_array($sectionData['teachers'])) {
                foreach ($sectionData['teachers'] as $teacherEntry) {
                    $teacherName = $teacherEntry['teacher_name'] ?? '—';
                    foreach ($teacherEntry['questions'] ?? [] as $code => $payload) {
                        $value = is_array($payload) ? ($payload['value'] ?? null) : $payload;
                        $flat[sprintf('%s · %s', $teacherName, $code)] = self::stringifyValue($value);
                    }
                }
            }
        }

        return $flat;
    }

    private static function stringifyValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE);

        return ($encoded !== false && $encoded !== '') ? $encoded : '—';
    }
}
