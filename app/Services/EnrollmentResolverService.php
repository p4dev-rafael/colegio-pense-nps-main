<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Segment;
use App\Models\Student;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;

/**
 * Resolves enrollments for lookup and transactional imports (completed in later phases).
 */
final class EnrollmentResolverService
{
    public function findByRegistrationCode(string $registrationCode, string $unitId, int $year): ?Enrollment
    {
        return Enrollment::query()
            ->where('registration_code', $registrationCode)
            ->where('unit_id', $unitId)
            ->where('year', $year)
            ->first();
    }

    /**
     * Creates or updates the student profile and enrollment for a unit/year, keyed by registration code.
     */
    public function upsertStudentEnrollment(
        string $registrationCode,
        string $studentName,
        ?string $guardianName,
        ?string $guardianEmail,
        ?string $guardianPhone,
        Segment $segment,
        Unit $unit,
        int $year,
        bool $isActive = true,
    ): Enrollment {
        return DB::transaction(function () use ($registrationCode, $studentName, $guardianName, $guardianEmail, $guardianPhone, $segment, $unit, $year, $isActive): Enrollment {
            $enrollment = Enrollment::query()
                ->where('registration_code', $registrationCode)
                ->where('unit_id', $unit->id)
                ->where('year', $year)
                ->first();

            if ($enrollment !== null) {
                $studentFields = ['name' => $studentName];
                if ($guardianName !== null) {
                    $studentFields['guardian_name'] = $guardianName;
                }
                if ($guardianEmail !== null) {
                    $studentFields['guardian_email'] = $guardianEmail;
                }
                if ($guardianPhone !== null) {
                    $studentFields['guardian_phone'] = $guardianPhone;
                }
                $enrollment->student->update($studentFields);

                $enrollment->update([
                    'segment_id' => $segment->id,
                    'is_active' => $isActive,
                ]);

                return $enrollment->fresh();
            }

            $student = Student::query()->create([
                'name' => $studentName,
                'guardian_name' => $guardianName,
                'guardian_email' => $guardianEmail,
                'guardian_phone' => $guardianPhone,
                'is_active' => true,
            ]);

            return Enrollment::query()->create([
                'student_id' => $student->id,
                'unit_id' => $unit->id,
                'segment_id' => $segment->id,
                'registration_code' => $registrationCode,
                'year' => $year,
                'is_active' => $isActive,
            ]);
        });
    }
}
