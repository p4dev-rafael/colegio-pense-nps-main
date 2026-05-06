<?php

declare(strict_types=1);

return [
    'label' => 'Disciplina',
    'plural' => 'Disciplinas',
    'relation' => [
        'subjects_title' => 'Disciplinas do segmento',
        'segments_title' => 'Segmentos vinculados',
    ],
    'fields' => [
        'name' => 'Nome',
        'slug' => 'Slug',
        'sort_order' => 'Ordem',
        'is_active' => 'Ativa',
    ],
];
