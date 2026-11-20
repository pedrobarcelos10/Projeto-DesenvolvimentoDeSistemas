{{-- Uso: <x-chamado.status :status="$chamado->status" /> --}}
@props(['status'])

<flux:badge :color="$status->cor()" size="sm">{{ $status->rotulo() }}</flux:badge>
