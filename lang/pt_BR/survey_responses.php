<?php

declare(strict_types=1);

return [
    'label' => 'Resposta',
    'plural' => 'Respostas',
    'fields' => [
        'survey_batch_id' => 'Lote',
        'enrollment_id' => 'Matrícula',
        'student_name' => 'Aluno',
        'registration_code' => 'Código de matrícula',
        'segment_id' => 'Segmento',
        'respondent_type' => 'Tipo de respondente',
        'respondent_name' => 'Respondente',
        'is_completed' => 'Concluída',
        'completed_at' => 'Concluída em',
        'ip_address' => 'Endereço IP',
        'user_agent' => 'Navegador',
        'answers' => 'Respostas',
    ],
    'sections' => [
        'identification' => 'Identificação',
        'audit' => 'Auditoria',
        'answers' => 'Respostas',
    ],
    'display' => [
        'no_answers' => 'Nenhuma resposta registrada.',
        'unknown_section' => 'Seção :code (fora do modelo atual)',
        'survey_unavailable' => 'Não foi possível carregar o questionário vinculado ao lote. Exibindo os dados brutos.',
    ],
];
