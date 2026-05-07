<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class NpsDashboardFiltersData
{
    public function __construct(
        public ?string $surveyBatchId = null,
        public ?string $segmentId = null,
        public ?string $subjectId = null,
        public ?string $teacherId = null,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public static function fromLivewireFilters(array $filters): self
    {
        return new self(
            surveyBatchId: self::filledString($filters['survey_batch_id'] ?? null),
            segmentId: self::filledString($filters['segment_id'] ?? null),
            subjectId: self::filledString($filters['subject_id'] ?? null),
            teacherId: self::filledString($filters['teacher_id'] ?? null),
        );
    }

    private static function filledString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}
