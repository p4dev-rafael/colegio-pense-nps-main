<?php

declare(strict_types=1);

namespace App\Support\Survey\Pdf;

final readonly class SurveyBatchPdfResponseSection
{
    /**
     * @param  list<SurveyBatchPdfResponseQuestion>  $questions
     * @param  list<SurveyBatchPdfResponseTeacher>  $teachers
     */
    public function __construct(
        public string $title,
        public ?string $description,
        public array $questions,
        public array $teachers,
    ) {}
}
