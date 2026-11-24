{{--
    Ícone em quadrado neutro para títulos de cartões; a cor fica só no traço do ícone.
    Uso: <x-icone-secao icone="key" cor="amber" />
--}}
@props(['icone', 'cor' => 'zinc'])

@php
    $tons = [
        'zinc' => 'text-zinc-500 dark:text-zinc-400',
        'blue' => 'text-blue-600 dark:text-blue-400',
        'sky' => 'text-sky-600 dark:text-sky-400',
        'emerald' => 'text-emerald-600 dark:text-emerald-400',
        'amber' => 'text-amber-600 dark:text-amber-400',
        'rose' => 'text-rose-600 dark:text-rose-400',
    ];
@endphp

<span {{ $attributes->class(['flex size-9 shrink-0 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800', $tons[$cor] ?? $tons['zinc']]) }}>
    <flux:icon :name="$icone" variant="mini" />
</span>
