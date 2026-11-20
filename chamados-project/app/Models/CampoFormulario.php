<?php

namespace App\Models;

use App\Enums\TipoCampo;
use Database\Factories\CampoFormularioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\Rule;

/**
 * @property TipoCampo $tipo
 * @property list<string>|null $opcoes
 */
#[Table('campos_formulario')]
#[Fillable(['formulario_id', 'rotulo', 'chave', 'tipo', 'opcoes', 'texto_ajuda', 'obrigatorio', 'ordem'])]
class CampoFormulario extends ModeloBase
{
    /** @use HasFactory<CampoFormularioFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'tipo' => TipoCampo::class,
            'opcoes' => 'array',
            'obrigatorio' => 'boolean',
            'ordem' => 'integer',
        ];
    }

    /** @return BelongsTo<Formulario, $this> */
    public function formulario(): BelongsTo
    {
        return $this->belongsTo(Formulario::class);
    }

    /**
     * Regras de validação geradas a partir da configuração do campo.
     *
     * @return list<mixed>
     */
    public function regrasValidacao(): array
    {
        $opcoes = $this->opcoes ?? [];

        return [$this->obrigatorio ? 'required' : 'nullable', ...match ($this->tipo) {
            TipoCampo::Texto => ['string', 'max:255'],
            TipoCampo::TextoLongo => ['string', 'max:5000'],
            TipoCampo::Numero => ['numeric'],
            TipoCampo::Email => ['email'],
            TipoCampo::Data => ['date'],
            TipoCampo::Lista, TipoCampo::EscolhaUnica => [Rule::in($opcoes)],
            TipoCampo::MultiplaEscolha => ['array'],
        }];
    }

    /**
     * Regras aplicadas a cada item marcado (apenas múltipla escolha).
     *
     * @return list<mixed>
     */
    public function regrasPorItem(): array
    {
        return $this->tipo === TipoCampo::MultiplaEscolha ? [Rule::in($this->opcoes ?? [])] : [];
    }
}
