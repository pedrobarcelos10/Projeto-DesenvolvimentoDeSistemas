<?php

namespace App\Actions\Chamados;

use App\Models\Acompanhamento;
use App\Models\Chamado;
use App\Models\User;

/**
 * Adiciona um acompanhamento ao chamado.
 * Notas internas só são aceitas de quem atende a área do chamado;
 * para os demais, o acompanhamento é sempre público.
 */
class AdicionarAcompanhamento
{
    public function executar(Chamado $chamado, User $autor, string $conteudo, bool $interno = false): Acompanhamento
    {
        $acompanhamento = $chamado->acompanhamentos()->make([
            'conteudo' => $conteudo,
            'interno' => $interno && $autor->podeAtender($chamado),
        ]);

        $acompanhamento->usuario()->associate($autor)->save();

        return $acompanhamento;
    }
}
