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
}
