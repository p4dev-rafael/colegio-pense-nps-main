<?php

declare(strict_types=1);

return [
    'fields' => [
        'name' => 'Nome',
        'email' => 'E-mail',
        'slug' => 'Slug',
        'password' => 'Senha',
        'password_confirmation' => 'Confirmar senha',
        'is_active' => 'Ativo',
        'created_at' => 'Criado em',
        'updated_at' => 'Atualizado em',
        'deleted_at' => 'Excluído em',
    ],
    'sections' => [
        'general' => 'Informações gerais',
        'access' => 'Acesso',
        'units' => 'Unidades',
        'guardian' => 'Responsável',
        'classification' => 'Classificação',
    ],
    'actions' => [
        'create' => 'Criar',
        'edit' => 'Editar',
        'delete' => 'Excluir',
        'view' => 'Visualizar',
        'save' => 'Salvar',
        'cancel' => 'Cancelar',
    ],
];
