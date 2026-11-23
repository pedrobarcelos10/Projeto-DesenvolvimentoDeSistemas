<div class="flex flex-col gap-6">
    <x-admin.cabecalho titulo="Formulários" descricao="Monte formulários com campos próprios para as solicitações mais comuns. Eles aparecem como atalhos na abertura de chamados.">
        <flux:button variant="primary" icon="plus" :href="route('admin.formularios.novo')" wire:navigate>Novo formulário</flux:button>
    </x-admin.cabecalho>

    @if ($this->formularios->isEmpty())
        <x-vazio icone="document-text" texto="Nenhum formulário cadastrado.">
            <flux:button variant="primary" icon="plus" :href="route('admin.formularios.novo')" wire:navigate>Criar formulário</flux:button>
        </x-vazio>
    @else
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($this->formularios as $formulario)
                <flux:card wire:key="form-{{ $formulario->id }}" @class(['flex flex-col gap-4', 'opacity-60' => ! $formulario->ativo])>
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <x-icone-secao icone="document-text" :cor="$formulario->tipo_chamado === \App\Enums\TipoChamado::Incidente ? 'rose' : 'indigo'" />
                            <div class="min-w-0">
                                <flux:heading class="truncate">{{ $formulario->nome }}</flux:heading>
                                <flux:text size="sm" class="truncate">{{ $formulario->categoria->area->sigla ?? $formulario->categoria->area->nome }} · {{ $formulario->categoria->nomeCompleto() }}</flux:text>
                            </div>
                        </div>
                        <flux:dropdown align="end">
                            <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" aria-label="Ações" />
                            <flux:menu>
                                <flux:menu.item icon="pencil-square" :href="route('admin.formularios.editar', $formulario)" wire:navigate>Editar</flux:menu.item>
                                <flux:menu.item :icon="$formulario->ativo ? 'no-symbol' : 'arrow-path'" wire:click="alternarAtivo({{ $formulario->id }})">
                                    {{ $formulario->ativo ? 'Desativar' : 'Ativar' }}
                                </flux:menu.item>
                                <flux:menu.separator />
                                <flux:menu.item icon="trash" variant="danger" wire:click="excluir({{ $formulario->id }})"
                                    wire:confirm="Excluir o formulário {{ $formulario->nome }}?">
                                    Excluir
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </div>

                    @if ($formulario->descricao)
                        <flux:text size="sm" class="line-clamp-2">{{ $formulario->descricao }}</flux:text>
                    @endif

                    <div class="mt-auto flex flex-wrap items-center gap-1.5">
                        <flux:badge size="sm" :color="$formulario->tipo_chamado === \App\Enums\TipoChamado::Incidente ? 'rose' : 'indigo'">{{ $formulario->tipo_chamado->rotulo() }}</flux:badge>
                        <x-chamado.prioridade :prioridade="$formulario->prioridade_padrao" />
                        <flux:badge size="sm" color="zinc" icon="list-bullet">{{ trans_choice('{0} Sem campos|{1} 1 campo|[2,*] :count campos', $formulario->campos_count) }}</flux:badge>
                        @unless ($formulario->ativo)
                            <flux:badge size="sm" color="zinc" icon="no-symbol">Inativo</flux:badge>
                        @endunless
                    </div>

                    <div class="flex items-center justify-between border-t border-zinc-100 pt-3 dark:border-zinc-800">
                        <flux:text size="sm">{{ trans_choice('{0} Nenhum chamado|{1} 1 chamado aberto|[2,*] :count chamados abertos', $formulario->chamados_count) }}</flux:text>
                        <flux:button size="sm" variant="filled" color="indigo" icon="pencil-square" :href="route('admin.formularios.editar', $formulario)" wire:navigate>Editar</flux:button>
                    </div>
                </flux:card>
            @endforeach
        </div>
    @endif
</div>
