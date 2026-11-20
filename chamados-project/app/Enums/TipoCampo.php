<?php

namespace App\Enums;

/** Tipos de campo disponíveis nos formulários montados pelo administrador. */
enum TipoCampo: string
{
    case Texto = 'texto';
    case TextoLongo = 'texto_longo';
    case Numero = 'numero';
    case Email = 'email';
    case Data = 'data';
    case Lista = 'lista';
    case EscolhaUnica = 'escolha_unica';
    case MultiplaEscolha = 'multipla_escolha';

    public function rotulo(): string
    {
        return match ($this) {
            self::Texto => 'Texto curto',
            self::TextoLongo => 'Texto longo',
            self::Numero => 'Número',
            self::Email => 'E-mail',
            self::Data => 'Data',
            self::Lista => 'Lista de seleção',
            self::EscolhaUnica => 'Escolha única',
            self::MultiplaEscolha => 'Múltipla escolha',
        };
    }

    /** Campos que exigem uma lista de opções configurada pelo admin. */
    public function possuiOpcoes(): bool
    {
        return in_array($this, [self::Lista, self::EscolhaUnica, self::MultiplaEscolha], true);
    }
}
