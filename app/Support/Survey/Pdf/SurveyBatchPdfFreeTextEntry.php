<?php

declare(strict_types=1);

namespace App\Support\Survey\Pdf;

final readonly class SurveyBatchPdfFreeTextEntry
{
    public function __construct(
        public string $questionText,
        public string $questionCode,
        public string $text,
        public string $respondentLabel,
    ) {}
}
