<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
final class StudentFactory extends Factory
{
    protected $model = Student::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'guardian_name' => fake()->optional(0.8)->name(),
            'guardian_email' => fake()->optional(0.7)->safeEmail(),
            'guardian_phone' => fake()->optional(0.5)->numerify('+55###########'),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    /**
     * @return $this
     */
    public function withGuardian(): static
    {
        return $this->state(fn (array $attributes): array => [
            'guardian_name' => fake()->name(),
            'guardian_email' => fake()->safeEmail(),
            'guardian_phone' => fake()->numerify('+55###########'),
        ]);
    }
}
