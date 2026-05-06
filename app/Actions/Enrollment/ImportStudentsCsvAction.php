<?php

declare(strict_types=1);

namespace App\Actions\Enrollment;

use App\DTOs\ImportStudentRow;
use App\Models\Segment;
use App\Models\Unit;
use App\Services\EnrollmentResolverService;
use InvalidArgumentException;
use Throwable;

final class ImportStudentsCsvAction
{
    public function __construct(
        private readonly EnrollmentResolverService $enrollmentResolver,
    ) {}

    /**
     * @return array{imported: int, skipped: int, errors: list<string>}
     */
    public function handle(string $csvContent, Unit $unit): array
    {
        $imported = 0;
        $skipped = 0;
        /** @var list<string> $errors */
        $errors = [];

        $lines = preg_split("/\r\n|\n|\r/", trim($csvContent)) ?: [];
        if ($lines === []) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => [__('students.import.empty_file')]];
        }

        $header = str_getcsv(array_shift($lines));
        $headerMap = [];
        foreach ($header as $index => $name) {
            $key = strtolower(trim((string) $name));
            $headerMap[$key] = $index;
        }

        $required = ['registration_code', 'name', 'segment_slug', 'year'];
        foreach ($required as $col) {
            if (! array_key_exists($col, $headerMap)) {
                return ['imported' => 0, 'skipped' => 0, 'errors' => [__('students.import.missing_header', ['column' => $col])]];
            }
        }

        foreach ($lines as $lineNumber => $line) {
            $line = trim($line);
            if ($line === '') {
                $skipped++;

                continue;
            }

            $cells = str_getcsv($line);
            $row = [];
            foreach ($headerMap as $column => $index) {
                $row[$column] = $cells[$index] ?? null;
            }

            try {
                $dto = ImportStudentRow::fromAssociative($row);
                $segment = Segment::query()->where('slug', $dto->segmentSlug)->active()->first();
                if ($segment === null) {
                    throw new InvalidArgumentException(__('students.import.unknown_segment', ['slug' => $dto->segmentSlug]));
                }

                $this->enrollmentResolver->upsertStudentEnrollment(
                    registrationCode: $dto->registrationCode,
                    studentName: $dto->name,
                    guardianName: $dto->guardianName,
                    guardianEmail: $dto->guardianEmail,
                    guardianPhone: $dto->guardianPhone,
                    segment: $segment,
                    unit: $unit,
                    year: $dto->year,
                );
                $imported++;
            } catch (Throwable $e) {
                $errors[] = __('students.import.row_error', [
                    'line' => $lineNumber + 2,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors];
    }
}
