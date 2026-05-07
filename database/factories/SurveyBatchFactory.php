<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SurveyBatchStatus;
use App\Models\Survey;
use App\Models\SurveyBatch;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SurveyBatch>
 */
final class SurveyBatchFactory extends Factory
{
    protected $model = SurveyBatch::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'unit_id' => Unit::factory(),
            'survey_id' => Survey::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'requires_identification' => true,
            'status' => SurveyBatchStatus::Draft->value,
            'public_token' => null,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'activated_at' => null,
            'closed_at' => null,
            'created_by' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SurveyBatchStatus::Draft->value,
            'public_token' => null,
            'activated_at' => null,
            'closed_at' => null,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SurveyBatchStatus::Active->value,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'activated_at' => now(),
            'closed_at' => null,
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SurveyBatchStatus::Closed->value,
            'closed_at' => now(),
        ]);
    }

    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'created_by' => $user->id,
        ]);
    }

    /** Pre-generated opaque token suitable for URLs (tests). */
    public function withPublicToken(?string $token = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'public_token' => $token ?? str_repeat('t', 64),
        ]);
    }

    public function withoutRequiredIdentification(): static
    {
        return $this->state(fn (array $attributes): array => [
            'requires_identification' => false,
        ]);
    }
}
