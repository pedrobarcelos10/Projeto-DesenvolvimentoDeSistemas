<?php

use App\Enums\PrioridadeChamado;
use App\Enums\StatusChamado;
use App\Models\Chamado;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Chamados')] class extends Component {
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
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Chamados</flux:heading>
            <flux:text class="mt-1">Acompanhe as solicitações que você abriu e as que estão sob sua responsabilidade.</flux:text>
        </div>

        @can('abrir', \App\Models\Chamado::class)
            <flux:button variant="primary" icon="plus" :href="route('chamados.novo')" wire:navigate>
                Abrir chamado
            </flux:button>
        @endcan
    </div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <flux:input wire:model.live.debounce.400ms="busca" icon="magnifying-glass" placeholder="Buscar por título ou número" clearable />

        <flux:select wire:model.live="escopo">
            @foreach ($this->escopos as $valor => $rotulo)
                <flux:select.option :value="$valor">{{ $rotulo }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="status">
            <flux:select.option value="ativos">Em andamento</flux:select.option>
            <flux:select.option value="todos">Todos os status</flux:select.option>
            @foreach (\App\Enums\StatusChamado::cases() as $opcao)
                <flux:select.option :value="$opcao->value">{{ $opcao->rotulo() }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="prioridade">
            <flux:select.option value="">Todas as prioridades</flux:select.option>
            @foreach (\App\Enums\PrioridadeChamado::cases() as $opcao)
                <flux:select.option :value="$opcao->value">{{ $opcao->rotulo() }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    @if ($this->filtrando)
        <div>
            <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="limparFiltros">Limpar filtros</flux:button>
        </div>
    @endif

    <x-chamado.tabela
        :chamados="$this->chamados"
        :mostrar-requerente="auth()->user()->fazAtendimento()"
        vazio="Nenhum chamado encontrado com esses filtros."
    />
</div>
