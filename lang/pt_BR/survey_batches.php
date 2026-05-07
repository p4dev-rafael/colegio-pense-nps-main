<?php

declare(strict_types=1);

return [
    'label' => 'Lote de pesquisa',
    'plural' => 'Lotes de pesquisa',
    'fields' => [
        'unit_id' => 'Unidade',
        'survey_id' => 'Pesquisa',
        'title' => 'Título',
        'description' => 'Descrição',
        'status' => 'Status',
        'public_token' => 'Token público',
        'public_url' => 'Link público',
        'starts_at' => 'Início',
        'ends_at' => 'Encerramento',
        'activated_at' => 'Ativado em',
        'closed_at' => 'Encerrado em',
        'created_by' => 'Criado por',
        'responses_count' => 'Respostas',
        'requires_identification' => 'Exige identificação por matrícula',
    ],
    'helpers' => [
        'requires_identification' => 'Quando desativado, o respondente pode iniciar a pesquisa sem informar a matrícula do aluno (respostas anônimas).',
    ],
    'sections' => [
        'period' => 'Período de respostas',
        'audit' => 'Auditoria',
    ],
    'actions' => [
        'activate' => 'Ativar',
        'close' => 'Encerrar',
        'reopen' => 'Reabrir',
        'copy_link' => 'Copiar link',
    ],
    'messages' => [
        'activated' => 'Lote ativado com sucesso.',
        'closed' => 'Lote encerrado com sucesso.',
        'reopened' => 'Lote reaberto com sucesso.',
        'link_copied' => 'Link copiado para a área de transferência.',
        'link_unavailable' => 'O link público estará disponível após a ativação do lote.',
    ],
];
