<?php

namespace App\Exceptions;

use App\Enums\StatusChamado;
use App\Models\Area;
use App\Models\User;
use DomainException;

/** Violação de regra de negócio dos chamados. A mensagem é exibida ao usuário. */
class ChamadoException extends DomainException
{
    public static function transicaoInvalida(StatusChamado $de, StatusChamado $para): self
    {
        return new self("Não é possível mudar o chamado de \"{$de->rotulo()}\" para \"{$para->rotulo()}\".");
    }

    public static function areaNaoAtende(Area $area): self
    {
        return new self("A área \"{$area->nome}\" não está recebendo chamados.");
    }

    public static function semDestino(): self
    {
        return new self('Informe a categoria ou a área de destino do chamado.');
    }

    public static function responsavelNaoAtendeArea(User $usuario): self
    {
        return new self("{$usuario->name} não é atendente da área deste chamado.");
    }

    public static function chamadoFinalizado(StatusChamado $status): self
    {
        return new self("Chamados com status \"{$status->rotulo()}\" não podem ser alterados.");
    }
}
