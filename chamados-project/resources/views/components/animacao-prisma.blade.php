{{--
    Animação da marca para a tela de login: um feixe de luz (o chamado) entra no
    prisma e se decompõe nas cores das áreas que atendem. Só SVG + CSS; os estilos
    e keyframes ficam em resources/css/app.css (seção "Animação do prisma") e
    param sozinhos quando o sistema pede movimento reduzido.
    Uso: <x-animacao-prisma class="w-full max-w-lg" />
--}}
@php $id = 'animacao-prisma-'.\Illuminate\Support\Str::random(6); @endphp

<svg viewBox="0 0 640 420" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"
    {{ $attributes->class('animacao-prisma') }}>
    <defs>
        <linearGradient id="{{ $id }}-face" x1="320" y1="90" x2="320" y2="300" gradientUnits="userSpaceOnUse">
            <stop offset="0" stop-color="currentColor" stop-opacity=".22" />
            <stop offset="1" stop-color="currentColor" stop-opacity=".04" />
        </linearGradient>
        <radialGradient id="{{ $id }}-halo" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(330 205) scale(230)">
            <stop offset="0" stop-color="#38BDF8" stop-opacity=".18" />
            <stop offset=".5" stop-color="#34D399" stop-opacity=".06" />
            <stop offset="1" stop-color="#34D399" stop-opacity="0" />
        </radialGradient>
        <filter id="{{ $id }}-brilho" x="-20%" y="-50%" width="140%" height="200%">
            <feGaussianBlur stdDeviation="6" />
        </filter>
        {{-- Esmaece as pontas dos feixes nas laterais, para que surjam e sumam suavemente --}}
        <linearGradient id="{{ $id }}-esmaecer" x1="-10" y1="0" x2="650" y2="0" gradientUnits="userSpaceOnUse">
            <stop offset="0" stop-color="#FFFFFF" stop-opacity="0" />
            <stop offset=".2" stop-color="#FFFFFF" />
            <stop offset=".75" stop-color="#FFFFFF" />
            <stop offset="1" stop-color="#FFFFFF" stop-opacity="0" />
        </linearGradient>
        <mask id="{{ $id }}-mascara" maskUnits="userSpaceOnUse" x="-20" y="0" width="680" height="420">
            <rect x="-20" y="0" width="680" height="420" fill="url(#{{ $id }}-esmaecer)" />
        </mask>
    </defs>

    <g mask="url(#{{ $id }}-mascara)">

    {{-- Halo suave atrás do prisma --}}
    <circle cx="330" cy="205" r="230" fill="url(#{{ $id }}-halo)" class="animacao-prisma__halo" />

    {{-- Feixe de entrada: traço base, desenho inicial e pulsos de luz --}}
    <g class="text-zinc-400 dark:text-white">
        <path d="M-10 262 265 205" stroke="currentColor" stroke-opacity=".15" stroke-width="3" stroke-linecap="round" />
        <path d="M-10 262 265 205" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
            class="animacao-prisma__desenho" style="--atraso: 0s" pathLength="1" />
        <path d="M-10 262 265 205" stroke="currentColor" stroke-width="3" stroke-linecap="round"
            class="animacao-prisma__pulso" style="--atraso: 1.6s" pathLength="100" />
    </g>

    {{-- Percurso dentro do prisma --}}
    <path d="M265 205 370 195" class="animacao-prisma__desenho text-zinc-300 dark:text-white/70" stroke="currentColor"
        stroke-width="2" stroke-linecap="round" style="--atraso: .55s" pathLength="1" />

    {{-- Feixes de saída: brilho desfocado, traço e pulsos, cada cor com seu ritmo --}}
    @foreach ([
        ['cor' => '#38BDF8', 'd' => 'M366 187 660 112', 'atraso' => .8],
        ['cor' => '#34D399', 'd' => 'M370 195 660 200', 'atraso' => .95],
        ['cor' => '#FBBF24', 'd' => 'M374 203 660 288', 'atraso' => 1.1],
    ] as $indice => $feixe)
        <g style="--atraso: {{ $feixe['atraso'] }}s; --fase: {{ $indice * 0.15 }}s">
            <path d="{{ $feixe['d'] }}" stroke="{{ $feixe['cor'] }}" stroke-width="14" stroke-linecap="round"
                filter="url(#{{ $id }}-brilho)" class="animacao-prisma__aura" />
            <path d="{{ $feixe['d'] }}" stroke="{{ $feixe['cor'] }}" stroke-width="3" stroke-linecap="round"
                class="animacao-prisma__desenho" pathLength="1" />
            <path d="{{ $feixe['d'] }}" stroke="#FFFFFF" stroke-width="3" stroke-linecap="round"
                class="animacao-prisma__pulso animacao-prisma__pulso--saida" pathLength="100" />
        </g>
    @endforeach

    {{-- O prisma, que "respira" devagar --}}
    <g class="animacao-prisma__corpo text-zinc-500 dark:text-white">
        <path d="M320 90 420 300H220Z" fill="url(#{{ $id }}-face)" />
        <path d="M320 90 420 300H220Z" stroke="currentColor" stroke-opacity=".55" stroke-width="2" stroke-linejoin="round" />
        <path d="M320 90 336 300" stroke="currentColor" stroke-opacity=".12" stroke-width="1.5" />
        <path d="M320 90 420 300H220Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"
            class="animacao-prisma__contorno" pathLength="1" />
    </g>

    {{-- Partículas de luz flutuando --}}
    @foreach ([
        [470, 140, '#38BDF8', 0], [540, 175, '#38BDF8', 2.2], [500, 215, '#34D399', 1.1],
        [585, 230, '#34D399', 3.0], [480, 262, '#FBBF24', 0.6], [560, 300, '#FBBF24', 2.6],
        [130, 222, 'currentColor', 1.8], [200, 246, 'currentColor', 3.4],
    ] as [$x, $y, $cor, $atraso])
        <circle cx="{{ $x }}" cy="{{ $y }}" r="2.2" fill="{{ $cor }}"
            class="animacao-prisma__particula text-zinc-400 dark:text-white" style="--atraso: {{ $atraso }}s" />
    @endforeach
    </g>
</svg>
