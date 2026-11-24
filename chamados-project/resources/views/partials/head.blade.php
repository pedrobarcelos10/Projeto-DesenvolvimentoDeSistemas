<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)" />
<meta name="theme-color" content="#18181b" media="(prefers-color-scheme: dark)" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Prisma') : config('app.name', 'Prisma') }}
</title>

{{--
    Favicon: o .ico com tamanho explícito evita que o Chrome o prefira ao SVG
    (que fica nítido em qualquer tamanho). O ?v= muda quando o arquivo muda,
    forçando o navegador a buscar o ícone novo em vez de usar o do cache.
--}}
@php $versao = fn (string $arquivo) => @filemtime(public_path($arquivo)) ?: '1'; @endphp
<link rel="icon" href="/favicon.ico?v={{ $versao('favicon.ico') }}" sizes="32x32">
<link rel="icon" href="/favicon.svg?v={{ $versao('favicon.svg') }}" sizes="any" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png?v={{ $versao('apple-touch-icon.png') }}">

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
