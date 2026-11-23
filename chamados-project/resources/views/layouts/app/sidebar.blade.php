<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('painel') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <div class="px-1">
                <flux:button variant="primary" icon="plus" :href="route('chamados.novo')" wire:navigate class="w-full">
                    Abrir chamado
                </flux:button>
            </div>

            <flux:sidebar.nav>
                <flux:sidebar.group heading="Chamados" class="grid">
                    <flux:sidebar.item icon="home" :href="route('painel')" :current="request()->routeIs('painel')" wire:navigate>
                        Painel
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="inbox-stack" :href="route('chamados.index')" :current="request()->routeIs('chamados.index', 'chamados.exibir') && request('escopo') !== 'fila'" wire:navigate>
                        Chamados
                    </flux:sidebar.item>
                    @if (auth()->user()->fazAtendimento())
                        <flux:sidebar.item icon="queue-list" :href="route('chamados.index', ['escopo' => 'fila'])" :current="request()->routeIs('chamados.index') && request('escopo') === 'fila'" wire:navigate>
                            Fila de atendimento
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>

                @can('administrar')
                    <flux:sidebar.group heading="Administração" class="grid">
                        <flux:sidebar.item icon="users" :href="route('admin.usuarios.index')" :current="request()->routeIs('admin.usuarios.*')" wire:navigate>
                            Usuários
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="building-office-2" :href="route('admin.estrutura')" :current="request()->routeIs('admin.estrutura')" wire:navigate>
                            Áreas e seções
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="tag" :href="route('admin.categorias')" :current="request()->routeIs('admin.categorias')" wire:navigate>
                            Categorias
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="document-text" :href="route('admin.formularios.index')" :current="request()->routeIs('admin.formularios.*')" wire:navigate>
                            Formulários
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endcan
            </flux:sidebar.nav>

            <flux:spacer />

            <div class="mx-1 flex flex-col gap-1.5 rounded-lg bg-indigo-50 px-3 py-2.5 dark:bg-indigo-500/10">
                <flux:badge size="sm" :color="auth()->user()->perfil->cor()" :icon="auth()->user()->perfil->icone()" class="self-start">
                    {{ auth()->user()->perfil->rotulo() }}
                </flux:badge>
                @if (auth()->user()->secao)
                    <flux:text size="sm" class="truncate">{{ auth()->user()->secao->area->sigla ?? auth()->user()->secao->area->nome }} · {{ auth()->user()->secao->nome }}</flux:text>
                @endif
            </div>

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="border-b border-zinc-200 bg-white lg:hidden dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <x-app-logo href="{{ route('painel') }}" wire:navigate class="ms-2" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    avatar:color="indigo"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                    color="indigo"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
