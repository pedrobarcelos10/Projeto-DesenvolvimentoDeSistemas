<?php

namespace App\Enums;

enum TipoChamado: string
{
    /** Algo parou de funcionar (ex.: impressora com defeito). */
    case Incidente = 'incidente';

    /** Pedido de serviço (ex.: criar acesso, instalar software). */
    case Requisicao = 'requisicao';

    public function rotulo(): string
    {
        return match ($this) {
            self::Incidente => 'Incidente',
            self::Requisicao => 'Requisição',
        };
    }

    /** Cor usada nos badges do Flux. */
    public function cor(): string
    {
        return match ($this) {
            self::Incidente => 'rose',
            self::Requisicao => 'sky',
        };
    }

    public function descricao(): string
    {
        return match ($this) {
            self::Incidente => 'Algo parou de funcionar',
            self::Requisicao => 'Pedido de serviço ou acesso',
        };
    }
}
