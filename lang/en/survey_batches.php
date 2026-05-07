<?php

declare(strict_types=1);

return [
    'label' => 'Survey batch',
    'plural' => 'Survey batches',
    'fields' => [
        'unit_id' => 'Unit',
        'survey_id' => 'Survey',
        'title' => 'Title',
        'description' => 'Description',
        'status' => 'Status',
        'public_token' => 'Public token',
        'public_url' => 'Public link',
        'starts_at' => 'Starts at',
        'ends_at' => 'Ends at',
        'activated_at' => 'Activated at',
        'closed_at' => 'Closed at',
        'created_by' => 'Created by',
        'responses_count' => 'Responses',
        'requires_identification' => 'Require registration identification',
    ],
    'helpers' => [
        'requires_identification' => 'When disabled, respondents may start the survey without entering a student registration code (anonymous responses).',
    ],
    'sections' => [
        'period' => 'Response period',
        'audit' => 'Audit',
    ],
    'actions' => [
        'activate' => 'Activate',
        'close' => 'Close',
        'reopen' => 'Reopen',
        'copy_link' => 'Copy link',
    ],
    'messages' => [
        'activated' => 'Batch activated successfully.',
        'closed' => 'Batch closed successfully.',
        'reopened' => 'Batch reopened successfully.',
        'link_copied' => 'Link copied to clipboard.',
        'link_unavailable' => 'The public link will be available after the batch is activated.',
    ],
];
