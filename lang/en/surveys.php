<?php

declare(strict_types=1);

return [
    'label' => 'Survey',
    'plural' => 'Surveys',
    'fields' => [
        'title' => 'Title',
        'description' => 'Description',
        'is_active' => 'Active',
        'sections_count' => 'Sections',
    ],
    'sections' => [
        'survey_sections_title' => 'Survey sections',
        'survey_questions_title' => 'Questions',
    ],
    'section_fields' => [
        'title' => 'Title',
        'description' => 'Description',
        'type' => 'Type',
        'sort_order' => 'Order',
        'is_active' => 'Active',
        'questions_count' => 'Questions',
    ],
    'question_fields' => [
        'code' => 'Code',
        'text' => 'Question',
        'type' => 'Type',
        'is_required' => 'Required',
        'sort_order' => 'Order',
        'is_active' => 'Active',
    ],
    'actions' => [
        'clone' => 'Clone',
    ],
    'messages' => [
        'cloned' => 'Survey cloned successfully.',
        'clone_default_title' => ':title (copy)',
        'section_cloned' => 'Survey section cloned successfully.',
        'section_clone_default_title' => ':title (copy)',
    ],
];
