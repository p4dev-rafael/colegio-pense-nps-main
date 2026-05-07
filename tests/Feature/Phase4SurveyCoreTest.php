<?php

declare(strict_types=1);

use App\Actions\Survey\ActivateBatchAction;
use App\Actions\Survey\CloseBatchAction;
use App\Actions\Survey\CompleteSurveyResponseAction;
use App\DTOs\SurveyResponseData;
use App\Enums\RespondentType;
use App\Enums\SegmentGroup;
use App\Enums\SurveyBatchStatus;
use App\Enums\UserRole;
use App\Events\Survey\SurveyBatchActivated;
use App\Exceptions\Survey\SurveyException;
use App\Jobs\Survey\CloseExpiredSurveyBatchesJob;
use App\Models\Enrollment;
use App\Models\Segment;
use App\Models\Student;
use App\Models\Survey;
use App\Models\SurveyBatch;
use App\Models\SurveyResponse;
use App\Models\Unit;
use App\Models\User;
use App\Services\EnrollmentResolverService;
use Database\Seeders\SurveyTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(SurveyTemplateSeeder::class);
});

/**
 * @return array{unit: Unit, survey: Survey}
 */
function tenantAndTemplate(): array
{
    $unit = Unit::factory()->create();
    $survey = Survey::query()->firstOrFail();

    return ['unit' => $unit, 'survey' => $survey];
}

test('draft survey batch is not accepting responses', function (): void {
    ['unit' => $unit, 'survey' => $survey] = tenantAndTemplate();

    $batch = SurveyBatch::factory()->draft()->create([
        'unit_id' => $unit->id,
        'survey_id' => $survey->id,
    ]);

    expect($batch->fresh()->status)->toBe(SurveyBatchStatus::Draft);
    expect($batch->fresh()->isAcceptingResponses())->toBeFalse();
});

test('activating draft batch assigns public token and dispatches lifecycle', function (): void {
    Event::fake([SurveyBatchActivated::class]);

    ['unit' => $unit, 'survey' => $survey] = tenantAndTemplate();

    $batch = SurveyBatch::factory()->draft()->create([
        'unit_id' => $unit->id,
        'survey_id' => $survey->id,
    ]);

    /** @var ActivateBatchAction $action */
    $action = app(ActivateBatchAction::class);
    $activated = $action->execute($batch, null);

    expect($activated->status)->toBe(SurveyBatchStatus::Active);
    expect($activated->public_token)->not->toBeEmpty();

    Event::assertDispatched(SurveyBatchActivated::class);
});

test('close expired survey batches job closes active batches past ends_at', function (): void {
    ['unit' => $unit, 'survey' => $survey] = tenantAndTemplate();

    $batch = SurveyBatch::factory()->active()->create([
        'unit_id' => $unit->id,
        'survey_id' => $survey->id,
        'ends_at' => now()->subHour(),
        'starts_at' => now()->subDay(),
        'activated_at' => now()->subDay(),
    ]);

    $job = new CloseExpiredSurveyBatchesJob;
    $job->handle(app(CloseBatchAction::class));

    expect($batch->fresh()->status)->toBe(SurveyBatchStatus::Closed);
});

test('complete survey response action stores snapshot and prevents duplicate completions', function (): void {
    ['unit' => $unit, 'survey' => $survey] = tenantAndTemplate();

    $student = Student::factory()->create();
    $segment = Segment::factory()->forGroup(SegmentGroup::Ef2)->create();

    $enrollment = Enrollment::factory()->currentYear($unit, $segment, $student)->create();

    $batch = SurveyBatch::factory()->active()->create([
        'unit_id' => $unit->id,
        'survey_id' => $survey->id,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDay(),
        'activated_at' => now(),
    ]);

    $payload = SurveyResponseData::fromSections([
        'S2' => [
            'questions' => [
                'S2Q1' => ['value' => 4],
            ],
        ],
    ]);

    /** @var CompleteSurveyResponseAction $action */
    $action = app(CompleteSurveyResponseAction::class);
    $response = $action->execute($payload, $enrollment, $batch);

    expect($response->is_completed)->toBeTrue();
    expect($response->unit_id)->toBe($unit->id);
    expect($response->segment_id)->toBe($segment->id);

    expect(fn () => $action->execute($payload, $enrollment, $batch))
        ->toThrow(SurveyException::class);
});

