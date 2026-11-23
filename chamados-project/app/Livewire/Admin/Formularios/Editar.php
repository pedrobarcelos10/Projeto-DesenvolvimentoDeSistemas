<?php

namespace App\Livewire\Admin\Formularios;

use App\Actions\Formularios\SalvarFormulario;
use App\Enums\PrioridadeChamado;
use App\Enums\TipoCampo;
use App\Enums\TipoChamado;
use App\Models\CampoFormulario;
use App\Models\Categoria;
use App\Models\Formulario;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Construtor de formulários: dados gerais + campos dinâmicos (ordem, tipo, opções).
 * Tudo é salvo de uma vez pela action SalvarFormulario.
 */
class Editar extends Component
{
    #[Locked]
    public ?int $formularioId = null;

    public string $nome = '';

    public string $descricao = '';

    public string $categoriaId = '';

    public string $tipoChamado = 'requisicao';

    public string $prioridadePadrao = 'media';

    public bool $ativo = true;

    /** @var array<int, array{uid: string, id: int|null, chave: string|null, rotulo: string, tipo: string, opcoes: string, texto_ajuda: string, obrigatorio: bool}> */
    public array $campos = [];

    public function mount(?Formulario $formulario = null): void
    {
        if (! $formulario?->exists) {
            $this->adicionarCampo();

            return;
        }

        $this->formularioId = $formulario->id;
        $this->nome = $formulario->nome;
        $this->descricao = (string) $formulario->descricao;
        $this->categoriaId = (string) $formulario->categoria_id;
        $this->tipoChamado = $formulario->tipo_chamado->value;
        $this->prioridadePadrao = $formulario->prioridade_padrao->value;
        $this->ativo = $formulario->ativo;

        $this->campos = $formulario->campos->map(fn (CampoFormulario $campo) => [
            'uid' => (string) Str::uuid(),
            'id' => $campo->id,
            'chave' => $campo->chave,
            'rotulo' => $campo->rotulo,
            'tipo' => $campo->tipo->value,
            'opcoes' => implode("\n", $campo->opcoes ?? []),
            'texto_ajuda' => (string) $campo->texto_ajuda,
            'obrigatorio' => $campo->obrigatorio,
        ])->all();
    }

    public function render(): View
    {
        return view('livewire.admin.formularios.editar')->title($this->formularioId ? 'Editar formulário' : 'Novo formulário');
    }

    /** @return Collection<int, Categoria> */
    #[Computed]
    public function categorias(): Collection
    {
        return Categoria::query()
            ->with(['area', 'categoriaPai'])
            ->where(fn ($q) => $q->where('ativo', true)->when($this->categoriaId !== '', fn ($q) => $q->orWhere('id', $this->categoriaId)))
            ->get()
            ->sortBy(fn (Categoria $c) => $c->area->nome.' '.$c->nomeCompleto())
            ->values();
    }

    public function adicionarCampo(): void
    {
        $this->campos[] = [
            'uid' => (string) Str::uuid(),
            'id' => null,
            'chave' => null,
            'rotulo' => '',
            'tipo' => TipoCampo::Texto->value,
            'opcoes' => '',
            'texto_ajuda' => '',
            'obrigatorio' => false,
        ];
    }

    public function removerCampo(int $indice): void
    {
        unset($this->campos[$indice]);
        $this->campos = array_values($this->campos);
    }

    public function moverCampo(int $indice, int $direcao): void
    {
        $destino = $indice + $direcao;

        if (! isset($this->campos[$indice], $this->campos[$destino])) {
            return;
        }

        [$this->campos[$indice], $this->campos[$destino]] = [$this->campos[$destino], $this->campos[$indice]];
    }

    public function salvar(SalvarFormulario $salvar): void
    {
        $tiposComOpcoes = collect(TipoCampo::cases())->filter->possuiOpcoes()->map->value->all();

        $this->validate([
            'nome' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string', 'max:2000'],
            'categoriaId' => ['required', Rule::exists('categorias', 'id')],
            'tipoChamado' => ['required', Rule::enum(TipoChamado::class)],
            'prioridadePadrao' => ['required', Rule::enum(PrioridadeChamado::class)],
            'ativo' => ['boolean'],
            'campos' => ['array'],
            'campos.*.rotulo' => ['required', 'string', 'max:255'],
            'campos.*.tipo' => ['required', Rule::enum(TipoCampo::class)],
            'campos.*.opcoes' => ['nullable', 'string', 'max:5000'],
            'campos.*.texto_ajuda' => ['nullable', 'string', 'max:255'],
            'campos.*.obrigatorio' => ['boolean'],
        ], [], [
            'categoriaId' => 'categoria',
            'campos.*.rotulo' => 'pergunta',
            'campos.*.opcoes' => 'opções',
        ]);

        foreach ($this->campos as $indice => $campo) {
            if (in_array($campo['tipo'], $tiposComOpcoes, true) && $salvar->normalizarOpcoes($campo['opcoes']) === []) {
                $this->addError("campos.{$indice}.opcoes", 'Informe pelo menos uma opção (uma por linha).');
            }
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $formulario = $salvar->executar([
            'categoria_id' => $this->categoriaId,
            'nome' => $this->nome,
            'descricao' => $this->descricao,
            'tipo_chamado' => $this->tipoChamado,
            'prioridade_padrao' => $this->prioridadePadrao,
            'ativo' => $this->ativo,
        ], array_values($this->campos), $this->formularioId ? Formulario::findOrFail($this->formularioId) : null);

        Flux::toast(variant: 'success', text: $this->formularioId ? 'Formulário atualizado.' : 'Formulário criado.');

        $this->redirectRoute('admin.formularios.editar', ['formulario' => $formulario], navigate: true);
    }
}
