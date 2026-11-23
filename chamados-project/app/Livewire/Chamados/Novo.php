<?php

namespace App\Livewire\Chamados;

use App\Actions\Chamados\AbrirChamado;
use App\Enums\PrioridadeChamado;
use App\Enums\TipoCampo;
use App\Enums\TipoChamado;
use App\Exceptions\ChamadoException;
use App\Models\Categoria;
use App\Models\Chamado;
use App\Models\Formulario;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * @property-read Formulario|null $formulario
 * @property-read Collection<int, Categoria> $categorias
 */
#[Title('Abrir chamado')]
class Novo extends Component
{
    /** Formulário do catálogo escolhido (null = chamado genérico). */
    #[Url(as: 'formulario')]
    public ?int $formularioId = null;

    public string $titulo = '';

    public string $descricao = '';

    public string $tipo = 'requisicao';

    public string $prioridade = 'media';

    public string $categoriaId = '';

    /** @var array<string, mixed> Respostas dos campos do formulário, indexadas pela chave. */
    public array $respostas = [];

    public function mount(): void
    {
        $this->authorize('abrir', Chamado::class);

        $formulario = $this->formulario;

        if ($formulario === null) {
            $this->formularioId = null;

            return;
        }

        $this->titulo = $formulario->nome;
        $this->tipo = $formulario->tipo_chamado->value;
        $this->prioridade = $formulario->prioridade_padrao->value;

        foreach ($formulario->campos as $campo) {
            $this->respostas[$campo->chave] = $campo->tipo === TipoCampo::MultiplaEscolha ? [] : '';
        }
    }

    #[Computed]
    public function formulario(): ?Formulario
    {
        if ($this->formularioId === null) {
            return null;
        }

        return $this->formulariosDisponiveis()->with('campos', 'categoria')->find($this->formularioId);
    }

    /** @return Collection<int, Formulario> */
    #[Computed]
    public function formularios(): Collection
    {
        return $this->formulariosDisponiveis()->with('categoria.area')->orderBy('nome')->get();
    }

    /** @return Collection<int, Categoria> */
    #[Computed]
    public function categorias(): Collection
    {
        return Categoria::query()
            ->ativas()
            ->whereHas('area', fn (Builder $q) => $q->queAtendem())
            ->with('categoriaPai', 'area')
            ->get()
            ->sortBy(fn (Categoria $c) => $c->area->nome.$c->nomeCompleto())
            ->values();
    }

    public function abrir(AbrirChamado $abrirChamado): void
    {
        $this->authorize('abrir', Chamado::class);

        $formulario = $this->formulario;

        $this->validate(...$this->regras($formulario));

        try {
            $chamado = $abrirChamado->executar(Auth::user(), [
                'titulo' => $this->titulo,
                'descricao' => $this->descricao,
                'tipo' => $this->tipo,
                'prioridade' => $this->prioridade,
                'categoria_id' => $this->categoriaId,
            ], $formulario, $this->respostas);
        } catch (ChamadoException $e) {
            $this->addError('categoriaId', $e->getMessage());

            return;
        }

        session()->flash('sucesso', "Chamado {$chamado->numero()} aberto. Acompanhe o andamento por aqui.");

        $this->redirectRoute('chamados.exibir', ['chamado' => $chamado], navigate: true);
    }

    /** @return array{0: array<string, mixed>, 1: array<string, string>, 2: array<string, string>} */
    private function regras(?Formulario $formulario): array
    {
        $regras = [
            'titulo' => ['required', 'string', 'max:255'],
            'descricao' => [$formulario ? 'nullable' : 'required', 'string', 'max:5000'],
            'tipo' => ['required', Rule::enum(TipoChamado::class)],
            'prioridade' => ['required', Rule::enum(PrioridadeChamado::class)],
        ];
        $atributos = [];

        if ($formulario === null) {
            $regras['categoriaId'] = ['required', Rule::in($this->categorias->pluck('id')->map(fn ($id) => (string) $id))];
            $atributos['categoriaId'] = 'categoria';
        }

        foreach ($formulario->campos ?? [] as $campo) {
            $regras["respostas.{$campo->chave}"] = $campo->regrasValidacao();
            $atributos["respostas.{$campo->chave}"] = mb_strtolower($campo->rotulo);

            if ($campo->regrasPorItem() !== []) {
                $regras["respostas.{$campo->chave}.*"] = $campo->regrasPorItem();
            }
        }

        return [$regras, [], $atributos];
    }

    /** @return Builder<Formulario> */
    private function formulariosDisponiveis(): Builder
    {
        return Formulario::query()
            ->ativos()
            ->whereHas('categoria', fn (Builder $q) => $q->ativas()->whereHas('area', fn (Builder $q) => $q->queAtendem()));
    }

    public function render(): View
    {
        return view('livewire.chamados.novo');
    }
}
