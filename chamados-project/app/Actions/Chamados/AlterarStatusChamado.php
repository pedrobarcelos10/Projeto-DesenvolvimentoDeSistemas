<?php

namespace App\Actions\Chamados;

use App\Enums\EventoChamado;
use App\Enums\StatusChamado;
use App\Exceptions\ChamadoException;
use App\Models\Chamado;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Altera o status respeitando o fluxo definido em StatusChamado::transicoesPermitidas().
 * Mantém solucionado_em/fechado_em coerentes e registra o histórico.
 * Se um comentário for informado (ex.: a solução aplicada), ele vira um acompanhamento.
 */
class AlterarStatusChamado
{
    public function executar(Chamado $chamado, StatusChamado $novo, User $autor, ?string $comentario = null): Chamado
    {
        $anterior = $chamado->status;

        if (! $anterior->podeMudarPara($novo)) {
            throw ChamadoException::transicaoInvalida($anterior, $novo);
        }

        $chamado->status = $novo;

        match ($novo) {
            StatusChamado::Solucionado => $chamado->solucionado_em = now(),
            StatusChamado::Fechado, StatusChamado::Cancelado => $chamado->fechado_em = now(),
            // Reaberto: a solução anterior deixa de valer.
            default => $chamado->solucionado_em = null,
        };

        return DB::transaction(function () use ($chamado, $anterior, $novo, $autor, $comentario) {
            $chamado->save();

            $chamado->historicos()->create([
                'usuario_id' => $autor->id,
                'evento' => EventoChamado::StatusAlterado,
                'valor_anterior' => $anterior->value,
                'valor_novo' => $novo->value,
            ]);

            if (filled($comentario)) {
                $chamado->acompanhamentos()->make(['conteudo' => $comentario])->usuario()->associate($autor)->save();
            }

            return $chamado;
        });
    }
}
