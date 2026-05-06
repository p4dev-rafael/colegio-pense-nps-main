<?php

declare(strict_types=1);

use App\Actions\Enrollment\ImportStudentsCsvAction;
use App\Models\Enrollment;
use App\Models\Segment;
use App\Models\SegmentTeacher;
use App\Models\Teacher;
use App\Models\Unit;
use Database\Seeders\SegmentSeeder;
use Database\Seeders\UnitSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(UnitSeeder::class);
    $this->seed(UserSeeder::class);
    $this->seed(SegmentSeeder::class);
});

test('sixteen seeded segments exist', function (): void {
    expect(Segment::query()->count())->toBe(16);
});

test('csv student import creates enrollment for tenant', function (): void {
    $unit = Unit::query()->where('slug', 'unidade-norte')->firstOrFail();
    $csv = <<<CSV
registration_code,name,segment_slug,year,guardian_name,guardian_email
REG001,Joaquim Silva,1o-ano,2026,Maria Silva,maria@example.com
CSV;

    $results = app(ImportStudentsCsvAction::class)->handle($csv, $unit);

    expect($results['imported'])->toBe(1)
        ->and($results['errors'])->toBeEmpty();

    expect(Enrollment::query()
        ->where('registration_code', 'REG001')
        ->where('unit_id', $unit->id)
        ->where('year', 2026)
        ->exists())->toBeTrue();
});

test('segment teacher row rejects teacher not linked to unit', function (): void {
    $unit = Unit::query()->where('slug', 'unidade-norte')->firstOrFail();
    $teacher = Teacher::factory()->create();
    $segment = Segment::query()->where('slug', 'maternal-1')->firstOrFail();

    expect(fn () => SegmentTeacher::query()->create([
        'unit_id' => $unit->id,
        'segment_id' => $segment->id,
        'teacher_id' => $teacher->id,
        'subject_id' => null,
    ]))->toThrow(ValidationException::class);
});

test('segment teacher row persists when teacher is linked to unit', function (): void {
    $unit = Unit::query()->where('slug', 'unidade-norte')->firstOrFail();
    $teacher = Teacher::factory()->create();
    $unit->teachers()->attach($teacher->id);

    $segment = Segment::query()->where('slug', 'maternal-1')->firstOrFail();

    $assignment = SegmentTeacher::query()->create([
        'unit_id' => $unit->id,
        'segment_id' => $segment->id,
        'teacher_id' => $teacher->id,
        'subject_id' => null,
    ]);

    expect($assignment->fresh())->teacher_id->toBe($teacher->id);
});
