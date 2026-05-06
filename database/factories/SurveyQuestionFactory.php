<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\QuestionType;
use App\Models\SurveyQuestion;
use App\Models\SurveySection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SurveyQuestion>
 */
final class SurveyQuestionFactory extends Factory
{
    protected $model = SurveyQuestion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'survey_section_id' => SurveySection::factory(),
            'code' => 'Q'.Str::upper(Str::random(8)),
            'text' => fake()->sentence(),
            'type' => QuestionType::Scale1to5->value,
            'is_required' => true,
            'sort_order' => fake()->numberBetween(1, 20),
            'is_active' => true,
        ];
    }

    public function scale1to5(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => QuestionType::Scale1to5->value,
        ]);
    }

    public function scale0to10(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => QuestionType::Scale0to10->value,
        ]);
    }

    public function freeText(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => QuestionType::FreeText->value,
            'is_required' => false,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
