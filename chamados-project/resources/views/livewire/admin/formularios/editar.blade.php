<div class="flex flex-col gap-6">
    <x-admin.cabecalho
        :titulo="$formularioId ? 'Editar formulário' : 'Novo formulário'"
        descricao="Defina para onde o chamado vai e quais informações o requerente precisa preencher."
        :voltar="route('admin.formularios.index')"
        voltar-rotulo="Voltar para formulários"
    >
        @if ($formularioId && $ativo)
            <flux:button variant="ghost" icon="eye" :href="route('chamados.novo', ['formulario' => $formularioId])" wire:navigate>Ver como requerente</flux:button>
        @endif
    </x-admin.cabecalho>

    <form wire:submit="salvar" class="grid gap-6 xl:grid-cols-3">
        <div class="flex flex-col gap-4 xl:col-span-2">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">Campos</flux:heading>
                <flux:text size="sm">{{ trans_choice('{0} Nenhum campo|{1} 1 campo|[2,*] :count campos', count($campos)) }}</flux:text>
            </div>

            @forelse ($campos as $indice => $campo)
                @php $temOpcoes = \App\Enums\TipoCampo::tryFrom($campo['tipo'])?->possuiOpcoes() ?? false; @endphp
                <flux:card wire:key="campo-{{ $campo['uid'] }}" class="flex flex-col gap-4">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="flex size-7 items-center justify-center rounded-full bg-zinc-100 text-xs font-semibold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">{{ $indice + 1 }}</span>
                            @if ($campo['chave'])
                                <flux:badge size="sm" color="zinc" class="font-mono">{{ $campo['chave'] }}</flux:badge>
                            @endif
                        </div>
                        <div class="flex gap-1">
                            <flux:button size="xs" variant="ghost" icon="arrow-up" wire:click="moverCampo({{ $indice }}, -1)" :disabled="$loop->first" aria-label="Mover para cima" />
                            <flux:button size="xs" variant="ghost" icon="arrow-down" wire:click="moverCampo({{ $indice }}, 1)" :disabled="$loop->last" aria-label="Mover para baixo" />
                            <flux:button size="xs" variant="ghost" color="red" icon="trash" wire:click="removerCampo({{ $indice }})" aria-label="Remover campo" />
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <flux:input wire:model="campos.{{ $indice }}.rotulo" label="Pergunta" placeholder="Ex.: Número do patrimônio" class="sm:col-span-2" />
                        <flux:select wire:model.live="campos.{{ $indice }}.tipo" label="Tipo de resposta">
                            @foreach (\App\Enums\TipoCampo::cases() as $tipo)
                                <flux:select.option :value="$tipo->value">{{ $tipo->rotulo() }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>

                    @if ($temOpcoes)
                        <flux:textarea wire:model="campos.{{ $indice }}.opcoes" label="Opções" description="Uma opção por linha." rows="3" />
                    @endif

                    <div class="grid items-end gap-4 sm:grid-cols-3">
                        <flux:input wire:model="campos.{{ $indice }}.texto_ajuda" label="Texto de ajuda" badge="Opcional" class="sm:col-span-2" />
                        <flux:switch wire:model="campos.{{ $indice }}.obrigatorio" label="Obrigatório" align="left" />
                    </div>
                </flux:card>
            @empty
                <x-vazio icone="list-bullet" texto="Este formulário ainda não tem campos. Sem campos, o requerente preenche apenas título e descrição." />
            @endforelse

            <div>
                <flux:button icon="plus" wire:click="adicionarCampo">Adicionar campo</flux:button>
            </div>
        </div>

        <div class="flex flex-col gap-6">
            <flux:card class="flex flex-col gap-5 xl:sticky xl:top-6">
                <div class="flex items-center gap-3">
                    <x-icone-secao icone="document-text" />
                    <flux:heading size="lg">Dados gerais</flux:heading>
                </div>

                <flux:input wire:model="nome" label="Nome do formulário" placeholder="Ex.: Acesso para novo colaborador" required />
                <flux:textarea wire:model="descricao" label="Descrição" badge="Opcional" rows="2" description="Aparece no atalho da tela de abertura." />

                <flux:select wire:model="categoriaId" label="Categoria" placeholder="Selecione a categoria" description="Define a área que receberá o chamado.">
                    @foreach ($this->categorias as $categoria)
                        <flux:select.option :value="$categoria->id">{{ $categoria->area->sigla ?? $categoria->area->nome }} · {{ $categoria->nomeCompleto() }}</flux:select.option>
                    @endforeach
                </flux:select>

                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-1 2xl:grid-cols-2">
                    <flux:select wire:model="tipoChamado" label="Tipo">
                        @foreach (\App\Enums\TipoChamado::cases() as $tipo)
                            <flux:select.option :value="$tipo->value">{{ $tipo->rotulo() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="prioridadePadrao" label="Prioridade padrão">
                        @foreach (\App\Enums\PrioridadeChamado::cases() as $prioridade)
                            <flux:select.option :value="$prioridade->value">{{ $prioridade->rotulo() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:switch wire:model="ativo" label="Formulário ativo" description="Formulários inativos não aparecem na abertura de chamados." align="left" />

                <flux:separator variant="subtle" />

                <div class="flex gap-2">
                    <flux:button variant="ghost" :href="route('admin.formularios.index')" wire:navigate class="flex-1">Cancelar</flux:button>
                    <flux:button type="submit" variant="primary" icon="check" class="flex-1">Salvar</flux:button>
                </div>
            </flux:card>
        </div>
    </form>
</div>
