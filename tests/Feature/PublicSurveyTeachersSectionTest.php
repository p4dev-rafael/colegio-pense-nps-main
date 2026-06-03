<?php

declare(strict_types=1);

use App\DTOs\NpsDashboardFiltersData;
use App\Enums\SegmentGroup;
use App\Livewire\Survey\PublicSurvey;
use App\Models\Enrollment;
use App\Models\Segment;
use App\Models\SegmentTeacher;
use App\Models\Student;
use App\Models\Survey;
use App\Models\SurveyBatch;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Models\Teacher;
use App\Models\Unit;
use App\Services\NpsAggregationService;
use Database\Seeders\SurveyTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(SurveyTemplateSeeder::class);
});

/**
 * @return array{unit: Unit, survey: Survey, batch: SurveyBatch, token: string}
 */
function optionalIdentificationBatch(): array
{
    $unit = Unit::factory()->create();
    $survey = Survey::query()->firstOrFail();
    $token = 'optional-identification-batch-token-'.fake()->uuid();

    $batch = SurveyBatch::factory()
        ->active()
        ->withoutRequiredIdentification()
        ->withPublicToken($token)
        ->create([
            'unit_id' => $unit->id,
            'survey_id' => $survey->id,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'activated_at' => now(),
        ]);

    return compact('unit', 'survey', 'batch', 'token');
}

test('public survey renders flat professores section when identification is not required', function (): void {
    ['token' => $token] = optionalIdentificationBatch();

    $s1QuestionText = SurveyQuestion::query()
        ->where('code', 'S1Q1')
        ->value('text');

    expect($s1QuestionText)->not->toBeNull();

    Livewire::test(PublicSurvey::class, ['token' => $token])
        ->call('identify')
        ->assertSet('identified', true)
        ->assertSet('teacherSlots', [])
        ->assertSee($s1QuestionText)
        ->assertSeeHtml('sectionAnswers.S1.S1Q1');
});

test('public survey persists flat s1 answers for anonymous respondents', function (): void {
    ['unit' => $unit, 'batch' => $batch, 'token' => $token] = optionalIdentificationBatch();

    $component = Livewire::test(PublicSurvey::class, ['token' => $token])
        ->call('identify');

    foreach ($component->get('sectionAnswers') as $sectionKey => $questions) {
        foreach (array_keys($questions) as $code) {
            $value = $code === 'S9NPS' ? '9' : '5';
            $component->set("sectionAnswers.{$sectionKey}.{$code}", $value);
        }
    }

    $component->call('submit')
        ->assertSet('submitted', true);

    $response = SurveyResponse::query()->where('survey_batch_id', $batch->id)->first();

    expect($response)->not->toBeNull()
        ->and($response->enrollment_id)->toBeNull()
        ->and($response->answers['sections']['S1']['questions']['S1Q1']['value'])->toBe(5)
        ->and($response->answers['sections']['S1'])->not->toHaveKey('teachers');
});

test('optional registration code still uses flat professores section', function (): void {
    ['unit' => $unit, 'batch' => $batch, 'token' => $token] = optionalIdentificationBatch();

    $student = Student::factory()->create(['name' => 'Aluno Teste']);
    $segment = Segment::factory()->forGroup(SegmentGroup::Ef2)->create();
    $enrollment = Enrollment::factory()->currentYear($unit, $segment, $student)->create([
        'registration_code' => 'MAT123456',
    ]);

    $teacher = Teacher::factory()->create(['name' => 'Prof. Segmento', 'is_active' => true]);
    $unit->teachers()->attach($teacher->id);
    SegmentTeacher::factory()->withSubject()->create([
        'unit_id' => $unit->id,
        'segment_id' => $segment->id,
        'teacher_id' => $teacher->id,
    ]);

    Livewire::test(PublicSurvey::class, ['token' => $token])
        ->set('registrationCode', $enrollment->registration_code)
        ->call('identify')
        ->assertSet('identified', true)
        ->assertSet('enrollmentId', $enrollment->id)
        ->assertSet('teacherSlots', [])
        ->assertDontSee('Prof. Segmento')
        ->assertSeeHtml('sectionAnswers.S1.S1Q1');
});

test('identified batch renders per-teacher professores section', function (): void {
    $unit = Unit::factory()->create();
    $survey = Survey::query()->firstOrFail();
    $token = 'identified-batch-token-'.fake()->uuid();

    $batch = SurveyBatch::factory()
        ->active()
        ->withPublicToken($token)
        ->create([
            'unit_id' => $unit->id,
            'survey_id' => $survey->id,
            'requires_identification' => true,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'activated_at' => now(),
        ]);

    $student = Student::factory()->create();
    $segment = Segment::factory()->forGroup(SegmentGroup::Ef2)->create();
    $enrollment = Enrollment::factory()->currentYear($unit, $segment, $student)->create([
        'registration_code' => 'MAT999888',
    ]);

    $teacher = Teacher::factory()->create(['name' => 'Prof. Identificado', 'is_active' => true]);
    $unit->teachers()->attach($teacher->id);
    $segmentTeacher = SegmentTeacher::factory()->withSubject()->create([
        'unit_id' => $unit->id,
        'segment_id' => $segment->id,
        'teacher_id' => $teacher->id,
    ]);

    Livewire::test(PublicSurvey::class, ['token' => $token])
        ->set('registrationCode', $enrollment->registration_code)
        ->call('identify')
        ->assertSet('teacherSlots', fn (array $slots): bool => count($slots) === 1)
        ->assertSee('Prof. Identificado')
        ->assertSeeHtml("teacherAnswers.{$segmentTeacher->id}");
});

test('aggregation includes flat s1 question answers', function (): void {
    ['unit' => $unit, 'batch' => $batch] = optionalIdentificationBatch();

    SurveyResponse::factory()->anonymous($batch, [
        'version' => '1.0',
        'sections' => [
            'S1' => ['questions' => ['S1Q1' => ['value' => 5]]],
        ],
    ])->create();

    /** @var NpsAggregationService $aggregator */
    $aggregator = app(NpsAggregationService::class);
    $result = $aggregator->aggregate($unit, new NpsDashboardFiltersData);

    expect($result->responsesCount)->toBe(1)
        ->and($result->scale15BySection['S1']->promoters)->toBe(1)
        ->and($result->scale15BySection['S1']->nps15())->toEqual(100.0);
});