test('complete survey response rejects anonymous submission when batch requires identification', function (): void {
    ['unit' => $unit, 'survey' => $survey] = tenantAndTemplate();

    $batch = SurveyBatch::factory()->active()->create([
        'unit_id' => $unit->id,
        'survey_id' => $survey->id,
        'requires_identification' => true,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDay(),
        'activated_at' => now(),
    ]);

    $payload = SurveyResponseData::fromSections([
        'S2' => [
            'questions' => [
                'S2Q1' => ['value' => 4],
            ],
        ],
    ]);

    /** @var CompleteSurveyResponseAction $action */
    $action = app(CompleteSurveyResponseAction::class);

    expect(fn () => $action->execute($payload, null, $batch))
        ->toThrow(SurveyException::class);
});

test('complete anonymous survey response when batch does not require identification', function (): void {
    ['unit' => $unit, 'survey' => $survey] = tenantAndTemplate();

    $batch = SurveyBatch::factory()->active()->withoutRequiredIdentification()->create([
        'unit_id' => $unit->id,
        'survey_id' => $survey->id,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDay(),
        'activated_at' => now(),
    ]);

    $payload = SurveyResponseData::fromSections([
        'S2' => [
            'questions' => [
                'S2Q1' => ['value' => 4],
            ],
        ],
    ]);

    /** @var CompleteSurveyResponseAction $action */
    $action = app(CompleteSurveyResponseAction::class);
    $response = $action->execute($payload, null, $batch);

    expect($response->is_completed)->toBeTrue();
    expect($response->enrollment_id)->toBeNull();
    expect($response->segment_id)->toBeNull();
    expect($response->respondent_type)->toBe(RespondentType::Anonymous);
    expect($response->unit_id)->toBe($unit->id);
});

test('enrollment resolver throws for unknown registration code', function (): void {
    ['unit' => $unit] = tenantAndTemplate();

    /** @var EnrollmentResolverService $resolver */
    $resolver = app(EnrollmentResolverService::class);

    expect(fn () => $resolver->resolveForPublicSurvey('NOPE', $unit->id))
        ->toThrow(SurveyException::class);
});

test('operator cannot reopen closed batch', function (): void {
    ['unit' => $unit, 'survey' => $survey] = tenantAndTemplate();

    $operator = User::factory()->create(['role' => UserRole::Operator]);

    $batch = SurveyBatch::factory()->closed()->create([
        'unit_id' => $unit->id,
        'survey_id' => $survey->id,
    ]);

    expect($operator->can('reopen', $batch))->toBeFalse();
});

test('admin can reopen closed batch per policy', function (): void {
    ['unit' => $unit, 'survey' => $survey] = tenantAndTemplate();

    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $batch = SurveyBatch::factory()->closed()->create([
        'unit_id' => $unit->id,
        'survey_id' => $survey->id,
    ]);

    expect($admin->can('reopen', $batch))->toBeTrue();
});

test('survey response factory paired creates coherent denormalized fields', function (): void {
    ['unit' => $unit, 'survey' => $survey] = tenantAndTemplate();

    $student = Student::factory()->create(['guardian_name' => 'Maria Silva']);
    $segment = Segment::factory()->forGroup(SegmentGroup::Ei)->create();

    $enrollment = Enrollment::factory()->currentYear($unit, $segment, $student)->create();

    $batch = SurveyBatch::factory()->create([
        'unit_id' => $unit->id,
        'survey_id' => $survey->id,
    ]);

    $response = SurveyResponse::factory()
        ->paired($batch, $enrollment, ['version' => '1.0', 'sections' => []], true)
        ->create();

    expect($response->unit_id)->toBe($unit->id);
    expect($response->is_completed)->toBeTrue();
});
