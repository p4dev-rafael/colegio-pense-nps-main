<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\Survey\SurveyException;
use App\Models\SurveyBatch;
use Illuminate\Support\Str;

final class SurveyBatchLinkService
{
    public function ensurePublicToken(SurveyBatch $batch): string
    {
        if ($batch->public_token !== null && $batch->public_token !== '') {
            return $batch->public_token;
        }

        $token = $this->generateUniqueToken();

        $batch->forceFill(['public_token' => $token])->save();

        return $token;
    }

    public function generatePublicUrl(SurveyBatch $batch): string
    {
        $token = $this->ensurePublicToken($batch);

        return route('survey.show', ['token' => $token]);
    }

    public function resolveByToken(string $token): SurveyBatch
    {
        $batch = SurveyBatch::query()
            ->where('public_token', $token)
            ->first();

        if ($batch === null) {
            throw SurveyException::batchNotFound($token);
        }

        return $batch;
    }

    private function generateUniqueToken(): string
    {
        do {
            $token = Str::random(64);
        } while (SurveyBatch::query()->where('public_token', $token)->exists());

        return $token;
    }
}
