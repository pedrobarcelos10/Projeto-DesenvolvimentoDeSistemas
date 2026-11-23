<?php

namespace App\Enums;

/**
 * Perfil de acesso do usuário.
 *
 * - Requerente: abre e acompanha os próprios chamados.
 * - Atendente: tudo do requerente + atende os chamados da área em que está lotado.
 * - Administrador: controla todo o sistema (estrutura, catálogo, formulários e usuários).
 */
enum PerfilUsuario: string
{
    case Administrador = 'administrador';
    case Atendente = 'atendente';
    case Requerente = 'requerente';

    public function rotulo(): string
    {
        return match ($this) {
            self::Administrador => 'Administrador',
            self::Atendente => 'Atendente',
            self::Requerente => 'Requerente',
        };
    }

    /** Cor usada nos badges do Flux. */
    public function cor(): string
    {
        return match ($this) {
            self::Administrador => 'violet',
            self::Atendente => 'sky',
            self::Requerente => 'teal',
        };
    }

    public function icone(): string
    {
        return match ($this) {
            self::Administrador => 'shield-check',
            self::Atendente => 'lifebuoy',
            self::Requerente => 'user',
        };
    }

    public function descricao(): string
    {
        return match ($this) {
            self::Administrador => 'Controla todo o sistema: usuários, estrutura, catálogo e formulários.',
            self::Atendente => 'Atende os chamados da área em que está lotado, além de abrir os próprios.',
            self::Requerente => 'Abre e acompanha os próprios chamados.',
        };
    }
}
