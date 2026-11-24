<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 antialiased dark:bg-zinc-900">
        <div class="grid min-h-svh lg:grid-cols-2">
            {{-- Painel da marca (desktop) --}}
            <div class="hidden overflow-hidden border-e border-zinc-200 bg-white p-12 lg:flex lg:flex-col dark:border-zinc-800 dark:bg-zinc-950">
                <a href="{{ route('home') }}" class="flex items-center gap-3 text-xl font-semibold text-zinc-900 dark:text-white" wire:navigate>
                    <x-app-logo-icon class="size-10" />
                    {{ config('app.name', 'Prisma') }}
                </a>

                {{-- O feixe (o chamado) entra no prisma e se decompõe nas cores das áreas --}}
                <div class="-mx-12 flex min-h-0 flex-1 items-center justify-center py-6">
                    <x-animacao-prisma class="h-full max-h-[min(26rem,42vh)] w-full" />
                </div>

                <div class="max-w-md">
                    <h2 class="text-3xl font-semibold leading-tight text-zinc-900 dark:text-white">Cada solicitação no caminho certo.</h2>
                    <p class="mt-3 text-zinc-600 dark:text-zinc-400">
                        Abra chamados, acompanhe o andamento e fale com a equipe responsável em um só lugar.
                    </p>
                    <ul class="mt-8 flex flex-col gap-3 text-sm text-zinc-700 dark:text-zinc-300">
                        <li class="flex items-center gap-3"><span class="size-2.5 rounded-full bg-sky-400"></span> Catálogo de serviços com formulários próprios</li>
                        <li class="flex items-center gap-3"><span class="size-2.5 rounded-full bg-emerald-400"></span> Fila de atendimento por área</li>
                        <li class="flex items-center gap-3"><span class="size-2.5 rounded-full bg-amber-400"></span> Linha do tempo completa de cada chamado</li>
                    </ul>
                </div>
            </div>

            {{-- Formulário --}}
            <div class="flex flex-col items-center justify-center gap-6 p-6 md:p-10">
                <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-semibold lg:hidden" wire:navigate>
                    <x-app-logo-icon class="size-12" />
                    <span class="text-lg text-zinc-900 dark:text-white">{{ config('app.name', 'Prisma') }}</span>
                </a>

                <div class="w-full max-w-sm rounded-2xl border border-zinc-200 bg-white p-8 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                    {{ $slot }}
                </div>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
