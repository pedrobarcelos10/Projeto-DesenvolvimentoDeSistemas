{{-- Uso: <x-chamado.prioridade :prioridade="$chamado->prioridade" /> --}}
@props(['prioridade'])

<flux:badge :color="$prioridade->cor()" size="sm" variant="pill" icon="flag">{{ $prioridade->rotulo() }}</flux:badge>
