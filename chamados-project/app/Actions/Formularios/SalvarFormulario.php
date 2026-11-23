<?php

namespace App\Actions\Formularios;

use App\Enums\TipoCampo;
use App\Models\CampoFormulario;
use App\Models\Formulario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Cria ou atualiza um formulário de abertura de chamado junto com os seus campos.
 *
 * - os campos chegam na ordem em que devem aparecer;
 * - campos que não vieram são removidos (os chamados já abertos guardam uma cópia das respostas);
 * - a chave de cada campo é gerada a partir do rótulo e nunca muda depois de criada;
 * - opções só são guardadas para lista, escolha única e múltipla escolha.
 */
class SalvarFormulario
{
    /**
     * @param  array{categoria_id: int|string, nome: string, descricao?: string|null, tipo_chamado: string, prioridade_padrao: string, ativo: bool}  $dados
     * @param  list<array{id?: int|string|null, rotulo: string, tipo: string, opcoes?: list<string>|string|null, texto_ajuda?: string|null, obrigatorio?: bool}>  $campos
     */
    public function executar(array $dados, array $campos, ?Formulario $formulario = null): Formulario
    {
        $formulario ??= new Formulario;

        return DB::transaction(function () use ($formulario, $dados, $campos) {
            $formulario->fill([
                'categoria_id' => (int) $dados['categoria_id'],
                'nome' => $dados['nome'],
                'descricao' => filled($dados['descricao'] ?? null) ? $dados['descricao'] : null,
                'tipo_chamado' => $dados['tipo_chamado'],
                'prioridade_padrao' => $dados['prioridade_padrao'],
                'ativo' => $dados['ativo'],
            ])->save();

            $existentes = $formulario->campos()->get()->keyBy('id');
            $mantidos = collect($campos)->pluck('id')->filter()->map(fn ($id) => (int) $id);

            $formulario->campos()->whereNotIn('id', $mantidos)->delete();

            $chavesEmUso = $existentes->only($mantidos->all())->map(fn (CampoFormulario $campo): string => $campo->chave)->values()->all();

            foreach ($campos as $ordem => $dadosCampo) {
                $tipo = TipoCampo::from($dadosCampo['tipo']);
                $campo = $existentes->get((int) ($dadosCampo['id'] ?? 0)) ?? $formulario->campos()->make();

                if (! $campo->exists) {
                    $campo->chave = $this->gerarChave($dadosCampo['rotulo'], $chavesEmUso);
                    $chavesEmUso[] = $campo->chave;
                }

                $campo->fill([
                    'rotulo' => $dadosCampo['rotulo'],
                    'tipo' => $tipo,
                    'opcoes' => $tipo->possuiOpcoes() ? $this->normalizarOpcoes($dadosCampo['opcoes'] ?? []) : null,
                    'texto_ajuda' => filled($dadosCampo['texto_ajuda'] ?? null) ? $dadosCampo['texto_ajuda'] : null,
                    'obrigatorio' => (bool) ($dadosCampo['obrigatorio'] ?? false),
                    'ordem' => $ordem,
                ])->save();
            }

            return $formulario->load('campos');
        });
    }

    /**
     * @param  list<string>|string|null  $opcoes  Lista ou texto com uma opção por linha.
     * @return list<string>
     */
    public function normalizarOpcoes(array|string|null $opcoes): array
    {
        $lista = is_array($opcoes) ? $opcoes : (preg_split('/\R/', (string) $opcoes) ?: []);

        return array_values(array_unique(array_filter(array_map(fn ($opcao) => trim((string) $opcao), $lista), fn (string $opcao) => $opcao !== '')));
    }

    /** @param  array<int, string>  $emUso  Chaves já usadas no formulário. */
    private function gerarChave(string $rotulo, array $emUso): string
    {
        $base = Str::limit(Str::slug($rotulo, '_'), 50, '') ?: 'campo';
        $chave = $base;

        for ($i = 2; in_array($chave, $emUso, true); $i++) {
            $chave = "{$base}_{$i}";
        }

        return $chave;
    }
}
