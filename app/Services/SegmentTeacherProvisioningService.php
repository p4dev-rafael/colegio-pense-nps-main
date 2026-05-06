<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Teacher;
use Illuminate\Validation\ValidationException;

/**
 * Validates invariants between {@see Teacher}, {@see Unit}, and {@see SegmentTeacher}.
 */
final class SegmentTeacherProvisioningService
{
    /**
     * @throws ValidationException
     */
    public function assertTeacherBelongsToUnit(string $teacherId, string $unitId): void
    {
        $exists = Teacher::query()
            ->whereKey($teacherId)
            ->whereHas('units', fn ($query) => $query->whereKey($unitId))
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'teacher_id' => __('teachers.messages.must_belong_unit_before_assignment'),
            ]);
        }
    }
}
