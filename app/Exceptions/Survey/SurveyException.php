<?php

declare(strict_types=1);

namespace App\Exceptions\Survey;

use App\Exceptions\BusinessException;

final class SurveyException extends BusinessException
{
    public static function invalidRegistrationCode(string $code): self
    {
        return new self(
            message: sprintf("Registration code '%s' not found", $code),
            userMessageKey: 'survey.errors.invalid_registration_code',
            context: ['code' => $code],
        );
    }

    public static function noEnrollmentCurrentYear(string $code, int $year): self
    {
        return new self(
            message: sprintf("No active enrollment found for code '%s' in year %d", $code, $year),
            userMessageKey: 'survey.errors.no_enrollment_current_year',
            context: ['code' => $code, 'year' => $year],
        );
    }

    public static function batchNotAcceptingResponses(string $batchId): self
    {
        return new self(
            message: sprintf('Batch %s is not accepting responses (status or period)', $batchId),
            userMessageKey: 'survey.errors.batch_not_accepting_responses',
            context: ['batch_id' => $batchId],
        );
    }

    public static function batchNotFound(string $token): self
    {
        return new self(
            message: sprintf("Batch with token '%s' not found", $token),
            userMessageKey: 'survey.errors.batch_not_found',
            context: ['token' => $token],
        );
    }

    public static function identificationRequired(string $batchId): self
    {
        return new self(
            message: sprintf('Batch %s requires registration identification', $batchId),
            userMessageKey: 'survey.errors.identification_required',
            context: ['batch_id' => $batchId],
        );
    }

    public static function duplicateResponse(string $enrollmentId, string $batchId): self
    {
        return new self(
            message: sprintf('Duplicate response: enrollment %s already responded to batch %s', $enrollmentId, $batchId),
            userMessageKey: 'survey.errors.duplicate_response',
            context: ['enrollment_id' => $enrollmentId, 'batch_id' => $batchId],
        );
    }

    public static function unauthorizedBatchReopen(string $batchId): self
    {
        return new self(
            message: sprintf('User not authorized to reopen batch %s', $batchId),
            userMessageKey: 'survey.errors.unauthorized_batch_reopen',
            context: ['batch_id' => $batchId],
        );
    }

    public static function invalidBatchTransition(string $from, string $to): self
    {
        return new self(
            message: sprintf('Invalid batch status transition: %s -> %s', $from, $to),
            userMessageKey: 'survey.errors.invalid_batch_transition',
            context: ['from' => $from, 'to' => $to],
        );
    }
}
