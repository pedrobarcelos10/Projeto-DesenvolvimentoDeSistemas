<?php

use App\Enums\StatusChamado;
use App\Models\Chamado;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Painel')] class extends Component {
    #[Computed]
    public function usuario(): User
    {
        return Auth::user();
    }

    /**
     * Indicadores exibidos nos cartões do topo.
     *
     * @return list<array{rotulo: string, valor: int, icone: string, link: string}>
     */
    #[Computed]
    public function indicadores(): array
    {
        $usuario = $this->usuario;
        $meus = fn () => Chamado::query()->where('requerente_id', $usuario->id);

        $indicadores = [
            [
                'rotulo' => 'Meus chamados em andamento',
                'valor' => $meus()->emAndamento()->count(),
                'icone' => 'inbox',
                'link' => route('chamados.index', ['escopo' => 'meus']),
            ],
            [
                'rotulo' => 'Aguardando minha avaliação',
                'valor' => $meus()->where('status', StatusChamado::Solucionado)->count(),
                'icone' => 'check-badge',
                'link' => route('chamados.index', ['escopo' => 'meus', 'status' => StatusChamado::Solucionado->value]),
            ],
        ];

        if ($usuario->fazAtendimento()) {
            $indicadores[] = [
                'rotulo' => 'Atribuídos a mim',
                'valor' => Chamado::query()->where('responsavel_id', $usuario->id)->emAndamento()->count(),
                'icone' => 'user',
                'link' => route('chamados.index', ['escopo' => 'atribuidos']),
            ];
            $indicadores[] = [
                'rotulo' => 'Fila sem responsável',
                'valor' => $this->filaQuery()->count(),
                'icone' => 'queue-list',
                'link' => route('chamados.index', ['escopo' => 'fila']),
            ];
        }

        return $indicadores;
    }

    /** @return Collection<int, Chamado> */
    #[Computed]
    public function meusRecentes(): Collection
    {
        return Chamado::query()
            ->where('requerente_id', $this->usuario->id)
            ->with(['responsavel', 'categoria.categoriaPai'])
            ->latest('criado_em')
            ->limit(5)
            ->get();
    }

    /** @return Collection<int, Chamado> */
    #[Computed]
    public function fila(): Collection
    {
        return $this->filaQuery()
            ->with(['requerente', 'responsavel', 'categoria.categoriaPai'])
            ->ordenadosPorPrioridade()
            ->limit(5)
            ->get();
    }

    /** Chamados ativos da área do atendente, sem responsável, que não foram abertos por ele. @return Builder<Chamado> */
    private function filaQuery(): Builder
    {
        return Chamado::query()
            ->visiveisPara($this->usuario)
            ->emAndamento()
            ->whereNull('responsavel_id')
            ->where('requerente_id', '!=', $this->usuario->id);
    }
}; ?>

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
            <a href="{{ $indicador['link'] }}" wire:navigate class="block">
                <flux:card class="h-full transition hover:border-accent">
                    <div class="flex items-center justify-between">
                        <flux:text size="sm">{{ $indicador['rotulo'] }}</flux:text>
                        <flux:icon :name="$indicador['icone']" class="size-5 text-zinc-400" />
                    </div>
                    <flux:heading size="xl" class="mt-2 text-3xl tabular-nums">{{ $indicador['valor'] }}</flux:heading>
                </flux:card>
            </a>
        @endforeach
    </div>

    @if ($this->usuario->fazAtendimento())
        <section class="flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <flux:heading size="lg" level="2">Próximos da fila</flux:heading>
                <flux:link :href="route('chamados.index', ['escopo' => 'fila'])" wire:navigate class="text-sm">Ver fila completa</flux:link>
            </div>
            <x-chamado.tabela :chamados="$this->fila" :mostrar-requerente="true" vazio="Nenhum chamado aguardando atendimento. 🎉" />
        </section>
    @endif

    <section class="flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <flux:heading size="lg" level="2">Meus chamados recentes</flux:heading>
            <flux:link :href="route('chamados.index', ['escopo' => 'meus', 'status' => 'todos'])" wire:navigate class="text-sm">Ver todos</flux:link>
        </div>
        <x-chamado.tabela :chamados="$this->meusRecentes" vazio="Você ainda não abriu nenhum chamado." />
    </section>
</div>
