<?php

namespace App\Models;

use Database\Factories\AreaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * Área da organização (ex.: TI, RH). Agrupa seções e, quando
 * `recebe_chamados` está ativo, é destino de chamados.
 */
#[Table('areas')]
#[Fillable(['nome', 'sigla', 'descricao', 'recebe_chamados', 'ativo'])]
class Area extends ModeloBase
{
    /** @use HasFactory<AreaFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'recebe_chamados' => 'boolean',
            'ativo' => 'boolean',
        ];
    }

    /** @return HasMany<Secao, $this> */
    public function secoes(): HasMany
    {
        return $this->hasMany(Secao::class);
    }

    /** @return HasManyThrough<User, Secao, $this> */
    public function usuarios(): HasManyThrough
    {
        return $this->hasManyThrough(User::class, Secao::class);
    }

    /** @return HasMany<Categoria, $this> */
    public function categorias(): HasMany
    {
        return $this->hasMany(Categoria::class);
    }

    /** @return HasMany<Chamado, $this> */
    public function chamados(): HasMany
    {
        return $this->hasMany(Chamado::class);
    }

    /** @param  Builder<self>  $query */
    #[Scope]
    protected function ativas(Builder $query): void
    {
        $query->where('ativo', true);
    }

    /**
     * Áreas ativas que podem receber chamados.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function queAtendem(Builder $query): void
    {
        $query->where('ativo', true)->where('recebe_chamados', true);
    }
}
