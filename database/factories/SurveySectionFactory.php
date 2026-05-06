<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SectionType;
use App\Models\Survey;
use App\Models\SurveySection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SurveySection>
 */
final class SurveySectionFactory extends Factory
{
    protected $model = SurveySection::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'survey_id' => Survey::factory(),
            'title' => fake()->unique()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'type' => fake()->randomElement(SectionType::cases())->value,
            'sort_order' => fake()->numberBetween(1, 9),
            'is_active' => true,
        ];
    }

    public function teachers(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => SectionType::Teachers->value,
            'title' => 'Professores',
            'sort_order' => 1,
        ]);
    }

    public function coordination(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => SectionType::Coordination->value,
            'title' => 'Coordenação',
            'sort_order' => 2,
        ]);
    }

    public function npsFinal(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => SectionType::NpsFinal->value,
            'title' => 'NPS Final',
            'sort_order' => 9,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
