<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\AcompanhamentoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Acompanhamento (comentário) do chamado. Quando `interno`, é uma nota
 * visível apenas para a equipe de atendimento.
 *
 * @property CarbonImmutable $criado_em
 */
#[Table('acompanhamentos')]
#[Fillable(['conteudo', 'interno'])]
class Acompanhamento extends ModeloBase
{
    /** @use HasFactory<AcompanhamentoFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'interno' => 'boolean',
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
}
