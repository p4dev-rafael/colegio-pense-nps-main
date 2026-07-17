<?php

declare(strict_types=1);

use App\Actions\Survey\ExportSurveyBatchPdfAction;
use App\Filament\Resources\SurveyBatches\Pages\ViewSurveyBatch;
use App\Models\Enrollment;
use App\Models\Segment;
use App\Models\Student;
use App\Models\Survey;
use App\Models\SurveyBatch;
use App\Models\SurveyResponse;
use App\Models\Unit;
use App\Models\User;
use App\Services\SurveyBatchPdfReportBuilder;
use Database\Seeders\SurveyTemplateSeeder;
use Database\Seeders\UnitSeeder;
use Database\Seeders\UserSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\LaravelPdf\Facades\Pdf;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Pdf::fake();

    $this->seed([
        UnitSeeder::class,
        UserSeeder::class,
        SurveyTemplateSeeder::class,
    ]);
});

/**
 * @return array{unit: Unit, survey: Survey, batch: SurveyBatch, enrollment: Enrollment}
 */
function surveyBatchPdfHarness(): array
{
    $unit = Unit::query()->where('slug', 'unidade-norte')->firstOrFail();
    $survey = Survey::query()->firstOrFail();

    $batch = SurveyBatch::factory()->active()->create([
        'unit_id' => $unit->id,
        'survey_id' => $survey->id,
        'title' => 'Lote PDF Teste',
    ]);

    $student = Student::factory()->create(['name' => 'Maria Silva']);
    $segment = Segment::factory()->create(['name' => 'EF II A']);
    $enrollment = Enrollment::factory()->currentYear($unit, $segment, $student)->create([
        'registration_code' => 'MAT999888',
    ]);

    return compact('unit', 'survey', 'batch', 'enrollment');
}

test('export survey batch pdf saves report view with batch content', function (): void {
    ['batch' => $batch, 'enrollment' => $enrollment] = surveyBatchPdfHarness();

    $answers = [
        'version' => '1.0',
        'sections' => [
            'S1' => [
                'teachers' => [
                    [
                        'teacher_id' => 'teacher-1',
                        'teacher_name' => 'Prof. João',
                        'questions' => [
                            'S1Q1' => ['value' => 5],
                            'S1Q2' => ['value' => 4],
                        ],
                    ],
                ],
            ],
            'S2' => ['questions' => ['S2Q1' => ['value' => 4]]],
            'S9' => [
                'questions' => [
                    'S9NPS' => ['value' => 10],
                    'S9T1' => ['value' => 'Excelente atendimento.'],
                ],
            ],
        ],
    ];

    SurveyResponse::factory()->paired($batch, $enrollment, $answers, true)->create();

    $response = app(ExportSurveyBatchPdfAction::class)->execute($batch);

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);

    Pdf::assertSaved(function ($pdf, string $path) use ($batch): bool {
        return str_ends_with($path, '.pdf')
            && $pdf->viewName === 'pdf.survey-batch.report'
            && str_contains($pdf->html, $batch->title)
            && str_contains($pdf->html, 'Maria Silva')
            && str_contains($pdf->html, 'Prof. João')
            && str_contains($pdf->html, 'Excelente atendimento.')
            && str_contains($pdf->html, __('survey_batches.pdf.summary'));
    });
});

test('export survey batch pdf works when batch has no completed responses', function (): void {
    ['batch' => $batch] = surveyBatchPdfHarness();

    $response = app(ExportSurveyBatchPdfAction::class)->execute($batch);

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);

    Pdf::assertSaved(function ($pdf, string $path) use ($batch): bool {
        return str_ends_with($path, '.pdf')
            && $pdf->viewName === 'pdf.survey-batch.report'
            && str_contains($pdf->html, $batch->title)
            && str_contains($pdf->html, __('survey_batches.pdf.no_responses'));
    });
});

test('survey batch pdf report builder aggregates question and teacher averages', function (): void {
    ['batch' => $batch, 'enrollment' => $enrollment] = surveyBatchPdfHarness();

    $answers = [
        'version' => '1.0',
        'sections' => [
            'S1' => [
                'teachers' => [
                    [
                        'teacher_name' => 'Prof. Ana',
                        'questions' => ['S1Q1' => ['value' => 4]],
                    ],
                ],
            ],
            'S2' => ['questions' => ['S2Q1' => ['value' => 5]]],
            'S9' => ['questions' => ['S9NPS' => ['value' => 9]]],
        ],
    ];

    SurveyResponse::factory()->paired($batch, $enrollment, $answers, true)->create();

    $report = app(SurveyBatchPdfReportBuilder::class)->build($batch);

    expect($report->aggregation->responsesCount)->toBe(1)
        ->and($report->questionAverages)->not->toBeEmpty()
        ->and($report->teacherAverages)->not->toBeEmpty()
        ->and($report->responses)->toHaveCount(1)
        ->and($report->responses[0]->registrationCode)->toBe('MAT999888');
});

test('view survey batch page export pdf action triggers pdf download', function (): void {
    ['unit' => $unit, 'batch' => $batch, 'enrollment' => $enrollment] = surveyBatchPdfHarness();

    SurveyResponse::factory()->paired($batch, $enrollment, [
        'version' => '1.0',
        'sections' => [
            'S2' => ['questions' => ['S2Q1' => ['value' => 5]]],
            'S9' => ['questions' => ['S9NPS' => ['value' => 10]]],
        ],
    ], true)->create();

    /** @var User $operator */
    $operator = User::query()->where('email', 'operador@colegiopense.edu.br')->firstOrFail();

    $this->actingAs($operator);
    Filament::setTenant($unit);

    Livewire::test(ViewSurveyBatch::class, ['record' => $batch->getKey()])
        ->callAction('exportPdf')
        ->assertFileDownloaded();

    Pdf::assertSaved(fn ($pdf, string $path): bool => $pdf->viewName === 'pdf.survey-batch.report');
});
