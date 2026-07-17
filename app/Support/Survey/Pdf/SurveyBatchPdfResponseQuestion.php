<?php

declare(strict_types=1);

namespace App\Support\Survey\Pdf;

final readonly class SurveyBatchPdfResponseQuestion
{
    public function __construct(
        public string $label,
        public string $value,
        public bool $isFreeText,
    ) {}
}
