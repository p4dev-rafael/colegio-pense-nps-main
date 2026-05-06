<?php

declare(strict_types=1);

use App\Enums\QuestionType;
use App\Enums\SectionType;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveySection;
use App\Models\User;
use Database\Seeders\SurveyTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('survey template seeder creates one survey with nine sections', function (): void {
    $this->seed(SurveyTemplateSeeder::class);

    expect(Survey::query()->count())->toBe(1);
    expect(SurveySection::query()->count())->toBe(9);
});

test('survey template seeder is idempotent', function (): void {
    $this->seed(SurveyTemplateSeeder::class);
    $this->seed(SurveyTemplateSeeder::class);

    expect(Survey::query()->count())->toBe(1);
    expect(SurveySection::query()->count())->toBe(9);
});

test('section question counts match DRF annex C', function (): void {
    $this->seed(SurveyTemplateSeeder::class);

    $expected = [
        SectionType::Teachers->value => 6,
        SectionType::Coordination->value => 6,
        SectionType::Secretariat->value => 6,
        SectionType::PhysicalStructure->value => 11,
        SectionType::Cafeteria->value => 8,
        SectionType::SocialMedia->value => 7,
        SectionType::Chaplaincy->value => 6,
        SectionType::Institutional->value => 3,
        SectionType::NpsFinal->value => 4,
    ];

    foreach ($expected as $type => $count) {
        $section = SurveySection::query()->where('type', $type)->firstOrFail();
        expect($section->surveyQuestions()->count())->toBe($count);
    }
});

test('nps final section has scale 0 to 10 and free text questions', function (): void {
    $this->seed(SurveyTemplateSeeder::class);

    $section = SurveySection::query()
        ->where('type', SectionType::NpsFinal->value)
        ->firstOrFail();

    expect($section->surveyQuestions()->where('code', 'S9NPS')->where('type', QuestionType::Scale0to10->value)->exists())
        ->toBeTrue();

    expect($section->surveyQuestions()->where('type', QuestionType::FreeText->value)->count())
        ->toBe(3);
});

test('question codes are unique across the template', function (): void {
    $this->seed(SurveyTemplateSeeder::class);

    $codes = SurveyQuestion::query()->pluck('code');
    expect($codes->count())->toBe($codes->unique()->count());
});

test('survey factory creates an active survey by default', function (): void {
    $survey = Survey::factory()->create();

    expect($survey->is_active)->toBeTrue();
});

test('survey section factory persists with correct cast', function (): void {
    $section = SurveySection::factory()->teachers()->create();

    expect($section->type)->toBe(SectionType::Teachers);
});

test('survey question factory casts type to enum', function (): void {
    $question = SurveyQuestion::factory()->scale1to5()->create();

    expect($question->type)->toBe(QuestionType::Scale1to5);
});

test('admin user can manage surveys per policy', function (): void {
    $admin = User::factory()->create(['role' => \App\Enums\UserRole::Admin]);

    $survey = Survey::factory()->create();

    expect($admin->can('viewAny', Survey::class))->toBeTrue();
    expect($admin->can('create', Survey::class))->toBeTrue();
    expect($admin->can('update', $survey))->toBeTrue();
    expect($admin->can('delete', $survey))->toBeTrue();
});

test('operator user has read only access to surveys per policy', function (): void {
    $operator = User::factory()->create(['role' => \App\Enums\UserRole::Operator]);

    $survey = Survey::factory()->create();

    expect($operator->can('viewAny', Survey::class))->toBeTrue();
    expect($operator->can('view', $survey))->toBeTrue();
    expect($operator->can('create', Survey::class))->toBeFalse();
    expect($operator->can('update', $survey))->toBeFalse();
    expect($operator->can('delete', $survey))->toBeFalse();
});

test('deleting a survey cascades to sections and questions', function (): void {
    $survey = Survey::factory()->create();
    $section = SurveySection::factory()->for($survey)->create();
    SurveyQuestion::factory()->for($section, 'surveySection')->count(3)->create();

    $survey->forceDelete();

    expect(SurveySection::query()->withTrashed()->count())->toBe(0);
    expect(SurveyQuestion::query()->withTrashed()->count())->toBe(0);
});
