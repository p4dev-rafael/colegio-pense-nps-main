<?php

declare(strict_types=1);

return [
    'label' => 'Response',
    'plural' => 'Responses',
    'fields' => [
        'survey_batch_id' => 'Batch',
        'enrollment_id' => 'Enrollment',
        'student_name' => 'Student',
        'registration_code' => 'Registration code',
        'segment_id' => 'Segment',
        'respondent_type' => 'Respondent type',
        'respondent_name' => 'Respondent',
        'is_completed' => 'Completed',
        'completed_at' => 'Completed at',
        'ip_address' => 'IP address',
        'user_agent' => 'User agent',
        'answers' => 'Answers',
    ],
    'sections' => [
        'identification' => 'Identification',
        'audit' => 'Audit',
        'answers' => 'Answers',
    ],
];
