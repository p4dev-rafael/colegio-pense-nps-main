<?php

declare(strict_types=1);

namespace App\DTOs;

use InvalidArgumentException;

/** Parsed row from CSV import ({@see \App\Actions\Enrollment\ImportStudentsCsvAction}). */
final readonly class ImportStudentRow
{
    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromAssociative(array $row): self
    {
        $registrationCode = self::stringOrEmpty($row['registration_code'] ?? null);
        $name = self::stringOrEmpty($row['name'] ?? null);
        $segmentSlug = self::stringOrEmpty($row['segment_slug'] ?? null);
        $year = filter_var($row['year'] ?? null, FILTER_VALIDATE_INT);

        if ($registrationCode === '' || $name === '' || $segmentSlug === '' || $year === false) {
            throw new InvalidArgumentException(__('students.import.invalid_row_required'));
        }

        return new self(
            registrationCode: $registrationCode,
            name: $name,
            guardianName: self::nullableString($row['guardian_name'] ?? null),
            guardianEmail: self::nullableString($row['guardian_email'] ?? null),
            guardianPhone: self::nullableString($row['guardian_phone'] ?? null),
            segmentSlug: $segmentSlug,
            year: $year,
        );
    }

    public function __construct(
        public string $registrationCode,
        public string $name,
        public ?string $guardianName,
        public ?string $guardianEmail,
        public ?string $guardianPhone,
        public string $segmentSlug,
        public int $year,
    ) {}

    private static function stringOrEmpty(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return is_string($value) ? trim($value) : trim((string) $value);
    }

    private static function nullableString(mixed $value): ?string
    {
        $s = self::stringOrEmpty($value);

        return $s === '' ? null : $s;
    }
}
