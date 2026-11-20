<?php

namespace App\Policies;

use App\Enums\StatusChamado;
use App\Models\Chamado;
use App\Models\User;

/**
 * Quem pode fazer o quê com um chamado.
 * Uso: $usuario->can('atender', $chamado), @can('cancelar', $chamado), $this->authorize(...).
 */
class ChamadoPolicy
{
    public function listar(User $usuario): bool
    {
        return $usuario->ativo;
    }

    /** Requerente vê os próprios; atendente vê os da sua área; administrador vê todos. */
    public function visualizar(User $usuario, Chamado $chamado): bool
    {
        return $this->ehRequerente($usuario, $chamado) || $usuario->podeAtender($chamado);
    }

    /** Qualquer usuário ativo pode abrir chamados (inclusive atendentes e administradores). */
    public function abrir(User $usuario): bool
    {
        return $usuario->ativo;
    }

    /** Editar título/descrição: o requerente enquanto ninguém começou a atender, ou a equipe. */
    public function editar(User $usuario, Chamado $chamado): bool
    {
        if ($chamado->status->finalizado()) {
            return false;
        }

        return $usuario->podeAtender($chamado)
            || ($this->ehRequerente($usuario, $chamado) && $chamado->status === StatusChamado::Aberto);
    }

    /** Atender: mudar status, definir responsável, escrever notas internas. */
    public function atender(User $usuario, Chamado $chamado): bool
    {
        return $usuario->podeAtender($chamado);
    }

    public function acompanhar(User $usuario, Chamado $chamado): bool
    {
        return ! $chamado->status->finalizado() && $this->visualizar($usuario, $chamado);
    }

    /** O requerente pode desistir do chamado enquanto ele estiver em andamento. */
    public function cancelar(User $usuario, Chamado $chamado): bool
    {
        return $chamado->status->podeMudarPara(StatusChamado::Cancelado)
            && ($this->ehRequerente($usuario, $chamado) || $usuario->podeAtender($chamado));
    }

    /** O requerente aceita (fecha) ou recusa (reabre) a solução. */
    public function avaliarSolucao(User $usuario, Chamado $chamado): bool
    {
        return $chamado->status === StatusChamado::Solucionado
            && ($this->ehRequerente($usuario, $chamado) || $usuario->podeAtender($chamado));
    }

    public function excluir(User $usuario, Chamado $chamado): bool
    {
        return $usuario->ativo && $usuario->ehAdministrador();
    }

    private function ehRequerente(User $usuario, Chamado $chamado): bool
    {
        return $usuario->ativo && $chamado->requerente_id === $usuario->id;
    }
}
