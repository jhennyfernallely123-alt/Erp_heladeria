<template>
    <!-- VasoDetailed: like the reference, with drip, sprinkles, wafer and outlined heart -->
    <svg viewBox="0 0 260 300" class="h-full w-full" role="img" aria-label="Ice cream cup with three flavors, wafer and sprinkles">
        <defs>
            <linearGradient id="cupTeal" x1="0.1" y1="0" x2="0.9" y2="1">
                <stop offset="0%" stop-color="#6FC9C1" />
                <stop offset="55%" stop-color="#4EB3AB" />
                <stop offset="100%" stop-color="#3AA098" />
            </linearGradient>
            <radialGradient id="scoopChoc" cx="0.35" cy="0.28" r="0.85">
                <stop offset="0%" stop-color="#8A6244" />
                <stop offset="100%" stop-color="#5E3D2A" />
            </radialGradient>
            <radialGradient id="scoopFresa" cx="0.35" cy="0.25" r="0.9">
                <stop offset="0%" stop-color="#F7B3BA" />
                <stop offset="100%" stop-color="#E68E9A" />
            </radialGradient>
            <radialGradient id="scoopVainilla" cx="0.35" cy="0.28" r="0.9">
                <stop offset="0%" stop-color="#FBEED2" />
                <stop offset="100%" stop-color="#EFD6A8" />
            </radialGradient>
            <linearGradient id="waferGrad" x1="0" y1="0" x2="1" y2="0">
                <stop offset="0%" stop-color="#E0AE6C" />
                <stop offset="100%" stop-color="#C08E4E" />
            </linearGradient>
        </defs>

        <!-- Sombra en el piso -->
        <ellipse cx="132" cy="288" rx="86" ry="9" fill="#1F4E57" opacity="0.10" />

        <!-- ============================================================
             GALERA CLAVADA A LA IZQUIERDA
             ============================================================ -->
        <g transform="rotate(-32 62 96)">
            <path d="M50 28h24v128a12 12 0 0 1-24 0z" fill="url(#waferGrad)" />
            <g fill="#A9793F" opacity="0.55">
                <rect x="51" y="46" width="22" height="5" rx="2.5" />
                <rect x="51" y="66" width="22" height="5" rx="2.5" />
                <rect x="51" y="86" width="22" height="5" rx="2.5" />
                <rect x="51" y="106" width="22" height="5" rx="2.5" />
                <rect x="51" y="126" width="22" height="5" rx="2.5" />
            </g>
            <ellipse cx="62" cy="30" rx="12" ry="7" fill="#E7BC80" />
        </g>

        <!-- ============================================================
             BOLAS DE HELADO (de atras hacia adelante)
             ============================================================ -->

        <!-- Chocolate (izquierda) -->
        <path
            d="M44 178c-14-16-14-44 2-58 12-11 30-11 42 0 16 14 16 42 2 58-11 12-34 12-46 0z"
            fill="url(#scoopChoc)"
        />
        <!-- Vainilla (derecha) -->
        <path
            d="M150 176c-13-15-13-42 2-55 12-11 29-11 40 0 15 13 15 40 2 55-11 11-33 11-44 0z"
            fill="url(#scoopVainilla)"
        />
        <!-- Fresa (centro, arriba) -->
        <path
            d="M78 122c-16-16-16-46 2-62 14-13 34-13 48 0 18 16 18 46 2 62-13 13-39 13-52 0z"
            fill="url(#scoopFresa)"
        />

        <!-- Brillos -->
        <ellipse cx="94" cy="84" rx="16" ry="10" fill="#FFFFFF" opacity="0.30" transform="rotate(-24 94 84)" />
        <ellipse cx="60" cy="140" rx="12" ry="8" fill="#FFFFFF" opacity="0.18" transform="rotate(-24 60 140)" />
        <ellipse cx="166" cy="140" rx="12" ry="8" fill="#FFFFFF" opacity="0.34" transform="rotate(-24 166 140)" />

        <!-- ============================================================
             CHORRO DE CHOCOLATE sobre la bola de fresa
             ============================================================ -->
        <path
            d="M80 74c4 6 8 4 12 10s8 4 12 10 8 4 12 10"
            fill="none"
            stroke="#4A2C1C"
            stroke-width="7"
            stroke-linecap="round"
        />
        <path
            d="M84 132c6 2 8 8 6 14-1 5-7 6-9 2-2-5 0-14 3-16z"
            fill="#4A2C1C"
        />
        <path
            d="M120 140c6 3 7 9 4 15-2 4-8 4-9 0-1-5 2-13 5-15z"
            fill="#4A2C1C"
        />
        <path
            d="M102 120c5 3 6 8 3 13-2 4-7 4-8 0-1-4 2-11 5-13z"
            fill="#4A2C1C"
        />

        <!-- ============================================================
             CHISPAS DE COLORES
             ============================================================ -->
        <g>
            <!-- Fresa -->
            <rect x="88" y="76" width="9" height="4" rx="2" fill="#F5A623" transform="rotate(28 92 78)" />
            <rect x="108" y="70" width="9" height="4" rx="2" fill="#4CAF50" transform="rotate(-34 112 72)" />
            <rect x="128" y="86" width="9" height="4" rx="2" fill="#E8453C" transform="rotate(52 132 88)" />
            <rect x="74" y="98" width="9" height="4" rx="2" fill="#FFFFFF" transform="rotate(-18 78 100)" />
            <rect x="100" y="96" width="9" height="4" rx="2" fill="#4FC3F7" transform="rotate(70 104 98)" />
            <rect x="120" y="108" width="9" height="4" rx="2" fill="#F5A623" transform="rotate(-48 124 110)" />
            <!-- Chocolate -->
            <rect x="46" y="132" width="9" height="4" rx="2" fill="#E8453C" transform="rotate(34 50 134)" />
            <rect x="70" y="126" width="9" height="4" rx="2" fill="#4FC3F7" transform="rotate(-40 74 128)" />
            <rect x="56" y="160" width="9" height="4" rx="2" fill="#4CAF50" transform="rotate(62 60 162)" />
            <rect x="34" y="152" width="9" height="4" rx="2" fill="#F5A623" transform="rotate(-22 38 154)" />
            <!-- Vainilla -->
            <rect x="154" y="128" width="9" height="4" rx="2" fill="#E8453C" transform="rotate(-36 158 130)" />
            <rect x="182" y="136" width="9" height="4" rx="2" fill="#4CAF50" transform="rotate(46 186 138)" />
            <rect x="166" y="160" width="9" height="4" rx="2" fill="#4FC3F7" transform="rotate(-16 170 162)" />
            <rect x="192" y="120" width="9" height="4" rx="2" fill="#F5A623" transform="rotate(58 196 122)" />
        </g>

        <!-- ============================================================
             VASO
             ============================================================ -->
        <path
            d="M42 168h176l-16 96a16 16 0 0 1-15.6 12.4H73.6A16 16 0 0 1 58 264z"
            fill="url(#cupTeal)"
        />
        <!-- Borde superior del vaso -->
        <ellipse cx="130" cy="168" rx="88" ry="15" fill="#8FD9D2" />
        <ellipse cx="130" cy="170" rx="76" ry="10" fill="#2F8A83" opacity="0.28" />
        <!-- Brillo lateral -->
        <path d="M60 182l11 82a6 6 0 0 1-6 6.6z" fill="#FFFFFF" opacity="0.26" />
        <!-- Sombra interior derecha -->
        <path d="M200 182l-13 82a6 6 0 0 0 6 6.6z" fill="#1F4E57" opacity="0.10" />

        <!-- Corazon blanco con contorno -->
        <path
            d="M130 250c-2-2.2-27-19.6-27-35.8 0-9.4 7.4-16.2 16.6-16.2 5.7 0 10.8 2.8 13.6 7.2 2.8-4.4 7.9-7.2 13.6-7.2 9.2 0 16.6 6.8 16.6 16.2 0 16.2-25 33.6-27 35.8z"
            fill="none"
            stroke="#FFFFFF"
            stroke-width="9"
            stroke-linejoin="round"
        />
    </svg>
</template>
