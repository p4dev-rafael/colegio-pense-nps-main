<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Enrollment;
use App\Models\Segment;
use App\Models\Student;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrollment>
 */
final class EnrollmentFactory extends Factory
{
    protected $model = Enrollment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = (int) fake()->year();

        return [
            'student_id' => Student::factory(),
            'unit_id' => Unit::factory(),
            'segment_id' => Segment::factory(),
            'registration_code' => strtoupper(fake()->unique()->bothify('??####')),
            'year' => $year,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    public function currentYear(Unit $unit, Segment $segment, Student $student): static
    {
        $year = (int) now()->year;

        return $this->state(fn (array $attributes): array => [
            'unit_id' => $unit->id,
            'segment_id' => $segment->id,
            'student_id' => $student->id,
            'year' => $year,
            'registration_code' => 'MAT'.fake()->unique()->numerify('######'),
            'is_active' => true,
        ]);
    }
}
