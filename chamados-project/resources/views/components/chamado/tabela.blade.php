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
    <div class="rounded-lg border border-dashed border-zinc-300 p-8 text-center dark:border-zinc-700">
        <flux:icon.inbox class="mx-auto mb-2 size-8 text-zinc-400" />
        <flux:text>{{ $vazio }}</flux:text>
    </div>
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
                    <flux:table.cell class="font-mono text-xs text-zinc-500">{{ $chamado->numero() }}</flux:table.cell>
                    <flux:table.cell class="max-w-sm">
                        <flux:link :href="route('chamados.exibir', $chamado)" wire:navigate variant="ghost" class="block truncate font-medium">
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
