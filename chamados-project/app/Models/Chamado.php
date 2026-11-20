<?php

namespace App\Models;

use App\Enums\PrioridadeChamado;
use App\Enums\StatusChamado;
use App\Enums\TipoChamado;
use Carbon\CarbonImmutable;
use Database\Factories\ChamadoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Chamado.
 *
 * Status, responsável e datas de solução/fechamento não são preenchidos
 * em massa: só mudam pelas Actions em App\Actions\Chamados, que registram histórico.
 *
 * @property TipoChamado $tipo
 * @property StatusChamado $status
 * @property PrioridadeChamado $prioridade
 * @property list<array{rotulo: string, valor: mixed}>|null $respostas_formulario
 * @property CarbonImmutable|null $solucionado_em
 * @property CarbonImmutable|null $fechado_em
 * @property CarbonImmutable $criado_em
 * @property CarbonImmutable $atualizado_em
 */
#[Table('chamados')]
#[Fillable([
    'titulo', 'descricao', 'tipo', 'prioridade',
    'area_id', 'secao_id', 'categoria_id', 'formulario_id', 'respostas_formulario',
])]
class Chamado extends ModeloBase
{
    /** @use HasFactory<ChamadoFactory> */
    use HasFactory, SoftDeletes;

    public const DELETED_AT = 'excluido_em';

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'aberto',
        'prioridade' => 'media',
        'tipo' => 'requisicao',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoChamado::class,
            'status' => StatusChamado::class,
            'prioridade' => PrioridadeChamado::class,
            'respostas_formulario' => 'array',
            'solucionado_em' => 'datetime',
            'fechado_em' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function requerente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requerente_id');
    }

    /** @return BelongsTo<User, $this> */
    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsavel_id');
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

    /** @return BelongsTo<Categoria, $this> */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    /** @return BelongsTo<Formulario, $this> */
    public function formulario(): BelongsTo
    {
        return $this->belongsTo(Formulario::class);
    }

    /** @return HasMany<Acompanhamento, $this> */
    public function acompanhamentos(): HasMany
    {
        return $this->hasMany(Acompanhamento::class)->oldest('criado_em');
    }

    /** @return HasMany<HistoricoChamado, $this> */
    public function historicos(): HasMany
    {
        return $this->hasMany(HistoricoChamado::class)->oldest('criado_em');
    }

    /** Número de protocolo exibido na interface: #000042. */
    public function numero(): string
    {
        return '#'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Restringe aos chamados que o usuário pode ver:
     * - administrador: todos;
     * - atendente: os que abriu + os da área em que está lotado;
     * - requerente: apenas os que abriu.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function visiveisPara(Builder $query, User $usuario): void
    {
        if ($usuario->ehAdministrador()) {
            return;
        }

        $areaId = $usuario->ehAtendente() ? $usuario->areaAtendidaId() : null;

        $query->where(function (Builder $query) use ($usuario, $areaId) {
            $query->where('requerente_id', $usuario->id);

            if ($areaId !== null) {
                $query->orWhere('area_id', $areaId);
            }
        });
    }

    /** @param  Builder<self>  $query */
    #[Scope]
    protected function emAndamento(Builder $query): void
    {
        $query->whereIn('status', StatusChamado::ativos());
    }

    /**
     * Fila de atendimento: mais urgentes primeiro e, entre iguais, os mais antigos.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function ordenadosPorPrioridade(Builder $query): void
    {
        $query->orderByRaw("case prioridade when 'urgente' then 4 when 'alta' then 3 when 'media' then 2 else 1 end desc")
            ->oldest('criado_em');
    }

    /**
     * Filtros da listagem de chamados.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function buscar(Builder $query, ?string $termo): void
    {
        $termo = trim((string) $termo);

        if ($termo === '') {
            return;
        }

        $numero = ltrim($termo, '#0');

        $query->where(function (Builder $query) use ($termo, $numero) {
            $query->where('titulo', 'like', "%{$termo}%");

            if ($numero !== '' && ctype_digit($numero)) {
                $query->orWhere('id', (int) $numero);
            }
        });
    }
}
