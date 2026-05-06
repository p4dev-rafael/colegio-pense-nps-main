<?php

declare(strict_types=1);

return [
    'label' => 'Teacher',
    'plural' => 'Teachers',
    'relation' => [
        'units_title' => 'Units',
        'segment_teachers_title' => 'Segment assignments',
    ],
    'fields' => [
        'name' => 'Name',
        'email' => 'Email',
        'is_active' => 'Active',
        'unit_id' => 'Unit',
        'segment_id' => 'Segment',
        'subject_id' => 'Subject',
        'teacher_id' => 'Teacher',
    ],
    'messages' => [
        'must_belong_unit_before_assignment' => 'The teacher must belong to this unit before a segment assignment.',
        'subject_required_for_segment' => 'Select a subject for EF2 or EM segments.',
        'subject_forbidden_for_segment' => 'This segment does not link teachers by subject.',
        'duplicate_segment_assignment' => 'This teacher is already assigned for this segment (and subject, if applicable).',
    ],
];
