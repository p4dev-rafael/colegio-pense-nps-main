<?php

declare(strict_types=1);

return [
    'label' => 'Pesquisa',
    'plural' => 'Pesquisas',
    'fields' => [
        'title' => 'Título',
        'description' => 'Descrição',
        'is_active' => 'Ativa',
        'sections_count' => 'Seções',
    ],
    'sections' => [
        'survey_sections_title' => 'Seções da pesquisa',
        'survey_questions_title' => 'Perguntas',
    ],
    'section_fields' => [
        'title' => 'Título',
        'description' => 'Descrição',
        'type' => 'Tipo',
        'sort_order' => 'Ordem',
        'is_active' => 'Ativa',
        'questions_count' => 'Perguntas',
    ],
    'question_fields' => [
        'code' => 'Código',
        'text' => 'Pergunta',
        'type' => 'Tipo',
        'is_required' => 'Obrigatória',
        'sort_order' => 'Ordem',
        'is_active' => 'Ativa',
    ],
    'actions' => [
        'clone' => 'Duplicar',
    ],
    'messages' => [
        'cloned' => 'Pesquisa duplicada com sucesso.',
        'clone_default_title' => ':title (cópia)',
    ],
];
