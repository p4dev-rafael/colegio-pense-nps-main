<?php

declare(strict_types=1);

return [
    'label' => 'Student',
    'plural' => 'Students',
    'relation' => [
        'enrollments_title' => 'Enrollments',
    ],
    'fields' => [
        'name' => 'Student name',
        'guardian_name' => 'Guardian name',
        'guardian_email' => 'Guardian email',
        'guardian_phone' => 'Guardian phone',
        'is_active' => 'Active',
    ],
    'actions' => [
        'import_csv' => 'Import CSV',
    ],
    'import' => [
        'modal_title' => 'Import students (CSV)',
        'hint' => 'Headers: registration_code, name, segment_slug, year, guardian_name (opt.), guardian_email (opt.), guardian_phone (opt.).',
        'csv_label' => 'CSV contents',
        'empty_file' => 'The CSV content is empty.',
        'missing_header' => 'Required header missing: :column.',
        'invalid_row_required' => 'Row missing registration_code, name, segment_slug, or year.',
        'unknown_segment' => 'Unknown or inactive segment: :slug.',
        'row_error' => 'Line :line: :message',
        'summary' => 'Imported :imported records (:skipped blank lines skipped).',
        'had_errors_title' => 'Import partially completed',
    ],
];
