<?php

declare(strict_types=1);

return [
    'title' => 'NPS dashboard',
    'navigation_label' => 'NPS dashboard',

    'filters' => [
        'section_heading' => 'Segmentation',
        'section_description' => 'Refine metrics by survey batch, segment, subject or teacher. Subject and teacher filters only narrow teacher-section ratings; remaining sections still aggregate every response that matched the other filters.',
        'survey_batch' => 'Survey batch',
        'segment' => 'Segment',
        'subject' => 'Subject',
        'teacher' => 'Teacher',
        'placeholder_all_batches' => 'All batches',
        'placeholder_all_segments' => 'All segments',
        'placeholder_all_subjects' => 'All subjects',
        'placeholder_all_teachers' => 'All teachers',
    ],

    'widgets' => [
        'overview' => [
            'heading' => 'Summary',
            'completed_responses' => 'Completed responses',
            'nps_scale_15' => 'NPS scale 1–5',
            'nps_scale_010' => 'Recommendation NPS (0–10)',
            'scale_15_help' => 'Promoters 4–5; detractors 1–3; NSA excluded.',
            'scale_010_help' => 'Promoters 9–10; detractors 0–6; passives 7–8 in denominator.',
        ],
        'sections_chart' => [
            'heading' => 'Structural NPS (scale 1–5)',
            'description' => 'Each column aggregates every 1–5 question in sections S1–S8; the 0–10 recommendation score is displayed only on the headline stat cards.',
            'dataset' => 'NPS (%)',
        ],
    ],
];
