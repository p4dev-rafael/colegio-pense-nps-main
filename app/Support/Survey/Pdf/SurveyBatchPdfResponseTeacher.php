<?php

declare(strict_types=1);

namespace App\Support\Survey\Pdf;

final readonly class SurveyBatchPdfResponseTeacher
{
    /**
     * @param  list<SurveyBatchPdfResponseQuestion>  $questions
     */
    public function __construct(
        public string $name,
        public array $questions,
    ) {}
}
