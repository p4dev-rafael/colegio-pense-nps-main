<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SegmentGroup;
use App\Models\Segment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Segment>
 */
final class SegmentFactory extends Factory
{
    protected $model = Segment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'group' => fake()->randomElement(SegmentGroup::cases())->value,
            'sort_order' => fake()->numberBetween(1, 100),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    /** @param  SegmentGroup|string  $group */
    public function forGroup(SegmentGroup|string $group): static
    {
        $value = $group instanceof SegmentGroup ? $group->value : $group;

        return $this->state(fn (array $attributes): array => [
            'group' => $value,
        ]);
    }
}
