<template>
    <!-- Cono en estilo linea, como el sello de la referencia -->
    <svg viewBox="0 0 120 138" class="h-full w-full" role="img" aria-label="Ice cream cone">
        <defs>
            <linearGradient id="coneWhite" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#FFFFFF" />
                <stop offset="100%" stop-color="#EAF6F4" />
            </linearGradient>
        </defs>

        <!-- Aura blanca del sello -->
        <path
            d="M60 4c14 0 25 10 25 23 0 6-2 11-2 16 4 3 7 8 7 14 0 8-6 14-13 16l12 58a5 5 0 0 1-10 2L66 82c-2 .3-4 .3-6 0L47 133a5 5 0 0 1-10-2l12-58c-7-2-13-8-13-16 0-6 3-11 7-14 0-5-2-10-2-16C41 14 52 4 66 4z"
            fill="#FFFFFF"
            transform="translate(-6 0)"
        />

        <!-- Cuerpo del cono -->
        <path
            d="M60 70c-1 0-2 0-3 .2L42 128a3 3 0 0 0 5 1.2L59 76z"
            fill="url(#coneWhite)"
            stroke="#1F4E57"
            stroke-width="5"
            stroke-linejoin="round"
        />
        <path
            d="M60 70c1 0 2 0 3 .2l15 57.8a3 3 0 0 1-5 1.2L61 76z"
            fill="url(#coneWhite)"
            stroke="#1F4E57"
            stroke-width="5"
            stroke-linejoin="round"
        />
        <path d="M60 70v6" stroke="#1F4E57" stroke-width="5" stroke-linecap="round" />

        <!-- Cuadrícula del cono -->
        <g stroke="#1F4E57" stroke-width="2.6" stroke-linecap="round" opacity="0.85">
            <path d="M52 84l-6 20M60 84v22M68 84l6 20" />
            <path d="M49 96h22M53 108h14" />
        </g>

        <!-- Borde superior del cono -->
        <path
            d="M44 68h32"
            stroke="#1F4E57"
            stroke-width="6"
            stroke-linecap="round"
        />

        <!-- Swirl del helado -->
        <path
            d="M40 56c-3-8 1-16 10-19 3-9 12-14 21-11 8 2 13 9 13 17 5 4 6 12 2 17-4 6-13 7-20 4-5 4-14 3-18-1-3-2-5-4-8-7z"
            fill="url(#coneWhite)"
            stroke="#1F4E57"
            stroke-width="5"
            stroke-linejoin="round"
        />
        <!-- Curl del swirl -->
        <path
            d="M50 44c4-4 11-4 15 0s3 11-2 14c-4 2-9 0-9-4"
            fill="none"
            stroke="#1F4E57"
            stroke-width="4.5"
            stroke-linecap="round"
        />
        <!-- Salpicaduras del sello -->
        <g stroke="#1F4E57" stroke-width="4.5" stroke-linecap="round">
            <path d="M104 22l6-5" />
            <path d="M108 34l8 1" />
            <path d="M100 14l2-8" />
        </g>
    </svg>
</template>
