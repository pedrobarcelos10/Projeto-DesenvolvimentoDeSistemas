<div class="flex flex-col gap-6">
    <x-admin.cabecalho titulo="Usuários" descricao="Crie contas, defina perfis e lotação e controle quem tem acesso ao sistema.">
        <flux:button variant="primary" icon="user-plus" :href="route('admin.usuarios.novo')" wire:navigate>
            Novo usuário
        </flux:button>
    </x-admin.cabecalho>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-indicador rotulo="Usuários cadastrados" :valor="$this->resumo['total']" icone="users" />
        <x-indicador rotulo="Ativos" :valor="$this->resumo['ativos']" icone="check-circle" cor="emerald" />
        <x-indicador rotulo="Atendentes ativos" :valor="$this->resumo['atendentes']" icone="lifebuoy" cor="sky" />
        <x-indicador rotulo="Administradores ativos" :valor="$this->resumo['administradores']" icone="shield-check" cor="amber" />
    </div>

    <div class="grid gap-3 sm:grid-cols-3">
        <flux:input wire:model.live.debounce.400ms="busca" icon="magnifying-glass" placeholder="Buscar por nome ou e-mail" clearable />

        <flux:select wire:model.live="perfil">
            <flux:select.option value="">Todos os perfis</flux:select.option>
            @foreach (\App\Enums\PerfilUsuario::cases() as $opcao)
                <flux:select.option :value="$opcao->value">{{ $opcao->rotulo() }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="situacao">
            <flux:select.option value="todos">Ativos e inativos</flux:select.option>
            <flux:select.option value="ativos">Somente ativos</flux:select.option>
            <flux:select.option value="inativos">Somente inativos</flux:select.option>
        </flux:select>
    </div>

    @if ($this->usuarios->isEmpty())
        <x-vazio icone="users" texto="Nenhum usuário encontrado com esses filtros." />
    @else
        <flux:table :paginate="$this->usuarios">
            <flux:table.columns>
                <flux:table.column>Usuário</flux:table.column>
                <flux:table.column>Perfil</flux:table.column>
                <flux:table.column>Lotação</flux:table.column>
                <flux:table.column>Situação</flux:table.column>
                <flux:table.column class="w-0"><span class="sr-only">Ações</span></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->usuarios as $usuario)
                    <flux:table.row :key="$usuario->id" @class(['opacity-60' => ! $usuario->ativo])>
                        <flux:table.cell>
                            <div class="flex items-center gap-3">
                                <flux:avatar size="sm" :name="$usuario->name" :initials="$usuario->initials()" color="auto" />
                                <div class="min-w-0">
                                    <div class="truncate font-medium text-zinc-800 dark:text-white">
                                        {{ $usuario->name }}
                                        @if ($usuario->is(auth()->user()))
                                            <flux:badge size="sm" color="zinc" class="ms-1">Você</flux:badge>
                                        @endif
                                    </div>
                                    <flux:text size="sm" class="truncate">{{ $usuario->email }}</flux:text>
                                </div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$usuario->perfil->cor()" :icon="$usuario->perfil->icone()">{{ $usuario->perfil->rotulo() }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($usuario->secao)
                                <span class="font-medium">{{ $usuario->secao->area->sigla ?? $usuario->secao->area->nome }}</span>
                                <flux:text size="sm" class="inline">· {{ $usuario->secao->nome }}</flux:text>
                            @else
                                <flux:text size="sm" class="italic">Sem lotação</flux:text>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($usuario->ativo)
                                <flux:badge size="sm" color="emerald" icon="check-circle">Ativo</flux:badge>
                            @else
                                <flux:badge size="sm" color="zinc" icon="no-symbol">Inativo</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex justify-end gap-1">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('admin.usuarios.editar', $usuario)" wire:navigate>
                                    Editar
                                </flux:button>
                                @unless ($usuario->is(auth()->user()))
                                    @if ($usuario->ativo)
                                        <flux:button size="sm" variant="ghost" icon="no-symbol" wire:click="confirmarAtivacao({{ $usuario->id }})">
                                            Desativar
                                        </flux:button>
                                    @else
                                        <flux:button size="sm" variant="ghost" icon="arrow-path" wire:click="confirmarAtivacao({{ $usuario->id }})">
                                            Ativar
                                        </flux:button>
                                    @endif
                                @endunless
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif

    <flux:modal name="confirmar-ativacao" class="md:w-[28rem]">
        @if ($usuario = $this->emConfirmacao)
            <div class="flex flex-col gap-4">
                <div>
                    <flux:heading size="lg">{{ $usuario->ativo ? 'Desativar' : 'Ativar' }} {{ $usuario->name }}?</flux:heading>
                    <flux:text class="mt-2">
                        @if ($usuario->ativo)
                            A pessoa perde o acesso imediatamente e não consegue mais entrar no sistema.
                            Os chamados e o histórico dela são preservados.
                        @else
                            A pessoa volta a acessar o sistema com o perfil {{ mb_strtolower($usuario->perfil->rotulo()) }}.
                        @endif
                    </flux:text>
                </div>
                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">Voltar</flux:button>
                    </flux:modal.close>
                    <flux:button :variant="$usuario->ativo ? 'danger' : 'primary'" wire:click="alternarAtivacao">
                        {{ $usuario->ativo ? 'Desativar' : 'Ativar' }}
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
