<div class="flex flex-col gap-8">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Olá, {{ \Illuminate\Support\Str::of($this->usuario->name)->before(' ') }}</flux:heading>
            <flux:text class="mt-1">
                @if ($this->usuario->fazAtendimento())
                    Veja seus chamados e a fila de atendimento{{ $this->usuario->secao ? ' de '.$this->usuario->secao->area->nome : '' }}.
                @else
                    Acompanhe aqui as suas solicitações.
                @endif
            </flux:text>
        </div>

        <flux:button variant="primary" icon="plus" :href="route('chamados.novo')" wire:navigate>Abrir chamado</flux:button>
    </div>

    <div @class([
        'grid gap-4 sm:grid-cols-2',
        'lg:grid-cols-4' => count($this->indicadores) > 2,
    ])>
        @foreach ($this->indicadores as $indicador)
            <x-indicador :rotulo="$indicador['rotulo']" :valor="$indicador['valor']" :icone="$indicador['icone']" :cor="$indicador['cor']" :href="$indicador['link']" />
        @endforeach
    </div>

    @if ($this->usuario->fazAtendimento())
        <section class="flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <flux:heading size="lg" level="2">Próximos da fila</flux:heading>
                <flux:button size="sm" variant="ghost" icon-trailing="arrow-right" :href="route('chamados.index', ['escopo' => 'fila'])" wire:navigate>Ver fila completa</flux:button>
            </div>
            <x-chamado.tabela :chamados="$this->fila" :mostrar-requerente="true" vazio="Nenhum chamado aguardando atendimento. 🎉" />
        </section>
    @endif

    <section class="flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <flux:heading size="lg" level="2">Meus chamados recentes</flux:heading>
            <flux:button size="sm" variant="ghost" icon-trailing="arrow-right" :href="route('chamados.index', ['escopo' => 'meus', 'status' => 'todos'])" wire:navigate>Ver todos</flux:button>
        </div>
        <x-chamado.tabela :chamados="$this->meusRecentes" vazio="Você ainda não abriu nenhum chamado." />
    </section>
</div>
