{{-- Estado vazio. Uso: <x-vazio icone="inbox" texto="Nada por aqui." /> (o slot recebe ações opcionais) --}}
@props(['icone' => 'inbox', 'texto'])

<div class="flex flex-col items-center gap-3 rounded-xl border border-dashed border-zinc-300 p-10 text-center dark:border-zinc-700">
    <span class="flex size-12 items-center justify-center rounded-full bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-500">
        <flux:icon :name="$icone" />
    </span>
    <flux:text>{{ $texto }}</flux:text>
    {{ $slot }}
</div>
