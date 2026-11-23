{{--
    Cabeçalho padrão das telas. O slot recebe os botões de ação.
    Uso: <x-admin.cabecalho titulo="Usuários" descricao="..."> <flux:button .../> </x-admin.cabecalho>
--}}
@props(['titulo', 'descricao' => null, 'voltar' => null, 'voltarRotulo' => 'Voltar'])

<div class="flex flex-col gap-2">
    @if ($voltar)
        <flux:link :href="$voltar" wire:navigate variant="subtle" class="text-sm">
            <flux:icon.arrow-left variant="micro" class="inline" /> {{ $voltarRotulo }}
        </flux:link>
    @endif

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ $titulo }}</flux:heading>
            @if ($descricao)
                <flux:text class="mt-1">{{ $descricao }}</flux:text>
            @endif
        </div>

        @if ($slot->isNotEmpty())
            <div class="flex flex-wrap gap-2">{{ $slot }}</div>
        @endif
    </div>
</div>
