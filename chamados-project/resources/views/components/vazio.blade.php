{{-- Estado vazio. Uso: <x-vazio icone="inbox" texto="Nada por aqui." /> (o slot recebe ações opcionais) --}}
@props(['icone' => 'inbox', 'texto'])

<div class="flex flex-col items-center gap-3 rounded-xl border border-dashed border-zinc-300 bg-zinc-50/60 p-10 text-center dark:border-zinc-700 dark:bg-zinc-900/40">
    <span class="flex size-12 items-center justify-center rounded-full bg-indigo-100 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-300">
        <flux:icon :name="$icone" />
    </span>
    <flux:text>{{ $texto }}</flux:text>
    {{ $slot }}
</div>
