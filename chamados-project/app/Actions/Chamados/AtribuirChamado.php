<?php

namespace App\Actions\Chamados;

use App\Enums\EventoChamado;
use App\Enums\StatusChamado;
use App\Exceptions\ChamadoException;
use App\Models\Chamado;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Define (ou remove, com null) o responsável pelo chamado.
 *
 * Regras:
 * - o responsável precisa poder atender a área do chamado;
 * - chamados finalizados não podem ser reatribuídos;
 * - ao atribuir um chamado "Aberto", ele passa para "Em atendimento".
 */
class AtribuirChamado
{
    public function __construct(private AlterarStatusChamado $alterarStatus) {}

    public function executar(Chamado $chamado, ?User $responsavel, User $autor): Chamado
    {
        if ($chamado->status->finalizado()) {
            throw ChamadoException::chamadoFinalizado($chamado->status);
        }

        if ($responsavel !== null && ! $responsavel->podeAtender($chamado)) {
            throw ChamadoException::responsavelNaoAtendeArea($responsavel);
        }

        $anterior = $chamado->responsavel;

        if ($anterior?->id === $responsavel?->id) {
            return $chamado;
        }

        return DB::transaction(function () use ($chamado, $responsavel, $anterior, $autor) {
            $chamado->responsavel()->associate($responsavel);
            $chamado->save();

            $chamado->historicos()->create([
                'usuario_id' => $autor->id,
                'evento' => $responsavel ? EventoChamado::ResponsavelDefinido : EventoChamado::ResponsavelRemovido,
                'valor_anterior' => $anterior?->name,
                'valor_novo' => $responsavel?->name,
            ]);

            if ($responsavel && $chamado->status === StatusChamado::Aberto) {
                $this->alterarStatus->executar($chamado, StatusChamado::EmAtendimento, $autor);
            }

            return $chamado;
        });
    }
}
