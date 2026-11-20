<?php

namespace App\Models;

use Database\Factories\CategoriaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Categoria (tipo de solicitação) do catálogo de serviços.
 * Define para qual área (e opcionalmente seção) o chamado vai.
 * Suporta hierarquia: "Hardware > Impressora".
 */
#[Table('categorias')]
#[Fillable(['area_id', 'secao_id', 'categoria_pai_id', 'nome', 'descricao', 'ativo'])]
class Categoria extends ModeloBase
{
    /** @use HasFactory<CategoriaFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }

    /** @return BelongsTo<Area, $this> */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    /** @return BelongsTo<Secao, $this> */
    public function secao(): BelongsTo
    {
        return $this->belongsTo(Secao::class);
    }

    /** @return BelongsTo<self, $this> */
    public function categoriaPai(): BelongsTo
    {
        return $this->belongsTo(self::class, 'categoria_pai_id');
    }

    /** @return HasMany<self, $this> */
    public function subcategorias(): HasMany
    {
        return $this->hasMany(self::class, 'categoria_pai_id');
    }

    /** @return HasMany<Formulario, $this> */
    public function formularios(): HasMany
    {
        return $this->hasMany(Formulario::class);
    }

    /** Nome completo com a hierarquia: "Hardware > Impressora". */
    public function nomeCompleto(): string
    {
        return $this->categoriaPai ? $this->categoriaPai->nomeCompleto().' > '.$this->nome : $this->nome;
    }

    /** @param  Builder<self>  $query */
    #[Scope]
    protected function ativas(Builder $query): void
    {
        $query->where('ativo', true);
    }
}
