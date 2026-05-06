<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Segment;
use App\Models\SegmentTeacher;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SegmentTeacher>
 */
final class SegmentTeacherFactory extends Factory
{
    protected $model = SegmentTeacher::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'unit_id' => Unit::factory(),
            'segment_id' => Segment::factory(),
            'teacher_id' => Teacher::factory(),
            'subject_id' => null,
        ];
    }

    public function withSubject(): static
    {
        return $this->state(fn (array $attributes): array => [
            'subject_id' => Subject::factory(),
        ]);
    }

    public function eiOrEf1Teacher(): static
    {
        return $this->state(fn (array $attributes): array => [
            'subject_id' => null,
        ]);
    }
}
