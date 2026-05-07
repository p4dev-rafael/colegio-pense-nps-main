<?php

declare(strict_types=1);

return [
    'public' => [
        'title' => 'Pesquisa NPS — Colégio Pense',
        'closed_title' => 'Pesquisa indisponível',
        'closed_description' => 'Este lote de pesquisa não está aceitando respostas no momento.',
        'anonymous_respondent' => 'Respondente anônimo',
        'identification' => [
            'heading' => 'Vamos começar',
            'description' => 'Informe sua matrícula para iniciar a pesquisa.',
            'description_optional' => 'Você pode informar a matrícula para personalizar a pesquisa ou continuar sem identificação.',
            'registration_code' => 'Matrícula',
            'optional_hint' => 'opcional',
            'continue' => 'Continuar',
        ],
        'form' => [
            'respondent_label' => 'Respondente',
            'segment_not_applicable' => '—',
            'student_label' => 'Aluno',
            'guardian_label' => 'Responsável por',
            'segment_label' => 'Segmento',
            'unit_label' => 'Unidade',
            'submit' => 'Enviar respostas',
            'nsa_option' => 'NSA',
            'free_text_placeholder' => 'Digite sua resposta...',
            'teacher_subject' => 'Disciplina',
            'select_value' => 'Selecione',
        ],
        'thanks' => [
            'heading' => 'Obrigado!',
            'description' => 'Sua resposta foi registrada com sucesso. Sua opinião é muito importante para nós.',
        ],
    ],
    'errors' => [
        'invalid_registration_code' => 'Matrícula não encontrada nesta unidade.',
        'no_enrollment_current_year' => 'Matrícula sem vínculo ativo para o ano letivo atual.',
        'batch_not_accepting_responses' => 'Este lote não está aceitando respostas no momento.',
        'batch_not_found' => 'Pesquisa não encontrada.',
        'duplicate_response' => 'Já existe uma resposta concluída para esta matrícula neste lote.',
        'unauthorized_batch_reopen' => 'Apenas administradores podem reabrir um lote.',
        'invalid_batch_transition' => 'Transição de status inválida (:from → :to).',
        'required_question' => 'Esta pergunta é obrigatória.',
        'identification_required' => 'Esta pesquisa exige matrícula.',
    ],
];
