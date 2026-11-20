<?php

namespace App\Models;

use App\Enums\PrioridadeChamado;
use App\Enums\TipoChamado;
use Database\Factories\FormularioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Formulário de abertura de chamado, montado pelo administrador.
 * Cada formulário pertence a uma categoria e possui campos dinâmicos.
 *
 * @property TipoChamado $tipo_chamado
 * @property PrioridadeChamado $prioridade_padrao
 */
#[Table('formularios')]
#[Fillable(['categoria_id', 'nome', 'descricao', 'tipo_chamado', 'prioridade_padrao', 'ativo'])]
class Formulario extends ModeloBase
{
    /** @use HasFactory<FormularioFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'tipo_chamado' => TipoChamado::class,
            'prioridade_padrao' => PrioridadeChamado::class,
            'ativo' => 'boolean',
        ];
    }

    /** @return BelongsTo<Categoria, $this> */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    /** @return HasMany<CampoFormulario, $this> */
    public function campos(): HasMany
    {
        return $this->hasMany(CampoFormulario::class)->orderBy('ordem');
    }

    /** @return HasMany<Chamado, $this> */
    public function chamados(): HasMany
    {
        return $this->hasMany(Chamado::class);
    }

    /** @param  Builder<self>  $query */
    #[Scope]
    protected function ativos(Builder $query): void
    {
        $query->where('ativo', true);
    }
}
