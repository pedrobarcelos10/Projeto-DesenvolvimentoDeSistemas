{{--
    Cartão de indicador: fundo neutro, número em destaque e só o ícone colorido.
    Uso: <x-indicador rotulo="Ativos" :valor="10" icone="users" cor="emerald" href="..." />
--}}
@props(['rotulo', 'valor', 'icone', 'cor' => 'zinc', 'href' => null])

@php $tag = $href ? 'a' : 'div'; @endphp

<{{ $tag }} @if ($href) href="{{ $href }}" wire:navigate @endif
    {{ $attributes->class([
        'block rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900',
        'transition-colors hover:border-zinc-300 hover:bg-zinc-50 dark:hover:border-zinc-600 dark:hover:bg-zinc-800/60' => $href,
    ]) }}>
    <div class="flex items-start justify-between gap-3">
        <flux:text size="sm" class="font-medium">{{ $rotulo }}</flux:text>
        <x-icone-secao :icone="$icone" :cor="$cor" />
    </div>
    <div class="mt-1 text-3xl font-semibold tabular-nums text-zinc-900 dark:text-white">{{ $valor }}</div>
</{{ $tag }}>
