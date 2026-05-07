<?php

declare(strict_types=1);

return [
    'public' => [
        'title' => 'NPS Survey — Colégio Pense',
        'closed_title' => 'Survey unavailable',
        'closed_description' => 'This survey batch is not accepting responses right now.',
        'anonymous_respondent' => 'Anonymous respondent',
        'identification' => [
            'heading' => 'Let’s begin',
            'description' => 'Enter your registration code to start the survey.',
            'description_optional' => 'You may enter your registration code to personalize the survey, or continue without it.',
            'registration_code' => 'Registration code',
            'optional_hint' => 'optional',
            'continue' => 'Continue',
        ],
        'form' => [
            'respondent_label' => 'Respondent',
            'segment_not_applicable' => '—',
            'student_label' => 'Student',
            'guardian_label' => 'Guardian of',
            'segment_label' => 'Segment',
            'unit_label' => 'Unit',
            'submit' => 'Submit responses',
            'nsa_option' => 'N/A',
            'free_text_placeholder' => 'Type your response...',
            'teacher_subject' => 'Subject',
            'select_value' => 'Select',
        ],
        'thanks' => [
            'heading' => 'Thank you!',
            'description' => 'Your response was successfully recorded. Your opinion matters to us.',
        ],
    ],
    'errors' => [
        'invalid_registration_code' => 'Registration code not found in this unit.',
        'no_enrollment_current_year' => 'Registration code has no active enrollment for the current year.',
        'batch_not_accepting_responses' => 'This batch is not accepting responses right now.',
        'batch_not_found' => 'Survey not found.',
        'duplicate_response' => 'There is already a completed response for this registration code in this batch.',
        'unauthorized_batch_reopen' => 'Only administrators may reopen a batch.',
        'invalid_batch_transition' => 'Invalid status transition (:from → :to).',
        'required_question' => 'This question is required.',
        'identification_required' => 'This survey requires a registration code.',
    ],
];
