<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 antialiased dark:bg-zinc-900">
        <div class="grid min-h-svh lg:grid-cols-2">
            {{-- Painel da marca (desktop) --}}
            <div class="relative hidden overflow-hidden bg-linear-to-br from-indigo-600 via-indigo-700 to-violet-800 p-12 text-white lg:flex lg:flex-col">
                {{-- Feixes de luz decorativos, como o espectro do prisma --}}
                <div aria-hidden="true" class="pointer-events-none absolute inset-0">
                    <div class="absolute -right-24 top-1/4 h-2 w-[42rem] -rotate-12 rounded-full bg-sky-300/40 blur-sm"></div>
                    <div class="absolute -right-24 top-[34%] h-2 w-[40rem] rotate-0 rounded-full bg-emerald-300/40 blur-sm"></div>
                    <div class="absolute -right-24 top-[43%] h-2 w-[38rem] rotate-12 rounded-full bg-amber-300/40 blur-sm"></div>
                    <div class="absolute -bottom-32 -left-32 size-96 rounded-full bg-violet-400/30 blur-3xl"></div>
                </div>

                <a href="{{ route('home') }}" class="relative z-10 flex items-center gap-3 text-xl font-semibold" wire:navigate>
                    <x-app-logo-icon class="size-11 rounded-xl ring-2 ring-white/30" />
                    {{ config('app.name', 'Prisma') }}
                </a>

                <div class="relative z-10 mt-auto max-w-md">
                    <h2 class="text-3xl font-semibold leading-tight">Cada solicitação no caminho certo.</h2>
                    <p class="mt-3 text-indigo-100">
                        Abra chamados, acompanhe o andamento e fale com a equipe responsável em um só lugar.
                    </p>
                    <ul class="mt-8 flex flex-col gap-3 text-sm text-indigo-50">
                        <li class="flex items-center gap-3"><span class="size-2.5 rounded-full bg-sky-300"></span> Catálogo de serviços com formulários próprios</li>
                        <li class="flex items-center gap-3"><span class="size-2.5 rounded-full bg-emerald-300"></span> Fila de atendimento por área</li>
                        <li class="flex items-center gap-3"><span class="size-2.5 rounded-full bg-amber-300"></span> Linha do tempo completa de cada chamado</li>
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
