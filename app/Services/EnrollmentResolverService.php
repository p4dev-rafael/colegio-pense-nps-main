<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\Survey\SurveyException;
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
     * Resolves an enrollment for the public survey form.
     *
     * @throws SurveyException
     */
    public function resolveForPublicSurvey(string $registrationCode, string $unitId, ?int $year = null): Enrollment
    {
        $year ??= (int) now()->year;

        $code = trim($registrationCode);

        $existsAnyYear = Enrollment::query()
            ->where('registration_code', $code)
            ->where('unit_id', $unitId)
            ->exists();

        if (! $existsAnyYear) {
            throw SurveyException::invalidRegistrationCode($code);
        }

        $enrollment = Enrollment::query()
            ->with(['student', 'segment', 'unit'])
            ->where('registration_code', $code)
            ->where('unit_id', $unitId)
            ->where('year', $year)
            ->where('is_active', true)
            ->first();

        if ($enrollment === null) {
            throw SurveyException::noEnrollmentCurrentYear($code, $year);
        }

        return $enrollment;
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
