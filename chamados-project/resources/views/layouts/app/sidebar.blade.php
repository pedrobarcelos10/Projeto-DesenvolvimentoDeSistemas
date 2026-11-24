<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 dark:bg-zinc-800">
        {{--
            Sidebar colapsável: no desktop o botão do cabeçalho recolhe a barra para
            só os ícones (cada item mostra o nome numa dica ao passar o mouse); no
            celular ela vira uma gaveta. O Flux lembra a escolha entre as páginas.
        --}}
        <flux:sidebar sticky collapsible class="border-e border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('painel') }}" wire:navigate />
                <flux:sidebar.collapse
                    tooltip="Recolher ou expandir menu"
                    class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-me-2"
                />
            </flux:sidebar.header>

            {{-- Os itens ficam soltos (sem grupo com título) para continuarem visíveis com a barra recolhida. --}}
            <flux:sidebar.nav>
                <div class="px-3 pb-1 text-sm font-medium text-zinc-400 in-data-flux-sidebar-collapsed-desktop:hidden">Chamados</div>

                <flux:sidebar.item icon="home" :href="route('painel')" :current="request()->routeIs('painel')" wire:navigate>
                    Painel
                </flux:sidebar.item>
                <flux:sidebar.item icon="plus-circle" :href="route('chamados.novo')" :current="request()->routeIs('chamados.novo')" wire:navigate>
                    Abrir chamado
                </flux:sidebar.item>
                <flux:sidebar.item icon="inbox-stack" :href="route('chamados.index')" :current="request()->routeIs('chamados.index', 'chamados.exibir') && request('escopo') !== 'fila'" wire:navigate>
                    Chamados
                </flux:sidebar.item>
                @if (auth()->user()->fazAtendimento())
                    <flux:sidebar.item icon="queue-list" :href="route('chamados.index', ['escopo' => 'fila'])" :current="request()->routeIs('chamados.index') && request('escopo') === 'fila'" wire:navigate>
                        Fila de atendimento
                    </flux:sidebar.item>
                @endif

                @can('administrar')
                    {{-- Grupo expansível com ícone: com a barra recolhida vira um submenu flutuante. --}}
                    <flux:sidebar.group
                        expandable
                        :expanded="request()->routeIs('admin.*')"
                        icon="cog-6-tooth"
                        heading="Administração"
                        class="mt-4 grid in-data-flux-sidebar-collapsed-desktop:mt-0"
                    >
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

            <x-seletor-aparencia />

            <div class="mx-1 flex flex-col gap-1.5 rounded-lg border border-zinc-200 px-3 py-2.5 in-data-flux-sidebar-collapsed-desktop:hidden dark:border-zinc-700">
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
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
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

        {{-- Liga as transições da sidebar só depois da página pronta (ver resources/css/app.css). --}}
        <script data-navigate-once>
            (() => {
                const raiz = document.documentElement;
                const ligar = () => requestAnimationFrame(() => requestAnimationFrame(() => raiz.setAttribute('data-animar-sidebar', '')));
                document.addEventListener('livewire:navigating', () => raiz.removeAttribute('data-animar-sidebar'));
                document.addEventListener('livewire:navigated', ligar);
                window.addEventListener('load', ligar);
            })();
        </script>

        @fluxScripts
    </body>
</html>
