<div class="flex flex-col gap-6">
    <x-admin.cabecalho titulo="Categorias" descricao="Os tipos de solicitação do catálogo. Cada categoria envia o chamado para uma área e, se quiser, para uma seção padrão.">
        <flux:button variant="primary" icon="plus" wire:click="nova">Nova categoria</flux:button>
    </x-admin.cabecalho>

    <div class="max-w-xs">
        <flux:select wire:model.live="filtroArea">
            <flux:select.option value="">Todas as áreas</flux:select.option>
            @foreach ($this->areas as $area)
                <flux:select.option :value="$area->id">{{ $area->nome }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    @if ($this->categorias->isEmpty())
        <x-vazio icone="tag" texto="Nenhuma categoria cadastrada.">
            <flux:button variant="primary" icon="plus" wire:click="nova">Cadastrar categoria</flux:button>
        </x-vazio>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Categoria</flux:table.column>
                <flux:table.column>Destino</flux:table.column>
                <flux:table.column class="text-center">Formulários</flux:table.column>
                <flux:table.column class="text-center">Chamados</flux:table.column>
                <flux:table.column>Situação</flux:table.column>
                <flux:table.column class="w-0"><span class="sr-only">Ações</span></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->categorias as $categoria)
                    @php $sub = $categoria->categoria_pai_id !== null; @endphp
                    <flux:table.row :key="$categoria->id" @class(['opacity-60' => ! $categoria->ativo])>
                        <flux:table.cell>
                            <div @class(['flex items-center gap-2', 'ps-7' => $sub])>
                                @if ($sub)
                                    <flux:icon.arrow-turn-down-right variant="micro" class="text-zinc-400" />
                                @else
                                    <flux:icon.folder variant="mini" class="text-indigo-500" />
                                @endif
                                <div class="min-w-0">
                                    <div @class(['truncate text-zinc-800 dark:text-white', 'font-semibold' => ! $sub, 'font-medium' => $sub])>{{ $categoria->nome }}</div>
                                    @if ($categoria->descricao)
                                        <flux:text size="sm" class="max-w-xs truncate">{{ $categoria->descricao }}</flux:text>
                                    @endif
                                </div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <span class="font-medium">{{ $categoria->area->sigla ?? $categoria->area->nome }}</span>
                            @if ($categoria->secao)
                                <flux:text size="sm" class="inline">· {{ $categoria->secao->nome }}</flux:text>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="text-center tabular-nums">{{ $categoria->formularios_count }}</flux:table.cell>
                        <flux:table.cell class="text-center tabular-nums">{{ $categoria->chamados_count }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($categoria->ativo)
                                <flux:badge size="sm" color="emerald" icon="check-circle">Ativa</flux:badge>
                            @else
                                <flux:badge size="sm" color="zinc" icon="no-symbol">Inativa</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:dropdown align="end">
                                <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" aria-label="Ações" />
                                <flux:menu>
                                    <flux:menu.item icon="pencil-square" wire:click="editar({{ $categoria->id }})">Editar</flux:menu.item>
                                    @unless ($sub)
                                        <flux:menu.item icon="plus" wire:click="nova({{ $categoria->id }})">Adicionar subcategoria</flux:menu.item>
                                    @endunless
                                    <flux:menu.item :icon="$categoria->ativo ? 'no-symbol' : 'arrow-path'" wire:click="alternarAtivo({{ $categoria->id }})">
                                        {{ $categoria->ativo ? 'Desativar' : 'Ativar' }}
                                    </flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item icon="trash" variant="danger" wire:click="excluir({{ $categoria->id }})"
                                        wire:confirm="Excluir a categoria {{ $categoria->nome }}?">
                                        Excluir
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif

    <flux:modal name="categoria" class="md:w-[32rem]">
        <form wire:submit="salvar" class="flex flex-col gap-5">
            <flux:heading size="lg">{{ $categoriaId ? 'Editar categoria' : 'Nova categoria' }}</flux:heading>

            <flux:input wire:model="nome" label="Nome" required />
            <flux:textarea wire:model="descricao" label="Descrição" badge="Opcional" rows="2" />

            <flux:select wire:model.live="areaId" label="Área responsável" placeholder="Selecione a área">
                @foreach ($this->areas as $area)
                    <flux:select.option :value="$area->id">{{ $area->nome }}{{ $area->recebe_chamados ? '' : ' (não recebe chamados)' }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model="secaoId" label="Seção padrão" badge="Opcional" :disabled="$areaId === ''">
                    <flux:select.option value="">Nenhuma</flux:select.option>
                    @foreach ($this->secoesDaArea as $secao)
                        <flux:select.option :value="$secao->id">{{ $secao->nome }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="categoriaPaiId" label="Categoria pai" badge="Opcional" :disabled="$areaId === '' || $this->temSubcategorias">
                    <flux:select.option value="">Nenhuma (principal)</flux:select.option>
                    @foreach ($this->paisPossiveis as $pai)
                        <flux:select.option :value="$pai->id">{{ $pai->nome }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <flux:switch wire:model="ativo" label="Categoria ativa" description="Categorias inativas não aparecem na abertura de chamados." align="left" />

            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Cancelar</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check">Salvar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
