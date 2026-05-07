<?php

declare(strict_types=1);

use App\DTOs\NpsDashboardFiltersData;
use App\Models\Enrollment;
use App\Models\Segment;
use App\Models\Student;
use App\Models\Survey;
use App\Models\SurveyBatch;
use App\Models\SurveyResponse;
use App\Models\Unit;
use App\Models\User;
use App\Services\NpsAggregationService;
use Database\Seeders\SurveyTemplateSeeder;
use Database\Seeders\UnitSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([
        UnitSeeder::class,
        UserSeeder::class,
        SurveyTemplateSeeder::class,
    ]);
});

/**
 * @return array{unit: Unit, survey: Survey, batch: SurveyBatch, enrollments: Enrollment[]}
 */
function npsHarness(): array
{
    $unit = Unit::query()->where('slug', 'unidade-norte')->firstOrFail();
    $survey = Survey::query()->firstOrFail();

    $batch = SurveyBatch::factory()->active()->create([
        'unit_id' => $unit->id,
        'survey_id' => $survey->id,
    ]);

    $segment = Segment::factory()->create();
    $students = Student::factory()->count(2)->create();

    $enrollments = [];
    foreach ($students as $student) {
        $enrollments[] = Enrollment::factory()->currentYear($unit, $segment, $student)->create();
    }

    return compact('unit', 'survey', 'batch', 'enrollments');
}

test('aggregation blends scale 15 and recommendation nps respecting nsa exclusions', function (): void {
    ['unit' => $unit, 'batch' => $batch, 'enrollments' => $enrollments] = npsHarness();

    $answersA = [
        'version' => '1.0',
        'sections' => [
            'S2' => ['questions' => ['S2Q1' => ['value' => 5]]],
            'S9' => ['questions' => ['S9NPS' => ['value' => 10]]],
        ],
    ];

    $answersB = [
        'version' => '1.0',
        'sections' => [
            'S2' => ['questions' => ['S2Q1' => ['value' => 3]]],
            'S9' => ['questions' => ['S9NPS' => ['value' => 0]]],
        ],
    ];

    SurveyResponse::factory()->paired($batch, $enrollments[0], $answersA, true)->create();
    SurveyResponse::factory()->paired($batch, $enrollments[1], $answersB, true)->create();

    /** @var NpsAggregationService $aggregator */
    $aggregator = app(NpsAggregationService::class);
    $result = $aggregator->aggregate($unit, new NpsDashboardFiltersData);

    expect($result->responsesCount)->toBe(2)
        ->and($result->overallScale15->nps15())->toEqual(0.0)
        ->and($result->overallScale010->nps010())->toEqual(0.0);

    foreach (SurveyResponse::withTrashed()->cursor() as $existing) {
        $existing->forceDelete();
    }

    $answersNsa = [
        'version' => '1.0',
        'sections' => [
            'S2' => ['questions' => ['S2Q1' => ['value' => 'nsa']]],
            'S9' => ['questions' => ['S9NPS' => ['value' => null]]],
        ],
    ];

    SurveyResponse::factory()->paired($batch, $enrollments[0], $answersNsa, true)->create();

    $isolated = $aggregator->aggregate($unit, new NpsDashboardFiltersData);

    expect($isolated->overallScale15->denominator15())->toBe(0)
        ->and($isolated->overallScale15->nps15())->toBeNull()
        ->and($isolated->overallScale010->denominator010())->toBe(0)
        ->and($isolated->overallScale010->nps010())->toBeNull();
});

test('survey batch filter scopes aggregation to the chosen batch only', function (): void {
    ['unit' => $unit, 'batch' => $batchA, 'enrollments' => $enrollments] = npsHarness();

    $survey = Survey::query()->firstOrFail();
    $batchB = SurveyBatch::factory()->active()->create([
        'unit_id' => $unit->id,
        'survey_id' => $survey->id,
    ]);

    $answers = [
        'version' => '1.0',
        'sections' => [
            'S2' => ['questions' => ['S2Q1' => ['value' => 5]]],
            'S9' => ['questions' => ['S9NPS' => ['value' => 10]]],
        ],
    ];

    SurveyResponse::factory()->paired($batchA, $enrollments[0], $answers, true)->create();

    /** @var NpsAggregationService $aggregator */
    $aggregator = app(NpsAggregationService::class);

    $scoped = $aggregator->aggregate($unit, new NpsDashboardFiltersData(surveyBatchId: $batchB->id));

    expect($scoped->responsesCount)->toBe(0);

    $all = $aggregator->aggregate($unit, new NpsDashboardFiltersData);

    expect($all->responsesCount)->toBe(1);
});

test('operators and admins can render the filament nps dashboard', function (): void {
    /** @var User $operator */
    $operator = User::query()->where('email', 'operador@colegiopense.edu.br')->firstOrFail();
    /** @var User $admin */
    $admin = User::query()->where('email', 'test@example.com')->firstOrFail();

    /** @var Unit $unit */
    $unit = $operator->units()->firstOrFail();

    $tenantParameter = ['tenant' => $unit->slug];

    $this->actingAs($operator)
        ->get(route('filament.app.pages.nps-dashboard', $tenantParameter))
        ->assertSuccessful();

    $this->actingAs($admin)
        ->followingRedirects()
        ->get(route('filament.app.pages.nps-dashboard', $tenantParameter))
        ->assertSuccessful();
});
