@php $perfilAtual = \App\Enums\PerfilUsuario::tryFrom($perfil); @endphp

<div class="flex flex-col gap-6">
    <x-admin.cabecalho
        :titulo="$this->editando() ? 'Editar usuário' : 'Novo usuário'"
        :descricao="$this->editando() ? 'Altere os dados, o perfil, a lotação ou o acesso de '.$name.'.' : 'Somente administradores podem criar contas. A pessoa entra com o e-mail e a senha definidos aqui.'"
        :voltar="route('admin.usuarios.index')"
        voltar-rotulo="Voltar para usuários"
    />

    <form wire:submit="salvar" class="grid gap-6 xl:grid-cols-3">
        <div class="flex flex-col gap-6 xl:col-span-2">
            <flux:card class="flex flex-col gap-5">
                <div class="flex items-center gap-3">
                    <x-icone-secao icone="identification" cor="indigo" />
                    <flux:heading size="lg">Dados pessoais</flux:heading>
                </div>
                <flux:input wire:model="name" label="Nome completo" required autocomplete="off" />
                <flux:input wire:model="email" label="E-mail" type="email" required autocomplete="off" placeholder="nome@organizacao.gov.br" />
            </flux:card>

            <flux:card class="flex flex-col gap-5">
                <div class="flex items-center gap-3">
                    <x-icone-secao icone="key" cor="amber" />
                    <div>
                        <flux:heading size="lg">{{ $this->editando() ? 'Redefinir senha' : 'Senha inicial' }}</flux:heading>
                        <flux:text size="sm">
                            {{ $this->editando() ? 'Deixe em branco para manter a senha atual.' : 'Informe a senha à pessoa e peça que ela a troque em Configurações > Segurança.' }}
                        </flux:text>
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input wire:model="password" label="Senha" type="password" viewable autocomplete="new-password" :required="! $this->editando()" />
                    <flux:input wire:model="password_confirmation" label="Confirmar senha" type="password" viewable autocomplete="new-password" :required="! $this->editando()" />
                </div>
            </flux:card>
        </div>

        <div class="flex flex-col gap-6">
            <flux:card class="flex flex-col gap-5">
                <div class="flex items-center gap-3">
                    <x-icone-secao icone="shield-check" cor="violet" />
                    <flux:heading size="lg">Acesso</flux:heading>
                </div>

                <flux:radio.group wire:model.live="perfil" label="Perfil" variant="cards" class="flex-col" :disabled="$this->editandoASiMesmo()">
                    @foreach (\App\Enums\PerfilUsuario::cases() as $opcao)
                        <flux:radio :value="$opcao->value" :icon="$opcao->icone()" :label="$opcao->rotulo()" :description="$opcao->descricao()" />
                    @endforeach
                </flux:radio.group>

                @if ($this->editandoASiMesmo())
                    <flux:text size="sm">Você não pode alterar o seu próprio perfil nem se desativar.</flux:text>
                @endif

                <flux:select wire:model="secaoId" label="Lotação (área · seção)" :badge="$perfil === 'atendente' ? 'Obrigatória' : 'Opcional'"
                    :description="$perfil === 'atendente' ? 'O atendente atende os chamados da área desta seção.' : null">
                    <flux:select.option value="">Sem lotação</flux:select.option>
                    @foreach ($this->secoesPorArea as $area => $secoes)
                        @foreach ($secoes as $secao)
                            <flux:select.option :value="$secao->id">{{ $area }} · {{ $secao->nome }}</flux:select.option>
                        @endforeach
                    @endforeach
                </flux:select>

                <flux:separator variant="subtle" />

                <flux:switch wire:model="ativo" label="Conta ativa" description="Contas inativas não conseguem entrar no sistema." align="left" :disabled="$this->editandoASiMesmo()" />
            </flux:card>

            <div class="flex gap-2">
                <flux:button variant="ghost" :href="route('admin.usuarios.index')" wire:navigate class="flex-1">Cancelar</flux:button>
                <flux:button type="submit" variant="primary" icon="check" class="flex-1">
                    {{ $this->editando() ? 'Salvar alterações' : 'Criar usuário' }}
                </flux:button>
            </div>
        </div>
    </form>
</div>
