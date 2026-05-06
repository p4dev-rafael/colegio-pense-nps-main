<?php

declare(strict_types=1);

return [
    'label' => 'Aluno',
    'plural' => 'Alunos',
    'relation' => [
        'enrollments_title' => 'Matrículas',
    ],
    'fields' => [
        'name' => 'Nome do aluno',
        'guardian_name' => 'Nome do responsável',
        'guardian_email' => 'E-mail do responsável',
        'guardian_phone' => 'Telefone do responsável',
        'is_active' => 'Ativo',
    ],
    'actions' => [
        'import_csv' => 'Importar CSV',
    ],
    'import' => [
        'modal_title' => 'Importar alunos (CSV)',
        'hint' => 'Cabeçalhos: registration_code, name, segment_slug, year, guardian_name (opc.), guardian_email (opc.), guardian_phone (opc.).',
        'csv_label' => 'Conteúdo CSV',
        'empty_file' => 'O arquivo CSV está vazio.',
        'missing_header' => 'Cabeçalho obrigatório ausente: :column.',
        'invalid_row_required' => 'Linha sem registration_code, name, segment_slug ou year.',
        'unknown_segment' => 'Segmento não encontrado ou inativo: :slug.',
        'row_error' => 'Linha :line: :message',
        'summary' => 'Importados :imported registros (:skipped ignorados em branco).',
        'had_errors_title' => 'Importação parcialmente concluída',
    ],
];
