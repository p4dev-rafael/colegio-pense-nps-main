<?php

declare(strict_types=1);

namespace App\Support\Survey\Pdf;

final readonly class SurveyBatchPdfResponseEntry
{
    /**
     * @param  list<SurveyBatchPdfResponseSection>  $sections
     */
    public function __construct(
        public string $id,
        public string $respondentLabel,
        public ?string $respondentTypeLabel,
        public ?string $segmentName,
        public ?string $registrationCode,
        public ?string $completedAt,
        public array $sections,
    ) {}
}
