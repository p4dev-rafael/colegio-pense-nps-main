<?php

declare(strict_types=1);

namespace App\Support\Survey\Pdf;

final readonly class SurveyBatchPdfQuestionAverage
{
    public function __construct(
        public string $sectionTitle,
        public string $questionText,
        public string $questionCode,
        public ?float $average,
        public int $count,
    ) {}
}
