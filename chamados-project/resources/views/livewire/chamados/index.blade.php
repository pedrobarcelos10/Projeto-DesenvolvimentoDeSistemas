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
