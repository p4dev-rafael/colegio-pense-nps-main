<?php

declare(strict_types=1);

return [
    'title' => 'Painel NPS',
    'navigation_label' => 'Painel NPS',

    'filters' => [
        'section_heading' => 'Segmentação',
        'section_description' => 'Refine métricas por lote acadêmico, segmento, disciplina ou professor. Filtros de disciplina/professor limitam apenas as avaliações da seção de professores; demais perguntas seguem usando todas as respostas que passaram pelos demais filtros.',
        'survey_batch' => 'Lote de pesquisa',
        'segment' => 'Segmento',
        'subject' => 'Disciplina',
        'teacher' => 'Professor',
        'placeholder_all_batches' => 'Todos os lotes',
        'placeholder_all_segments' => 'Todos os segmentos',
        'placeholder_all_subjects' => 'Todas as disciplinas',
        'placeholder_all_teachers' => 'Todos os professores',
    ],

    'widgets' => [
        'overview' => [
            'heading' => 'Resumo',
            'completed_responses' => 'Respostas concluídas',
            'nps_scale_15' => 'NPS perguntas 1–5',
            'nps_scale_010' => 'NPS recomendação (0–10)',
            'scale_15_help' => 'Promotores 4–5; detratores 1–3; NSA ignorado.',
            'scale_010_help' => 'Promotores 9–10; detratores 0–6; neutros 7–8 no denominador.',
        ],
        'sections_chart' => [
            'heading' => 'NPS por área estrutural (escala 1–5)',
            'description' => 'Médias agregam todas as perguntas em cada uma das seções S1–S8; a recomendação 0–10 aparece apenas no cartão do topo.',
            'dataset' => 'NPS (%)',
        ],
    ],
];
