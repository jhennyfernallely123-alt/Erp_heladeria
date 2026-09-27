<template>
    <svg viewBox="0 0 120 132" class="h-full w-full" role="img" aria-label="Helado de cono">
        <defs>
            <linearGradient id="coneFill" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0%" stop-color="#E8C89A" />
                <stop offset="100%" stop-color="#CFA16B" />
            </linearGradient>
            <linearGradient id="softSwirl" x1="0.2" y1="0" x2="0.8" y2="1">
                <stop offset="0%" stop-color="#FFFFFF" />
                <stop offset="100%" stop-color="#DDF3F0" />
            </linearGradient>
        </defs>

        <!-- Cono de galleta -->
        <path d="M38 66h44l-17 58a7 7 0 0 1-10 0z" fill="url(#coneFill)" />
        <!-- Cuadrícula del cono -->
        <g stroke="#B98B55" stroke-width="1.5" opacity="0.55" fill="none">
            <path d="M45 70l13 44M54 70l12 40M63 70l11 36M72 70l10 30" />
            <path d="M42 84h36M46 100h28M50 114h20" />
        </g>
        <!-- Borde superior del cono -->
        <ellipse cx="60" cy="66" rx="22" ry="5.5" fill="#E8C89A" />

        <!-- Helado en tres capas de swirl -->
        <path
            d="M60 12c11 0 20 9 20 20 0 7-4 12-4 17s5 8 5 14c0 4-4 7-9 7s-12-2-12-2-12 2-12 2-9-3-9-7c0-6 5-9 5-14s-4-10-4-17c0-11 9-20 20-20z"
            fill="url(#softSwirl)"
        />
        <path
            d="M45 45c4 4 10 6 15 6s11-2 15-6"
            fill="none"
            stroke="#BCE6E2"
            stroke-width="2.4"
            stroke-linecap="round"
        />
        <path
            d="M47 61c4 3 9 5 13 5s9-2 13-5"
            fill="none"
            stroke="#93D5CF"
            stroke-width="2.2"
            stroke-linecap="round"
            opacity="0.7"
        />
        <!-- Brillo -->
        <ellipse cx="50" cy="30" rx="6" ry="9" fill="#FFFFFF" opacity="0.75" transform="rotate(-20 50 30)" />
        <!-- Cereza -->
        <circle cx="60" cy="11" r="5" fill="#E4685D" />
        <circle cx="58.4" cy="9.6" r="1.5" fill="#FFFFFF" opacity="0.6" />
    </svg>
</template>
