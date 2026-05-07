<?php

declare(strict_types=1);

use App\Models\Enrollment;
use App\Models\Segment;
use App\Models\Student;
use App\Models\Survey;
use App\Models\SurveyBatch;
use App\Models\SurveyResponse;
use App\Models\Unit;
use App\Support\Survey\SurveyResponseAnswersDisplay;
use Database\Seeders\SurveyTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(SurveyTemplateSeeder::class);
});

test('structured answers display uses section titles and question texts', function (): void {
    $unit = Unit::factory()->create();
    $survey = Survey::query()->firstOrFail();

    $student = Student::factory()->create();
    $segment = Segment::factory()->create();

    $enrollment = Enrollment::factory()->currentYear($unit, $segment, $student)->create();

    $batch = SurveyBatch::factory()->active()->create([
        'unit_id' => $unit->id,
        'survey_id' => $survey->id,
    ]);

    $answers = [
        'version' => '1.0',
        'sections' => [
            'S2' => ['questions' => ['S2Q1' => ['value' => 4]]],
        ],
    ];

    $response = SurveyResponse::factory()->paired($batch, $enrollment, $answers, true)->create();
    $html = SurveyResponseAnswersDisplay::toHtml($response->fresh())->toHtml();

    expect($html)->toContain('Coordenação')
        ->and($html)->toContain('Acessibilidade da coordenação')
        ->and($html)->toContain('4');
});

test('answers display falls back to json when survey template is unavailable', function (): void {
    $unit = Unit::factory()->create();
    $survey = Survey::query()->firstOrFail();

    $batch = SurveyBatch::factory()->active()->create([
        'unit_id' => $unit->id,
        'survey_id' => $survey->id,
    ]);

    $survey->delete();

    $student = Student::factory()->create();
    $segment = Segment::factory()->create();
    $enrollment = Enrollment::factory()->currentYear($unit, $segment, $student)->create();

    $answers = [
        'version' => '1.0',
        'sections' => [
            'S2' => ['questions' => ['S2Q1' => ['value' => 4]]],
        ],
    ];

    $response = SurveyResponse::factory()->paired($batch, $enrollment, $answers, true)->create();
    $html = SurveyResponseAnswersDisplay::toHtml($response->fresh())->toHtml();

    expect($html)->toContain(__('survey_responses.display.survey_unavailable'))
        ->and($html)->toContain('S2Q1');
});

test('nsa scale answers use the translated survey label', function (): void {
    $unit = Unit::factory()->create();
    $survey = Survey::query()->firstOrFail();

    $student = Student::factory()->create();
    $segment = Segment::factory()->create();

    $enrollment = Enrollment::factory()->currentYear($unit, $segment, $student)->create();

    $batch = SurveyBatch::factory()->active()->create([
        'unit_id' => $unit->id,
        'survey_id' => $survey->id,
    ]);

    $answers = [
        'version' => '1.0',
        'sections' => [
            'S2' => ['questions' => ['S2Q1' => ['value' => 'nsa']]],
        ],
    ];

    $response = SurveyResponse::factory()->paired($batch, $enrollment, $answers, true)->create();
    $html = SurveyResponseAnswersDisplay::toHtml($response->fresh())->toHtml();

    expect($html)->toContain(__('survey.public.form.nsa_option'));
});
