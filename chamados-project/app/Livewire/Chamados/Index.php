<?php

namespace App\Livewire\Chamados;

use App\Enums\PrioridadeChamado;
use App\Enums\StatusChamado;
use App\Models\Chamado;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * @property-read array<string, string> $escopos
 */
#[Title('Chamados')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'busca', except: '')]
    public string $busca = '';

    /** todos | meus | atribuidos | fila */
    #[Url(except: 'todos')]
    public string $escopo = 'todos';

    /** ativos | todos | <valor de StatusChamado> */
    #[Url(except: 'ativos')]
    public string $status = 'ativos';

    #[Url(except: '')]
    public string $prioridade = '';

    public function mount(): void
    {
        $this->authorize('listar', Chamado::class);

        if (! array_key_exists($this->escopo, $this->escopos)) {
            $this->escopo = 'todos';
        }
    }

    public function updated(string $propriedade): void
    {
        if (in_array($propriedade, ['busca', 'escopo', 'status', 'prioridade'], true)) {
            $this->resetPage();
        }
    }

    public function limparFiltros(): void
    {
        $this->reset('busca', 'escopo', 'status', 'prioridade');
        $this->resetPage();
    }

    /** @return array<string, string> */
    #[Computed]
    public function escopos(): array
    {
        $escopos = ['todos' => 'Todos que posso ver', 'meus' => 'Abertos por mim'];

        if (Auth::user()->fazAtendimento()) {
            $escopos['atribuidos'] = 'Atribuídos a mim';
            $escopos['fila'] = 'Fila sem responsável';
        }

        return $escopos;
    }

    /** @return LengthAwarePaginator<int, Chamado> */
    #[Computed]
    public function chamados(): LengthAwarePaginator
    {
        $usuario = Auth::user();

        $query = Chamado::query()
            ->visiveisPara($usuario)
            ->with(['requerente', 'responsavel', 'categoria.categoriaPai'])
            ->buscar($this->busca);

        match ($this->escopo) {
            'meus' => $query->where('requerente_id', $usuario->id),
            'atribuidos' => $query->where('responsavel_id', $usuario->id),
            'fila' => $query->whereNull('responsavel_id')->where('requerente_id', '!=', $usuario->id),
            default => null,
        };

        match ($this->status) {
            'ativos' => $query->emAndamento(),
            'todos' => null,
            default => StatusChamado::tryFrom($this->status) ? $query->where('status', $this->status) : null,
        };

        if (PrioridadeChamado::tryFrom($this->prioridade)) {
            $query->where('prioridade', $this->prioridade);
        }

        $this->escopo === 'fila'
            ? $query->ordenadosPorPrioridade()
            : $query->latest('criado_em');

        return $query->paginate(15);
    }

    #[Computed]
    public function filtrando(): bool
    {
        return $this->busca !== '' || $this->escopo !== 'todos' || $this->status !== 'ativos' || $this->prioridade !== '';
    }

    public function render(): View
    {
        return view('livewire.chamados.index');
    }
}
