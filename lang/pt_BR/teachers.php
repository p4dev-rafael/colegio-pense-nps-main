<?php

declare(strict_types=1);

return [
    'label' => 'Professor',
    'plural' => 'Professores',
    'relation' => [
        'units_title' => 'Unidades',
        'segment_teachers_title' => 'Atribuições por segmento',
    ],
    'fields' => [
        'name' => 'Nome',
        'email' => 'E-mail',
        'is_active' => 'Ativo',
        'unit_id' => 'Unidade',
        'segment_id' => 'Segmento',
        'subject_id' => 'Disciplina',
        'teacher_id' => 'Professor',
    ],
    'messages' => [
        'must_belong_unit_before_assignment' => 'O professor deve estar vinculado à mesma unidade antes de uma atribuição por segmento.',
        'subject_required_for_segment' => 'Selecione a disciplina para segmentos EF2 ou EM.',
        'subject_forbidden_for_segment' => 'Este segmento não utiliza vínculo por disciplina.',
        'duplicate_segment_assignment' => 'Este professor já está atribuído a esse segmento (e disciplina, se aplicável).',
    ],
];
