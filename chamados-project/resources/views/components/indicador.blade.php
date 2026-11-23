{{--
    Cartão de indicador com ícone colorido.
    Uso: <x-indicador rotulo="Ativos" :valor="10" icone="users" cor="indigo" href="..." />
--}}
@props(['rotulo', 'valor', 'icone', 'cor' => 'indigo', 'href' => null])

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
    $barras = [
        'indigo' => 'bg-indigo-500', 'violet' => 'bg-violet-500', 'sky' => 'bg-sky-500',
        'emerald' => 'bg-emerald-500', 'amber' => 'bg-amber-500', 'rose' => 'bg-rose-500', 'teal' => 'bg-teal-500',
    ];
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" wire:navigate @endif
    {{ $attributes->class([
        'group relative block overflow-hidden rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-700 dark:bg-zinc-900',
        'transition hover:-translate-y-0.5 hover:shadow-md' => $href,
    ]) }}>
    <span class="absolute inset-x-0 top-0 h-1 {{ $barras[$cor] ?? $barras['indigo'] }}"></span>
    <div class="flex items-start justify-between gap-3">
        <flux:text size="sm" class="font-medium">{{ $rotulo }}</flux:text>
        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg {{ $tons[$cor] ?? $tons['indigo'] }}">
            <flux:icon :name="$icone" variant="mini" />
        </span>
    </div>
    <div class="mt-1 text-3xl font-semibold tabular-nums text-zinc-900 dark:text-white">{{ $valor }}</div>
</{{ $tag }}>
