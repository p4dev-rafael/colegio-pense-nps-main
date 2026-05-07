<?php

declare(strict_types=1);

return [
    'user_role' => [
        'admin' => 'Administrator',
        'operator' => 'Operator',
    ],
    'segment_group' => [
        'EI' => 'Early childhood education',
        'EF1' => 'Elementary I',
        'EF2' => 'Elementary II',
        'EM' => 'High school',
    ],
    'question_type' => [
        'scale_1_to_5' => 'Scale 1-5',
        'scale_0_to_10' => 'Scale 0-10',
        'free_text' => 'Free text',
    ],
    'section_type' => [
        'teachers' => 'Teachers',
        'coordination' => 'Coordination',
        'secretariat' => 'Secretariat',
        'physical_structure' => 'Physical structure',
        'cafeteria' => 'Cafeteria',
        'social_media' => 'Social media',
        'chaplaincy' => 'Chaplaincy',
        'institutional' => 'Institutional assessment',
        'nps_final' => 'Final NPS',
    ],
    'survey_batch_status' => [
        'draft' => 'Draft',
        'active' => 'Active',
        'closed' => 'Closed',
    ],
    'respondent_type' => [
        'student' => 'Student',
        'guardian' => 'Guardian',
        'anonymous' => 'Anonymous',
    ],
];
