<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Public-facing payload submitted by the survey respondent before persistence.
 *
 * @phpstan-type AnswersPayload array{
 *     version: string,
 *     sections: array<string, array<string, mixed>>
 * }
 */
final readonly class SurveyResponseData
{
    /**
     * @param  AnswersPayload  $answers
     */
    public function __construct(
        public array $answers,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
    ) {}

    /**
     * @param  array<string, mixed>  $sections
     */
    public static function fromSections(array $sections, ?string $ipAddress = null, ?string $userAgent = null): self
    {
        return new self(
            answers: [
                'version' => '1.0',
                'sections' => $sections,
            ],
            ipAddress: $ipAddress,
            userAgent: $userAgent,
        );
    }
}
