<?php

namespace App\Enums;

enum StatusChamado: string
{
    case Aberto = 'aberto';
    case EmAtendimento = 'em_atendimento';
    case Pendente = 'pendente';
    case Solucionado = 'solucionado';
    case Fechado = 'fechado';
    case Cancelado = 'cancelado';

    public function rotulo(): string
    {
        return match ($this) {
            self::Aberto => 'Aberto',
            self::EmAtendimento => 'Em atendimento',
            self::Pendente => 'Pendente',
            self::Solucionado => 'Solucionado',
            self::Fechado => 'Fechado',
            self::Cancelado => 'Cancelado',
        };
    }

    /** Cor usada nos badges do Flux. */
    public function cor(): string
    {
        return match ($this) {
            self::Aberto => 'sky',
            self::EmAtendimento => 'amber',
            self::Pendente => 'orange',
            self::Solucionado => 'green',
            self::Fechado => 'zinc',
            self::Cancelado => 'red',
        };
    }

    /** Rótulo do botão que leva o chamado para este status. */
    public function acao(): string
    {
        return match ($this) {
            self::Aberto => 'Reabrir',
            self::EmAtendimento => 'Colocar em atendimento',
            self::Pendente => 'Marcar como pendente',
            self::Solucionado => 'Solucionar',
            self::Fechado => 'Fechar',
            self::Cancelado => 'Cancelar',
        };
    }

    /** Status em que o chamado ainda exige alguma ação da equipe. */
    public function emAndamento(): bool
    {
        return in_array($this, [self::Aberto, self::EmAtendimento, self::Pendente], true);
    }

    /** Status finais não admitem nenhuma transição. */
    public function finalizado(): bool
    {
        return $this->transicoesPermitidas() === [];
    }

    /**
     * Fluxo permitido entre os status.
     *
     * @return list<self>
     */
    public function transicoesPermitidas(): array
    {
        return match ($this) {
            self::Aberto => [self::EmAtendimento, self::Pendente, self::Solucionado, self::Cancelado],
            self::EmAtendimento => [self::Pendente, self::Solucionado, self::Cancelado],
            self::Pendente => [self::EmAtendimento, self::Solucionado, self::Cancelado],
            // Solucionado pode ser fechado (solução aceita) ou reaberto (solução recusada).
            self::Solucionado => [self::Fechado, self::EmAtendimento],
            self::Fechado, self::Cancelado => [],
        };
    }

    public function podeMudarPara(self $status): bool
    {
        return in_array($status, $this->transicoesPermitidas(), true);
    }

    /** @return list<self> */
    public static function ativos(): array
    {
        return array_values(array_filter(self::cases(), fn (self $status) => $status->emAndamento()));
    }
}
