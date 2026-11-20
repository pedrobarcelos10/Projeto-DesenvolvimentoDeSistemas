<?php

namespace App\Enums;

/** Tipos de evento registrados no histórico do chamado. */
enum EventoChamado: string
{
    case Aberto = 'aberto';
    case StatusAlterado = 'status_alterado';
    case ResponsavelDefinido = 'responsavel_definido';
    case ResponsavelRemovido = 'responsavel_removido';

    public function rotulo(): string
    {
        return match ($this) {
            self::Aberto => 'Chamado aberto',
            self::StatusAlterado => 'Status alterado',
            self::ResponsavelDefinido => 'Responsável definido',
            self::ResponsavelRemovido => 'Responsável removido',
        };
    }

    public function icone(): string
    {
        return match ($this) {
            self::Aberto => 'plus-circle',
            self::StatusAlterado => 'arrow-path',
            self::ResponsavelDefinido => 'user-plus',
            self::ResponsavelRemovido => 'user-minus',
        };
    }
}
