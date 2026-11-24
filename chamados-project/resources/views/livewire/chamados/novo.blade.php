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
                       class="group rounded-xl border border-zinc-200 bg-white p-4 transition-colors hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600 dark:hover:bg-zinc-800/60">
                        <div class="flex items-start gap-3">
                            <x-icone-secao icone="document-text" cor="blue" />
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
