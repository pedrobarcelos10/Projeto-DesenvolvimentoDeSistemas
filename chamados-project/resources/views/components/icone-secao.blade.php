{{-- Ícone em quadrado colorido para títulos de cartões. Uso: <x-icone-secao icone="key" cor="amber" /> --}}
@props(['icone', 'cor' => 'indigo'])

@php
    $tons = [
        'indigo' => 'bg-indigo-100 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-300',
        'violet' => 'bg-violet-100 text-violet-600 dark:bg-violet-500/15 dark:text-violet-300',
        'sky' => 'bg-sky-100 text-sky-600 dark:bg-sky-500/15 dark:text-sky-300',
        'emerald' => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-300',
        'amber' => 'bg-amber-100 text-amber-600 dark:bg-amber-500/15 dark:text-amber-300',
        'rose' => 'bg-rose-100 text-rose-600 dark:bg-rose-500/15 dark:text-rose-300',
        'teal' => 'bg-teal-100 text-teal-600 dark:bg-teal-500/15 dark:text-teal-300',
    ];
@endphp

<span {{ $attributes->class(['flex size-9 shrink-0 items-center justify-center rounded-lg', $tons[$cor] ?? $tons['indigo']]) }}>
    <flux:icon :name="$icone" variant="mini" />
</span>
