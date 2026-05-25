<?php

declare(strict_types=1);

namespace App\Support\Survey;

use App\Models\SurveyQuestion;

final class SurveyQuestionCodeGenerator
{
    private int $sequence;

    public function __construct()
    {
        $this->sequence = $this->resolveStartingSequence();
    }

    public function nextCode(): string
    {
        return 'SQ'.str_pad((string) $this->sequence++, 2, '0', STR_PAD_LEFT);
    }

    private function resolveStartingSequence(): int
    {
        $maxSuffix = SurveyQuestion::query()
            ->withTrashed()
            ->where('code', 'like', 'SQ%')
            ->pluck('code')
            ->map(function (string $code): ?int {
                if (! preg_match('/^SQ(\d+)$/', $code, $matches)) {
                    return null;
                }

                return (int) $matches[1];
            })
            ->filter()
            ->max();

        return ($maxSuffix ?? 0) + 1;
    }
}
