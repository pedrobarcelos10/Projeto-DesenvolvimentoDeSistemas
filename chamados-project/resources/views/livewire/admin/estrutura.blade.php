<div class="flex flex-col gap-6">
    <x-admin.cabecalho titulo="Áreas e seções" descricao="Organize a estrutura da organização. Usuários são lotados em seções, e só áreas que recebem chamados aparecem como destino.">
        <flux:button variant="primary" icon="plus" wire:click="novaArea">Nova área</flux:button>
    </x-admin.cabecalho>

    @if ($this->areas->isEmpty())
        <x-vazio icone="building-office-2" texto="Nenhuma área cadastrada ainda.">
            <flux:button variant="primary" icon="plus" wire:click="novaArea">Cadastrar a primeira área</flux:button>
        </x-vazio>
    @endif

    <div class="grid gap-4 xl:grid-cols-2">
        @foreach ($this->areas as $area)
            <flux:card wire:key="area-{{ $area->id }}" @class(['flex flex-col gap-4', 'opacity-60' => ! $area->ativo])>
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <x-icone-secao icone="building-office-2" :cor="$area->recebe_chamados ? 'indigo' : 'teal'" />
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <flux:heading size="lg">{{ $area->nome }}</flux:heading>
                                @if ($area->sigla)
                                    <flux:badge size="sm" color="zinc">{{ $area->sigla }}</flux:badge>
                                @endif
                            </div>
                            <div class="mt-1 flex flex-wrap gap-1.5">
                                @if ($area->recebe_chamados)
                                    <flux:badge size="sm" color="indigo" icon="inbox-arrow-down">Recebe chamados</flux:badge>
                                @else
                                    <flux:badge size="sm" color="teal" icon="paper-airplane">Apenas solicita</flux:badge>
                                @endif
                                @unless ($area->ativo)
                                    <flux:badge size="sm" color="zinc" icon="no-symbol">Inativa</flux:badge>
                                @endunless
                            </div>
                        </div>
                    </div>

                    <flux:dropdown align="end">
                        <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" aria-label="Ações da área" />
                        <flux:menu>
                            <flux:menu.item icon="pencil-square" wire:click="editarArea({{ $area->id }})">Editar área</flux:menu.item>
                            <flux:menu.item icon="plus" wire:click="novaSecao({{ $area->id }})">Adicionar seção</flux:menu.item>
                            <flux:menu.separator />
                            <flux:menu.item icon="trash" variant="danger" wire:click="excluirArea({{ $area->id }})"
                                wire:confirm="Excluir a área {{ $area->nome }}? Essa ação não pode ser desfeita.">
                                Excluir área
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </div>

                @if ($area->descricao)
                    <flux:text size="sm">{{ $area->descricao }}</flux:text>
                @endif

                <div class="grid grid-cols-3 gap-2 text-center">
                    @foreach (['Seções' => $area->secoes->count(), 'Categorias' => $area->categorias_count, 'Chamados' => $area->chamados_count] as $rotulo => $valor)
                        <div class="rounded-lg bg-zinc-50 py-2 dark:bg-zinc-800/60">
                            <div class="text-lg font-semibold tabular-nums text-zinc-800 dark:text-white">{{ $valor }}</div>
                            <flux:text size="sm">{{ $rotulo }}</flux:text>
                        </div>
                    @endforeach
                </div>

                <div class="flex flex-col divide-y divide-zinc-100 rounded-lg border border-zinc-200 dark:divide-zinc-800 dark:border-zinc-700">
                    @forelse ($area->secoes as $secao)
                        <div wire:key="secao-{{ $secao->id }}" class="flex items-center justify-between gap-3 px-3 py-2">
                            <div class="min-w-0">
                                <div @class(['truncate text-sm font-medium text-zinc-800 dark:text-zinc-100', 'line-through opacity-60' => ! $secao->ativo])>
                                    {{ $secao->nome }}
                                </div>
                                <flux:text size="sm" class="truncate">
                                    {{ trans_choice('{0} Nenhum usuário|{1} 1 usuário|[2,*] :count usuários', $secao->usuarios_count) }}
                                    @if ($secao->descricao) · {{ $secao->descricao }} @endif
                                </flux:text>
                            </div>
                            <div class="flex shrink-0 gap-1">
                                <flux:button size="xs" variant="ghost" icon="pencil-square" wire:click="editarSecao({{ $secao->id }})" aria-label="Editar seção" />
                                <flux:button size="xs" variant="ghost" color="red" icon="trash" wire:click="excluirSecao({{ $secao->id }})"
                                    wire:confirm="Excluir a seção {{ $secao->nome }}?" aria-label="Excluir seção" />
                            </div>
                        </div>
                    @empty
                        <flux:text size="sm" class="px-3 py-3 italic">Nenhuma seção nesta área.</flux:text>
                    @endforelse
                </div>

                <div>
                    <flux:button size="sm" variant="filled" color="indigo" icon="plus" wire:click="novaSecao({{ $area->id }})">Adicionar seção</flux:button>
                </div>
            </flux:card>
        @endforeach
    </div>

    <flux:modal name="area" class="md:w-[32rem]">
        <form wire:submit="salvarArea" class="flex flex-col gap-5">
            <flux:heading size="lg">{{ $areaId ? 'Editar área' : 'Nova área' }}</flux:heading>

            <div class="grid gap-4 sm:grid-cols-3">
                <flux:input wire:model="areaNome" label="Nome" class="sm:col-span-2" required />
                <flux:input wire:model="areaSigla" label="Sigla" badge="Opcional" maxlength="20" />
            </div>
            <flux:textarea wire:model="areaDescricao" label="Descrição" badge="Opcional" rows="2" />
            <flux:switch wire:model="areaRecebeChamados" label="Recebe chamados" description="A área aparece como destino na abertura e pode ter atendentes." align="left" />
            <flux:switch wire:model="areaAtiva" label="Área ativa" description="Áreas inativas deixam de receber chamados." align="left" />

            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Cancelar</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check">Salvar</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="secao" class="md:w-[32rem]">
        <form wire:submit="salvarSecao" class="flex flex-col gap-5">
            <flux:heading size="lg">{{ $secaoId ? 'Editar seção' : 'Nova seção' }}</flux:heading>

            <flux:select wire:model="secaoAreaId" label="Área">
                @foreach ($this->areas as $area)
                    <flux:select.option :value="$area->id">{{ $area->nome }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="secaoNome" label="Nome" required />
            <flux:textarea wire:model="secaoDescricao" label="Descrição" badge="Opcional" rows="2" />
            <flux:switch wire:model="secaoAtiva" label="Seção ativa" description="Seções inativas não aparecem para novas lotações." align="left" />

            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Cancelar</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check">Salvar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
