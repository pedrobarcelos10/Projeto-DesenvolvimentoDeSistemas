{{--
    Seletor de aparência para a sidebar. Usa o estado do Flux ($flux.appearance),
    o mesmo da tela de configurações, salvo no navegador.
    - Sidebar aberta: controle segmentado Claro / Escuro / Sistema.
    - Sidebar recolhida: um único botão que alterna entre claro e escuro.
    Uso: <x-seletor-aparencia />
--}}
<div {{ $attributes->class('px-1') }}>
    <div class="in-data-flux-sidebar-collapsed-desktop:hidden">
        <flux:radio.group x-data variant="segmented" x-model="$flux.appearance" size="sm" aria-label="Aparência" class="w-full">
            <flux:radio value="light" icon="sun" aria-label="Tema claro" title="Tema claro" class="flex-1" />
            <flux:radio value="dark" icon="moon" aria-label="Tema escuro" title="Tema escuro" class="flex-1" />
            <flux:radio value="system" icon="computer-desktop" aria-label="Seguir o sistema" title="Seguir o sistema" class="flex-1" />
        </flux:radio.group>
    </div>

    <div class="hidden in-data-flux-sidebar-collapsed-desktop:flex in-data-flux-sidebar-collapsed-desktop:justify-center">
        <flux:tooltip content="Alternar tema claro/escuro" position="right">
            <flux:button
                x-data
                x-on:click="$flux.dark = ! $flux.dark"
                variant="subtle"
                square
                aria-label="Alternar tema claro/escuro"
            >
                <flux:icon.sun variant="mini" x-show="$flux.dark" />
                <flux:icon.moon variant="mini" x-show="! $flux.dark" />
            </flux:button>
        </flux:tooltip>
    </div>
</div>
