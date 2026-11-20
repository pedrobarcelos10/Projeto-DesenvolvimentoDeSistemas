<?php

use App\Actions\Chamados\AbrirChamado;
use App\Enums\PrioridadeChamado;
use App\Enums\TipoCampo;
use App\Enums\TipoChamado;
use App\Exceptions\ChamadoException;
use App\Models\Categoria;
use App\Models\Chamado;
use App\Models\Formulario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Abrir chamado')] class extends Component {
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
}; ?>

<div class="mx-auto flex w-full max-w-3xl flex-col gap-6">
    <div>
        <flux:link :href="route('chamados.index')" wire:navigate variant="subtle" class="text-sm">
            <flux:icon.arrow-left variant="micro" class="inline" /> Voltar para chamados
        </flux:link>
        <flux:heading size="xl" level="1" class="mt-2">Abrir chamado</flux:heading>
        <flux:text class="mt-1">Descreva o que você precisa. A equipe responsável será avisada pela fila de atendimento.</flux:text>
    </div>

    @if ($this->formulario === null && $this->formularios->isNotEmpty())
        <section class="flex flex-col gap-3">
            <flux:heading size="lg" level="2">Solicitações frequentes</flux:heading>
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($this->formularios as $item)
                    <a href="{{ route('chamados.novo', ['formulario' => $item->id]) }}" wire:navigate
                       class="group rounded-lg border border-zinc-200 p-4 transition hover:border-accent hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">
                        <div class="flex items-start gap-3">
                            <flux:icon.document-text class="mt-0.5 size-5 text-zinc-400 group-hover:text-accent" />
                            <div>
                                <flux:heading>{{ $item->nome }}</flux:heading>
                                @if ($item->descricao)
                                    <flux:text size="sm" class="mt-1">{{ $item->descricao }}</flux:text>
                                @endif
                                <flux:text size="sm" class="mt-2 text-zinc-400">{{ $item->categoria->area->nome }}</flux:text>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>

        <flux:separator text="ou descreva sua solicitação" />
    @endif

    <form wire:submit="abrir" class="flex flex-col gap-6">
        @if ($formulario = $this->formulario)
            <flux:callout icon="document-text" color="sky">
                <flux:callout.heading>{{ $formulario->nome }}</flux:callout.heading>
                @if ($formulario->descricao)
                    <flux:callout.text>{{ $formulario->descricao }}</flux:callout.text>
                @endif
                <x-slot name="actions">
                    <flux:button size="sm" :href="route('chamados.novo')" wire:navigate>Escolher outro tipo</flux:button>
                </x-slot>
            </flux:callout>
        @else
            <flux:radio.group wire:model="tipo" label="O que está acontecendo?" variant="cards" class="max-sm:flex-col">
                @foreach (\App\Enums\TipoChamado::cases() as $opcao)
                    <flux:radio :value="$opcao->value" :label="$opcao->rotulo()" :description="$opcao->descricao()" />
                @endforeach
            </flux:radio.group>

            <flux:select wire:model="categoriaId" label="Categoria" placeholder="Selecione a categoria">
                @foreach ($this->categorias as $categoria)
                    <flux:select.option :value="$categoria->id">{{ $categoria->area->sigla }} · {{ $categoria->nomeCompleto() }}</flux:select.option>
                @endforeach
            </flux:select>
        @endif

        <flux:input wire:model="titulo" label="Título" placeholder="Resuma o problema em poucas palavras" maxlength="255" />

        @if ($formulario)
            @foreach ($formulario->campos as $campo)
                @php $modelo = 'respostas.'.$campo->chave; @endphp

                @switch($campo->tipo)
                    @case(\App\Enums\TipoCampo::TextoLongo)
                        <flux:textarea wire:model="{{ $modelo }}" :label="$campo->rotulo" :description="$campo->texto_ajuda" :badge="$campo->obrigatorio ? null : 'Opcional'" rows="3" />
                        @break
                    @case(\App\Enums\TipoCampo::Lista)
                        <flux:select wire:model="{{ $modelo }}" :label="$campo->rotulo" :description="$campo->texto_ajuda" :badge="$campo->obrigatorio ? null : 'Opcional'" placeholder="Selecione">
                            @foreach ($campo->opcoes ?? [] as $opcao)
                                <flux:select.option :value="$opcao">{{ $opcao }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        @break
                    @case(\App\Enums\TipoCampo::EscolhaUnica)
                        <flux:radio.group wire:model="{{ $modelo }}" :label="$campo->rotulo" :description="$campo->texto_ajuda">
                            @foreach ($campo->opcoes ?? [] as $opcao)
                                <flux:radio :value="$opcao" :label="$opcao" />
                            @endforeach
                        </flux:radio.group>
                        @break
                    @case(\App\Enums\TipoCampo::MultiplaEscolha)
                        <flux:checkbox.group wire:model="{{ $modelo }}" :label="$campo->rotulo" :description="$campo->texto_ajuda">
                            @foreach ($campo->opcoes ?? [] as $opcao)
                                <flux:checkbox :value="$opcao" :label="$opcao" />
                            @endforeach
                        </flux:checkbox.group>
                        @break
                    @default
                        <flux:input
                            wire:model="{{ $modelo }}"
                            :label="$campo->rotulo"
                            :description="$campo->texto_ajuda"
                            :badge="$campo->obrigatorio ? null : 'Opcional'"
                            :type="match ($campo->tipo) { \App\Enums\TipoCampo::Numero => 'number', \App\Enums\TipoCampo::Email => 'email', \App\Enums\TipoCampo::Data => 'date', default => 'text' }"
                        />
                @endswitch
            @endforeach
        @endif

        <flux:textarea
            wire:model="descricao"
            :label="$formulario ? 'Informações adicionais' : 'Descrição'"
            :badge="$formulario ? 'Opcional' : null"
            placeholder="O que aconteceu? Desde quando? Onde? Já tentou alguma coisa?"
            rows="5"
        />

        <flux:select wire:model="prioridade" label="Prioridade" class="max-w-xs">
            @foreach (\App\Enums\PrioridadeChamado::cases() as $opcao)
                <flux:select.option :value="$opcao->value">{{ $opcao->rotulo() }}</flux:select.option>
            @endforeach
        </flux:select>

        <div class="flex items-center justify-end gap-2">
            <flux:button variant="ghost" :href="route('chamados.index')" wire:navigate>Cancelar</flux:button>
            <flux:button type="submit" variant="primary" icon="paper-airplane">Abrir chamado</flux:button>
        </div>
    </form>
</div>
