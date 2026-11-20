<?php

namespace App\Actions\Chamados;

use App\Enums\EventoChamado;
use App\Enums\PrioridadeChamado;
use App\Enums\StatusChamado;
use App\Enums\TipoChamado;
use App\Exceptions\ChamadoException;
use App\Models\Area;
use App\Models\CampoFormulario;
use App\Models\Categoria;
use App\Models\Chamado;
use App\Models\Formulario;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Abre um novo chamado.
 *
 * Regras:
 * - o chamado nasce sempre com status "Aberto";
 * - o destino (área/seção) vem da categoria; sem categoria, a área é obrigatória;
 * - a área de destino precisa estar ativa e recebendo chamados;
 * - quando aberto por formulário, guarda uma cópia das respostas (rótulo + valor).
 */
class AbrirChamado
{
    /**
     * @param  array{
     *     titulo: string,
     *     descricao: string,
     *     prioridade?: PrioridadeChamado|string|null,
     *     tipo?: TipoChamado|string|null,
     *     categoria_id?: int|string|null,
     *     area_id?: int|string|null,
     * }  $dados
     * @param  array<string, mixed>  $respostas  Respostas indexadas pela `chave` de cada campo do formulário.
     */
    public function executar(User $requerente, array $dados, ?Formulario $formulario = null, array $respostas = []): Chamado
    {
        $categoria = match (true) {
            $formulario !== null => $formulario->categoria,
            filled($dados['categoria_id'] ?? null) => Categoria::findOrFail($dados['categoria_id']),
            default => null,
        };

        $area = match (true) {
            $categoria !== null => $categoria->area,
            filled($dados['area_id'] ?? null) => Area::findOrFail($dados['area_id']),
            default => null,
        };

        if ($area === null) {
            throw ChamadoException::semDestino();
        }

        if (! $area->ativo || ! $area->recebe_chamados) {
            throw ChamadoException::areaNaoAtende($area);
        }

        $chamado = new Chamado([
            'titulo' => $dados['titulo'],
            'descricao' => $dados['descricao'],
            'tipo' => $dados['tipo'] ?? ($formulario !== null ? $formulario->tipo_chamado : TipoChamado::Requisicao),
            'prioridade' => $dados['prioridade'] ?? ($formulario !== null ? $formulario->prioridade_padrao : PrioridadeChamado::Media),
            'area_id' => $area->id,
            'secao_id' => $categoria?->secao_id,
            'categoria_id' => $categoria?->id,
            'formulario_id' => $formulario?->id,
            'respostas_formulario' => $formulario ? $this->copiarRespostas($formulario, $respostas) : null,
        ]);

        $chamado->requerente()->associate($requerente);
        $chamado->status = StatusChamado::Aberto;

        return DB::transaction(function () use ($chamado, $requerente) {
            $chamado->save();

            $chamado->historicos()->create([
                'usuario_id' => $requerente->id,
                'evento' => EventoChamado::Aberto,
                'valor_novo' => StatusChamado::Aberto->value,
            ]);

            return $chamado;
        });
    }

    /**
     * @param  array<string, mixed>  $respostas
     * @return list<array{rotulo: string, valor: mixed}>
     */
    private function copiarRespostas(Formulario $formulario, array $respostas): array
    {
        return array_values($formulario->campos
            ->map(fn (CampoFormulario $campo) => ['rotulo' => $campo->rotulo, 'valor' => $respostas[$campo->chave] ?? null])
            ->all());
    }
}
