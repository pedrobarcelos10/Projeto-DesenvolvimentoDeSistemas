<?php

namespace App\Exceptions;

use DomainException;

/** Violação de regra da área administrativa. A mensagem é exibida ao usuário. */
class AdministracaoException extends DomainException
{
    public static function naoPodeAlterarProprioAcesso(): self
    {
        return new self('Você não pode desativar a própria conta nem remover o seu perfil de administrador.');
    }

    public static function ultimoAdministrador(): self
    {
        return new self('O sistema precisa de pelo menos um administrador ativo.');
    }

    public static function atendenteSemLotacao(): self
    {
        return new self('Atendentes precisam estar lotados em uma seção: é ela que define a área que atendem.');
    }

    public static function emUso(string $item, string $motivo): self
    {
        return new self("Não é possível excluir {$item}: {$motivo}. Desative-o(a) em vez de excluir.");
    }

    public static function hierarquiaInvalida(): self
    {
        return new self('A categoria pai precisa ser uma categoria principal da mesma área.');
    }
}
