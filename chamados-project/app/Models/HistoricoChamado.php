<?php

namespace App\Models;

use App\Enums\EventoChamado;
use App\Enums\StatusChamado;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro imutável de cada mudança relevante do chamado.
 * É a base para a linha do tempo e para os indicadores futuros.
 *
 * @property EventoChamado $evento
 * @property CarbonImmutable $criado_em
 */
#[Table('historicos_chamado')]
#[Fillable(['usuario_id', 'evento', 'valor_anterior', 'valor_novo'])]
class HistoricoChamado extends ModeloBase
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'evento' => EventoChamado::class,
            'criado_em' => 'datetime',
        ];
    }

    /** @return BelongsTo<Chamado, $this> */
    public function chamado(): BelongsTo
    {
        return $this->belongsTo(Chamado::class);
    }

    /** @return BelongsTo<User, $this> */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /** Frase exibida na linha do tempo. */
    public function descricao(): string
    {
        return match ($this->evento) {
            EventoChamado::Aberto => 'abriu o chamado',
            EventoChamado::StatusAlterado => match ([$this->valor_anterior, $this->valor_novo]) {
                [StatusChamado::Solucionado->value, StatusChamado::Fechado->value] => 'aceitou a solução e fechou o chamado',
                [StatusChamado::Solucionado->value, StatusChamado::EmAtendimento->value] => 'reabriu o atendimento (solução não aceita)',
                default => 'alterou o status de "'.$this->rotuloStatus($this->valor_anterior)
                    .'" para "'.$this->rotuloStatus($this->valor_novo).'"',
            },
            EventoChamado::ResponsavelDefinido => 'definiu '.$this->valor_novo.' como responsável',
            EventoChamado::ResponsavelRemovido => 'removeu '.$this->valor_anterior.' da responsabilidade',
        };
    }

    private function rotuloStatus(?string $valor): string
    {
        return $valor !== null ? (StatusChamado::tryFrom($valor)?->rotulo() ?? $valor) : '';
    }
}
