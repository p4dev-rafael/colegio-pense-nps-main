<?php

declare(strict_types=1);

use App\Actions\Survey\CloneSurveyAction;
use App\Enums\QuestionType;
use App\Enums\SectionType;
use App\Enums\UserRole;
use App\Models\Survey;
use App\Models\SurveyBatch;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Models\SurveySection;
use App\Models\User;
use App\Support\Survey\SurveyQuestionCodeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createSurveyWithSections(int $sectionCount = 2, int $questionsPerSection = 3): Survey
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

test('clone survey action deep copies sections and questions', function (): void {
    $source = createSurveyWithSections(sectionCount: 2, questionsPerSection: 3);

    $clone = app(CloneSurveyAction::class)->execute($source, 'Cloned Survey');

    expect($clone->surveySections)->toHaveCount(2);
    expect($clone->surveySections->flatMap->surveyQuestions)->toHaveCount(6);
});

test('clone survey action copies section and question attributes except ids and codes', function (): void {
    $source = createSurveyWithSections(sectionCount: 1, questionsPerSection: 1);

    $sourceSection = $source->surveySections->firstOrFail();
    $sourceQuestion = $sourceSection->surveyQuestions->firstOrFail();

    $clone = app(CloneSurveyAction::class)->execute($source, 'Cloned Survey');

    $clonedSection = $clone->surveySections->firstOrFail();
    $clonedQuestion = $clonedSection->surveyQuestions->firstOrFail();

    expect($clone->description)->toBe($source->description);
    expect($clone->is_active)->toBe($source->is_active);
    expect($clonedSection->survey_id)->toBe($clone->id);
    expect($clonedSection->title)->toBe($sourceSection->title);
    expect($clonedSection->description)->toBe($sourceSection->description);
    expect($clonedSection->type)->toBe($sourceSection->type);
    expect($clonedSection->sort_order)->toBe($sourceSection->sort_order);
    expect($clonedSection->is_active)->toBe($sourceSection->is_active);
    expect($clonedQuestion->text)->toBe($sourceQuestion->text);
    expect($clonedQuestion->type)->toBe($sourceQuestion->type);
    expect($clonedQuestion->is_required)->toBe($sourceQuestion->is_required);
    expect($clonedQuestion->sort_order)->toBe($sourceQuestion->sort_order);
    expect($clonedQuestion->is_active)->toBe($sourceQuestion->is_active);
    expect($clonedQuestion->code)->not->toBe($sourceQuestion->code);
});

test('clone survey action assigns sequential sq codes starting from sq01 when none exist', function (): void {
    $source = createSurveyWithSections(sectionCount: 1, questionsPerSection: 2);

    $clone = app(CloneSurveyAction::class)->execute($source, 'Cloned Survey');

    expect($clone->surveySections->flatMap->surveyQuestions->pluck('code')->all())
        ->toBe(['SQ01', 'SQ02']);
});

test('clone survey action continues sq sequence after existing sq codes', function (): void {
    $existingSection = SurveySection::factory()->create();
    SurveyQuestion::factory()->for($existingSection, 'surveySection')->create(['code' => 'SQ05']);

    $source = createSurveyWithSections(sectionCount: 1, questionsPerSection: 3);

    $clone = app(CloneSurveyAction::class)->execute($source, 'Cloned Survey');

    expect($clone->surveySections->flatMap->surveyQuestions->pluck('code')->all())
        ->toBe(['SQ06', 'SQ07', 'SQ08']);
});

test('clone survey action leaves source survey unchanged', function (): void {
    $source = createSurveyWithSections(sectionCount: 2, questionsPerSection: 2);

    $originalSectionIds = $source->surveySections->pluck('id')->all();
    $originalQuestionIds = $source->surveySections->flatMap->surveyQuestions->pluck('id')->all();
    $originalQuestionCodes = $source->surveySections->flatMap->surveyQuestions->pluck('code')->all();

    app(CloneSurveyAction::class)->execute($source, 'Cloned Survey');

    expect(Survey::query()->count())->toBe(2);
    expect(SurveySection::query()->whereIn('id', $originalSectionIds)->count())->toBe(2);
    expect(SurveyQuestion::query()->whereIn('id', $originalQuestionIds)->pluck('code')->all())
        ->toBe($originalQuestionCodes);
});

test('clone survey action does not copy batches or responses', function (): void {
    $source = createSurveyWithSections(sectionCount: 1, questionsPerSection: 1);

    $batch = SurveyBatch::factory()->for($source)->create();
    SurveyResponse::factory()->anonymous($batch)->create();

    $clone = app(CloneSurveyAction::class)->execute($source, 'Cloned Survey');

    expect(SurveyBatch::query()->count())->toBe(1);
    expect(SurveyResponse::query()->count())->toBe(1);
    expect($clone->surveyBatches)->toHaveCount(0);
});

test('clone survey action persists custom title', function (): void {
    $source = Survey::factory()->create();

    $clone = app(CloneSurveyAction::class)->execute($source, 'Custom Clone Title');

    expect($clone->title)->toBe('Custom Clone Title');
});

test('clone survey action copies is_active from source', function (): void {
    $source = Survey::factory()->inactive()->create();

    $clone = app(CloneSurveyAction::class)->execute($source, 'Inactive Clone');

    expect($clone->is_active)->toBeFalse();
});

test('clone survey action creates empty clone when source has no sections', function (): void {
    $source = Survey::factory()->create();

    $clone = app(CloneSurveyAction::class)->execute($source, 'Empty Clone');

    expect($clone->surveySections)->toHaveCount(0);
});

test('admin user can create surveys per policy for clone authorization', function (): void {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    expect($admin->can('create', Survey::class))->toBeTrue();
});

test('operator user cannot create surveys per policy for clone authorization', function (): void {
    $operator = User::factory()->create(['role' => UserRole::Operator]);

    expect($operator->can('create', Survey::class))->toBeFalse();
});

test('survey question code generator ignores non sq template codes', function (): void {
    $section = SurveySection::factory()->create();
    SurveyQuestion::factory()->for($section, 'surveySection')->create(['code' => 'S1Q1']);
    SurveyQuestion::factory()->for($section, 'surveySection')->create(['code' => 'SQ03']);

    $generator = new SurveyQuestionCodeGenerator;

    expect($generator->nextCode())->toBe('SQ04');
    expect($generator->nextCode())->toBe('SQ05');
});

test('survey question code generator considers soft deleted sq codes', function (): void {
    $section = SurveySection::factory()->create();
    $question = SurveyQuestion::factory()->for($section, 'surveySection')->create(['code' => 'SQ10']);
    $question->delete();

    $generator = new SurveyQuestionCodeGenerator;

    expect($generator->nextCode())->toBe('SQ11');
});
