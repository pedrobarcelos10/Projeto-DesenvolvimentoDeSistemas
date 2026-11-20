<?php

/*
 * Mensagens de validação em português.
 * Regras não listadas aqui caem no inglês (APP_FALLBACK_LOCALE).
 */
return [
    'accepted' => 'O campo :attribute deve ser aceito.',
    'array' => 'O campo :attribute deve ser uma lista.',
    'boolean' => 'O campo :attribute deve ser verdadeiro ou falso.',
    'confirmed' => 'A confirmação de :attribute não confere.',
    'current_password' => 'A senha está incorreta.',
    'date' => 'O campo :attribute deve ser uma data válida.',
    'email' => 'O campo :attribute deve ser um e-mail válido.',
    'exists' => 'O valor selecionado em :attribute é inválido.',
    'in' => 'O valor selecionado em :attribute é inválido.',
    'integer' => 'O campo :attribute deve ser um número inteiro.',
    'lowercase' => 'O campo :attribute deve estar em letras minúsculas.',
    'max' => [
        'array' => 'O campo :attribute não pode ter mais de :max itens.',
        'file' => 'O campo :attribute não pode ser maior que :max kilobytes.',
        'numeric' => 'O campo :attribute não pode ser maior que :max.',
        'string' => 'O campo :attribute não pode ter mais de :max caracteres.',
    ],
    'min' => [
        'array' => 'O campo :attribute deve ter pelo menos :min itens.',
        'file' => 'O campo :attribute deve ter pelo menos :min kilobytes.',
        'numeric' => 'O campo :attribute deve ser pelo menos :min.',
        'string' => 'O campo :attribute deve ter pelo menos :min caracteres.',
    ],
    'numeric' => 'O campo :attribute deve ser um número.',
    'password' => [
        'letters' => 'O campo :attribute deve conter pelo menos uma letra.',
        'mixed' => 'O campo :attribute deve conter letras maiúsculas e minúsculas.',
        'numbers' => 'O campo :attribute deve conter pelo menos um número.',
        'symbols' => 'O campo :attribute deve conter pelo menos um símbolo.',
        'uncompromised' => 'Esta senha apareceu em um vazamento de dados. Escolha outra.',
    ],
    'required' => 'O campo :attribute é obrigatório.',
    'string' => 'O campo :attribute deve ser um texto.',
    'unique' => 'Este :attribute já está em uso.',

    'attributes' => [
        'name' => 'nome',
        'email' => 'e-mail',
        'password' => 'senha',
        'current_password' => 'senha atual',
        'titulo' => 'título',
        'descricao' => 'descrição',
        'prioridade' => 'prioridade',
        'tipo' => 'tipo',
        'categoria_id' => 'categoria',
        'conteudo' => 'mensagem',
        'comentario' => 'comentário',
    ],
];
