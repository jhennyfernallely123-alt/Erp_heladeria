<template>
    <svg
        viewBox="0 0 320 344"
        class="h-full w-full"
        role="img"
        aria-label="Galera de helado con crema, fresas, arándanos, moras y hoja de menta"
    >
        <defs>
            <clipPath id="galeraClip">
                <path d="M56 176 L112 310 Q160 336 208 310 L264 176 Z" />
            </clipPath>
        </defs>

        <!-- ============================================================
             HOJAS Y FRUTOS DEL FONDO (detras del helado)
             ============================================================ -->

        <!-- Hoja de menta izquierda -->
        <g transform="translate(104 66) rotate(-22)">
            <path
                d="M0 0 L-10 -16 L-20 -6 L-30 -18 L-32 -2 L-20 4 L-26 16 L-10 12 L-4 24 L4 12 L18 18 L18 4 L30 -2 L24 -18 L12 -12 L8 -26 Z"
                fill="#4CAF50"
                stroke="#1F2937"
                stroke-width="3.5"
                stroke-linejoin="round"
            />
            <path d="M-2 2 L2 20" stroke="#1F2937" stroke-width="2.5" stroke-linecap="round" fill="none" />
        </g>

        <!-- Mora (arandano negro) derecha -->
        <g transform="translate(214 78)">
            <g fill="#3B2A4A" stroke="#1F2937" stroke-width="3">
                <circle cx="0" cy="0" r="9" />
                <circle cx="-11" cy="6" r="8.5" />
                <circle cx="11" cy="6" r="8.5" />
                <circle cx="-6" cy="-9" r="8" />
                <circle cx="7" cy="-9" r="8" />
                <circle cx="0" cy="12" r="8" />
            </g>
            <circle cx="-4" cy="-4" r="2.4" fill="#7A6A94" />
            <circle cx="4" cy="3" r="2" fill="#7A6A94" opacity="0.7" />
        </g>

        <!-- Arandano pequeno izquierda -->
        <g transform="translate(88 104)">
            <circle r="10" fill="#4A5FA5" stroke="#1F2937" stroke-width="3.5" />
            <path
                d="M0 -4 L3.4 -0.6 L-3.4 -0.6 Z"
                fill="#1F2937"
                stroke="#1F2937"
                stroke-width="1.6"
                stroke-linejoin="round"
            />
            <circle cx="-3.4" cy="-3.4" r="2" fill="#9FB2E8" opacity="0.85" />
        </g>

        <!-- ============================================================
             HELADO: CREMA CON VOLUTAS
             ============================================================ -->

        <!-- Voluta inferior (la mas ancha) -->
        <path
            d="M64 178
               c-2 -24 16 -42 40 -38
               c6 -22 40 -28 58 -8
               c14 -20 48 -18 58 10
               c22 -6 42 10 40 30
               c0 6 -6 8 -16 8
               l-164 0
               c-10 0 -14 -1 -16 -2z"
            fill="#FFF8E7"
            stroke="#1F2937"
            stroke-width="4"
            stroke-linejoin="round"
        />

        <!-- Voluta del medio -->
        <path
            d="M88 142
               c-2 -20 14 -34 34 -31
               c6 -18 34 -22 48 -6
               c12 -16 40 -14 48 8
               c18 -4 34 8 32 24
               c0 5 -5 7 -13 7
               l-136 0
               c-8 0 -12 -1 -13 -2z"
            fill="#FFF8E7"
            stroke="#1F2937"
            stroke-width="4"
            stroke-linejoin="round"
        />

        <!-- Voluta superior con la punta curl -->
        <path
            d="M112 108
               c-1 -17 12 -28 29 -25
               c5 -16 30 -19 41 -4
               c11 -13 34 -11 40 7
               c15 -3 28 7 26 20
               c0 4 -4 6 -11 6
               l-112 0
               c-7 0 -11 -2 -13 -4z"
            fill="#FFF8E7"
            stroke="#1F2937"
            stroke-width="4"
            stroke-linejoin="round"
        />

        <!-- Punta de la voluta -->
        <path
            d="M196 112 c14 -2 26 -10 30 -22 c3 -9 -2 -17 -10 -18 c-8 -1 -14 5 -14 13 c0 6 4 10 9 11"
            fill="#FFF8E7"
            stroke="#1F2937"
            stroke-width="4"
            stroke-linecap="round"
            stroke-linejoin="round"
        />

        <!-- ============================================================
             FRUTOS DEL FRENTE
             ============================================================ -->

        <!-- Fresa central -->
        <g transform="translate(160 92) rotate(8)">
            <path
                d="M0 24 C-11 15 -14 2 -8 -5 C-4 -10 4 -10 8 -5 C14 2 11 15 0 24 Z"
                fill="#E23B4A"
                stroke="#1F2937"
                stroke-width="3.5"
                stroke-linejoin="round"
            />
            <!-- Calizulo -->
            <path
                d="M-11 -6 L-4 -9 L-1 -14 L3 -9 L11 -6 L3 -3 L-3 -3 Z"
                fill="#4CAF50"
                stroke="#1F2937"
                stroke-width="3"
                stroke-linejoin="round"
            />
            <!-- Semillas -->
            <g fill="#FFE082">
                <ellipse cx="-4" cy="4" rx="1.5" ry="2.2" />
                <ellipse cx="4" cy="4" rx="1.5" ry="2.2" />
                <ellipse cx="0" cy="12" rx="1.5" ry="2.2" />
                <ellipse cx="-5" cy="13" rx="1.4" ry="2" />
                <ellipse cx="5" cy="13" rx="1.4" ry="2" />
            </g>
            <path d="M-6 0 C-4 -3 -1 -3 0 0" fill="#FFFFFF" opacity="0.35" />
        </g>

        <!-- Arandano derecho -->
        <g transform="translate(206 118)">
            <circle r="11" fill="#4A5FA5" stroke="#1F2937" stroke-width="3.5" />
            <path d="M0 -4.6 L3.8 -0.7 L-3.8 -0.7 Z" fill="#1F2937" stroke="#1F2937" stroke-width="1.8" stroke-linejoin="round" />
            <circle cx="-4" cy="-4" r="2.2" fill="#9FB2E8" opacity="0.85" />
        </g>

        <!-- ============================================================
             GALERA DE WAFFLE
             ============================================================ -->

        <!-- Cuerpo de la galera -->
        <path
            d="M56 176 L112 310 Q160 336 208 310 L264 176 Z"
            fill="#C08457"
            stroke="#1F2937"
            stroke-width="4.5"
            stroke-linejoin="round"
        />

        <!-- Trama de waffle, recortada dentro de la galera -->
        <g clip-path="url(#galeraClip)" stroke="#8A5A34" stroke-width="5" opacity="0.9">
            <path d="M20 140 L240 340" />
            <path d="M-10 190 L210 390" />
            <path d="M60 120 L280 320" />
            <path d="M110 100 L330 300" />
            <path d="M300 140 L80 340" />
            <path d="M330 190 L110 390" />
            <path d="M260 120 L40 320" />
            <path d="M210 100 L-10 300" />
        </g>

        <!-- Borde superior de la galera -->
        <path
            d="M56 176 L264 176"
            stroke="#1F2937"
            stroke-width="4.5"
            stroke-linecap="round"
        />

        <!-- Brillo lateral -->
        <path d="M74 186 L116 306 a8 8 0 0 1 -8 6z" fill="#FFFFFF" opacity="0.16" />
    </svg>
</template>
