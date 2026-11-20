<?php

namespace App\Enums;

enum PrioridadeChamado: string
{
    case Baixa = 'baixa';
    case Media = 'media';
    case Alta = 'alta';
    case Urgente = 'urgente';

    public function rotulo(): string
    {
        return match ($this) {
            self::Baixa => 'Baixa',
            self::Media => 'Média',
            self::Alta => 'Alta',
            self::Urgente => 'Urgente',
        };
    }

    public function cor(): string
    {
        return match ($this) {
            self::Baixa => 'zinc',
            self::Media => 'sky',
            self::Alta => 'amber',
            self::Urgente => 'red',
        };
    }
}
