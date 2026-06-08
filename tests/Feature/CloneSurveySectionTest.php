<?php

declare(strict_types=1);

use App\Actions\Survey\CloneSurveySectionAction;
use App\Enums\QuestionType;
use App\Enums\SectionType;
use App\Enums\UserRole;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveySection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createSurveyWithOrderedSections(int $sectionCount = 2, int $questionsPerSection = 3): Survey
{
    $survey = Survey::factory()->create([
        'title' => 'Source Survey',
        'description' => 'Source description',
        'is_active' => true,
    ]);

    for ($sectionIndex = 1; $sectionIndex <= $sectionCount; $sectionIndex++) {
        $section = SurveySection::factory()->for($survey)->create([
            'title' => "Section {$sectionIndex}",
            'description' => "Section {$sectionIndex} description",
            'type' => SectionType::Teachers->value,
            'sort_order' => $sectionIndex,
            'is_active' => true,
        ]);

        for ($questionIndex = 1; $questionIndex <= $questionsPerSection; $questionIndex++) {
            SurveyQuestion::factory()->for($section, 'surveySection')->create([
                'code' => "S{$sectionIndex}Q{$questionIndex}",
                'text' => "Question {$sectionIndex}.{$questionIndex}",
                'type' => QuestionType::Scale1to5->value,
                'is_required' => true,
                'sort_order' => $questionIndex,
                'is_active' => true,
            ]);
        }
    }

    return $survey->fresh(['surveySections.surveyQuestions']);
}

test('clone survey section action deep copies questions', function (): void {
    $survey = createSurveyWithOrderedSections(sectionCount: 3, questionsPerSection: 2);
    $sourceSection = $survey->surveySections->firstWhere('sort_order', 2);

    $clone = app(CloneSurveySectionAction::class)->execute($sourceSection, 'Cloned Section');

    expect($clone->surveyQuestions)->toHaveCount(2);
    expect($clone->surveyQuestions->pluck('text')->all())
        ->toBe($sourceSection->surveyQuestions->pluck('text')->all());
});

test('clone survey section action copies section and question attributes except ids and codes', function (): void {
    $survey = createSurveyWithOrderedSections(sectionCount: 1, questionsPerSection: 1);
    $sourceSection = $survey->surveySections->firstOrFail();
    $sourceQuestion = $sourceSection->surveyQuestions->firstOrFail();

    $clone = app(CloneSurveySectionAction::class)->execute($sourceSection, 'Cloned Section');
    $clonedQuestion = $clone->surveyQuestions->firstOrFail();

    expect($clone->survey_id)->toBe($sourceSection->survey_id);
    expect($clone->description)->toBe($sourceSection->description);
    expect($clone->type)->toBe($sourceSection->type);
    expect($clone->is_active)->toBe($sourceSection->is_active);
    expect($clonedQuestion->text)->toBe($sourceQuestion->text);
    expect($clonedQuestion->type)->toBe($sourceQuestion->type);
    expect($clonedQuestion->is_required)->toBe($sourceQuestion->is_required);
    expect($clonedQuestion->sort_order)->toBe($sourceQuestion->sort_order);
    expect($clonedQuestion->is_active)->toBe($sourceQuestion->is_active);
    expect($clonedQuestion->code)->not->toBe($sourceQuestion->code);
});

test('clone survey section action inserts clone at origin sort order plus one and shifts later sections', function (): void {
    $survey = createSurveyWithOrderedSections(sectionCount: 5, questionsPerSection: 0);
    $sourceSection = $survey->surveySections->firstWhere('sort_order', 3);

    $clone = app(CloneSurveySectionAction::class)->execute($sourceSection, 'Cloned Section');

    $survey->refresh()->load('surveySections');

    expect($clone->sort_order)->toBe(4);
    expect($survey->surveySections)->toHaveCount(6);
    expect($survey->surveySections->firstWhere('sort_order', 1)?->title)->toBe('Section 1');
    expect($survey->surveySections->firstWhere('sort_order', 2)?->title)->toBe('Section 2');
    expect($survey->surveySections->firstWhere('sort_order', 3)?->id)->toBe($sourceSection->id);
    expect($survey->surveySections->firstWhere('sort_order', 4)?->id)->toBe($clone->id);
    expect($survey->surveySections->firstWhere('sort_order', 5)?->title)->toBe('Section 4');
    expect($survey->surveySections->firstWhere('sort_order', 6)?->title)->toBe('Section 5');
});

test('clone survey section action appends clone when source is the last section', function (): void {
    $survey = createSurveyWithOrderedSections(sectionCount: 5, questionsPerSection: 0);
    $sourceSection = $survey->surveySections->firstWhere('sort_order', 5);

    $clone = app(CloneSurveySectionAction::class)->execute($sourceSection, 'Cloned Section');

    $survey->refresh()->load('surveySections');

    expect($clone->sort_order)->toBe(6);
    expect($survey->surveySections)->toHaveCount(6);
    expect($survey->surveySections->firstWhere('sort_order', 5)?->id)->toBe($sourceSection->id);
    expect($survey->surveySections->firstWhere('sort_order', 6)?->id)->toBe($clone->id);
});

test('clone survey section action assigns fresh sq codes to cloned questions', function (): void {
    $survey = createSurveyWithOrderedSections(sectionCount: 1, questionsPerSection: 2);
    $sourceSection = $survey->surveySections->firstOrFail();

    $clone = app(CloneSurveySectionAction::class)->execute($sourceSection, 'Cloned Section');

    expect($clone->surveyQuestions->pluck('code')->all())->toBe(['SQ01', 'SQ02']);
    expect($sourceSection->fresh()->surveyQuestions->pluck('code')->all())->toBe(['S1Q1', 'S1Q2']);
});

test('clone survey section action leaves source section unchanged', function (): void {
    $survey = createSurveyWithOrderedSections(sectionCount: 3, questionsPerSection: 2);
    $sourceSection = $survey->surveySections->firstWhere('sort_order', 2);
    $originalQuestionIds = $sourceSection->surveyQuestions->pluck('id')->all();
    $originalQuestionCodes = $sourceSection->surveyQuestions->pluck('code')->all();

    app(CloneSurveySectionAction::class)->execute($sourceSection, 'Cloned Section');

    $sourceSection->refresh();

    expect($sourceSection->sort_order)->toBe(2);
    expect(SurveyQuestion::query()->whereIn('id', $originalQuestionIds)->pluck('code')->all())
        ->toBe($originalQuestionCodes);
});

test('clone survey section action persists custom title', function (): void {
    $survey = createSurveyWithOrderedSections(sectionCount: 1, questionsPerSection: 0);
    $sourceSection = $survey->surveySections->firstOrFail();

    $clone = app(CloneSurveySectionAction::class)->execute($sourceSection, 'Custom Clone Title');

    expect($clone->title)->toBe('Custom Clone Title');
});

test('admin user can create survey sections per policy for clone authorization', function (): void {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    expect($admin->can('create', SurveySection::class))->toBeTrue();
});

test('operator user cannot create survey sections per policy for clone authorization', function (): void {
    $operator = User::factory()->create(['role' => UserRole::Operator]);

    expect($operator->can('create', SurveySection::class))->toBeFalse();
});
