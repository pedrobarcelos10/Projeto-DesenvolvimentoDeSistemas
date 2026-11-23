<?php

namespace App\Models;

use Database\Factories\SecaoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Seção dentro de uma área (ex.: TI > Suporte, TI > Infraestrutura).
 * Usuários são lotados em uma seção.
 */
#[Table('secoes')]
#[Fillable(['area_id', 'nome', 'descricao', 'ativo'])]
class Secao extends ModeloBase
{
    /** @use HasFactory<SecaoFactory> */
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

    /** @return HasMany<User, $this> */
    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** @return HasMany<Chamado, $this> */
    public function chamados(): HasMany
    {
        return $this->hasMany(Chamado::class);
    }

    /** @return HasMany<Categoria, $this> */
    public function categorias(): HasMany
    {
        return $this->hasMany(Categoria::class);
    }

    /** Por que a seção não pode ser excluída (null = pode). */
    public function motivoParaNaoExcluir(): ?string
    {
        return match (true) {
            $this->usuarios()->exists() => 'há usuários lotados nela',
            $this->categorias()->exists() => 'ela é a seção padrão de categorias',
            $this->chamados()->withTrashed()->exists() => 'ela possui chamados',
            default => null,
        };
    }
}
