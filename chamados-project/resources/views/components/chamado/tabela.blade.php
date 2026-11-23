{{--
    Tabela de chamados reutilizada no painel e na listagem.
    Uso: <x-chamado.tabela :chamados="$chamados" :mostrar-requerente="true" />
    Aceita uma coleção ou um paginator (que ativa a paginação do Flux).
--}}
@props([
    'chamados',
    'mostrarRequerente' => false,
    'vazio' => 'Nenhum chamado encontrado.',
])

@if ($chamados->isEmpty())
    <x-vazio icone="inbox" :texto="$vazio" />
@else
    <flux:table :paginate="$chamados instanceof \Illuminate\Contracts\Pagination\Paginator ? $chamados : null">
        <flux:table.columns>
            <flux:table.column class="w-24">Nº</flux:table.column>
            <flux:table.column>Título</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Prioridade</flux:table.column>
            @if ($mostrarRequerente)
                <flux:table.column>Requerente</flux:table.column>
            @endif
            <flux:table.column>Responsável</flux:table.column>
            <flux:table.column>Aberto</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($chamados as $chamado)
                <flux:table.row :key="$chamado->id">
                    <flux:table.cell class="font-mono text-xs font-medium text-indigo-600 dark:text-indigo-300">{{ $chamado->numero() }}</flux:table.cell>
                    <flux:table.cell class="max-w-sm">
                        <flux:link :href="route('chamados.exibir', $chamado)" wire:navigate variant="ghost" class="block truncate font-medium hover:text-indigo-600! dark:hover:text-indigo-300!">
                            {{ $chamado->titulo }}
                        </flux:link>
                        @if ($chamado->categoria)
                            <flux:text size="sm" class="truncate">{{ $chamado->categoria->nomeCompleto() }}</flux:text>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell><x-chamado.status :status="$chamado->status" /></flux:table.cell>
                    <flux:table.cell><x-chamado.prioridade :prioridade="$chamado->prioridade" /></flux:table.cell>
                    @if ($mostrarRequerente)
                        <flux:table.cell>{{ $chamado->requerente->name }}</flux:table.cell>
                    @endif
                    <flux:table.cell>
                        @if ($chamado->responsavel)
                            {{ $chamado->responsavel->name }}
                        @else
                            <flux:text size="sm" class="italic">Sem responsável</flux:text>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <span title="{{ $chamado->criado_em->format('d/m/Y H:i') }}">{{ $chamado->criado_em->diffForHumans() }}</span>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
@endif
