<template>
    <svg viewBox="0 0 200 190" class="h-full w-full" role="img" aria-label="Vaso de helado con fresas, vainilla y chocolate, galleta y cereza">
        <defs>
            <linearGradient id="cupBody" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#7FC7C3" />
                <stop offset="100%" stop-color="#45AAA7" />
            </linearGradient>
            <linearGradient id="scoopStrawberry" x1="0.2" y1="0" x2="0.8" y2="1">
                <stop offset="0%" stop-color="#FBD3DA" />
                <stop offset="100%" stop-color="#F2A8B6" />
            </linearGradient>
            <linearGradient id="scoopVanilla" x1="0.2" y1="0" x2="0.8" y2="1">
                <stop offset="0%" stop-color="#FDF0D8" />
                <stop offset="100%" stop-color="#F2D9AC" />
            </linearGradient>
            <linearGradient id="scoopChocolate" x1="0.2" y1="0" x2="0.8" y2="1">
                <stop offset="0%" stop-color="#C79A6E" />
                <stop offset="100%" stop-color="#9E6F45" />
            </linearGradient>
            <linearGradient id="stick" x1="0" y1="0" x2="1" y2="0">
                <stop offset="0%" stop-color="#E8C89A" />
                <stop offset="100%" stop-color="#D3AC79" />
            </linearGradient>
        </defs>

        <!-- Sombra suave bajo el vaso -->
        <ellipse cx="100" cy="180" rx="62" ry="7" fill="#2C5A59" opacity="0.10" />

        <!-- Galleta wafer a la izquierda -->
        <g transform="rotate(-22 44 88)">
            <rect x="36" y="46" width="17" height="82" rx="8.5" fill="url(#stick)" />
            <g stroke="#C69C68" stroke-width="1.6" opacity="0.75">
                <path d="M37 60h15M37 74h15M37 88h15M37 102h15M37 116h15" />
            </g>
        </g>

        <!-- Cuchara de madera a la derecha -->
        <g transform="rotate(20 160 84)">
            <rect x="156" y="56" width="8" height="66" rx="4" fill="#D9A86C" />
            <ellipse cx="160" cy="50" rx="12" ry="14" fill="#E5B87E" />
        </g>

        <!-- Bolas de helado (de atrás hacia adelante) -->
        <!-- Chocolate -->
        <circle cx="128" cy="86" r="30" fill="url(#scoopChocolate)" />
        <!-- Vainilla -->
        <circle cx="82" cy="90" r="29" fill="url(#scoopVanilla)" />
        <!-- Fresa (al frente y arriba) -->
        <circle cx="105" cy="66" r="32" fill="url(#scoopStrawberry)" />

        <!-- Brillos -->
        <ellipse cx="95" cy="52" rx="12" ry="8" fill="#FFFFFF" opacity="0.42" transform="rotate(-18 95 52)" />
        <ellipse cx="73" cy="80" rx="9" ry="6" fill="#FFFFFF" opacity="0.34" transform="rotate(-18 73 80)" />

        <!-- Chorretones que caen sobre el borde del vaso -->
        <path d="M62 96c0 7 3 11 3 15a4 4 0 0 1-8 0c0-4 3-8 5-15z" fill="#F2D9AC" />
        <path d="M118 92c0 8 3 12 3 17a4 4 0 0 1-8 0c0-5 3-9 5-17z" fill="#F2A8B6" />
        <path d="M142 98c0 6 2 9 2 13a3.5 3.5 0 0 1-7 0c0-4 2-7 5-13z" fill="#9E6F45" />

        <!-- Toppings: chispas -->
        <g>
            <rect x="88" y="44" width="7" height="3" rx="1.5" fill="#E4685D" transform="rotate(24 91 45)" />
            <rect x="112" y="40" width="7" height="3" rx="1.5" fill="#6BC2BB" transform="rotate(-30 115 41)" />
            <rect x="122" y="58" width="7" height="3" rx="1.5" fill="#F2C94C" transform="rotate(48 125 59)" />
            <rect x="76" y="66" width="7" height="3" rx="1.5" fill="#E4685D" transform="rotate(-14 79 67)" />
            <rect x="102" y="70" width="7" height="3" rx="1.5" fill="#7A9E46" transform="rotate(66 105 71)" />
            <rect x="136" y="72" width="6" height="3" rx="1.5" fill="#6BC2BB" transform="rotate(-52 139 73)" />
            <rect x="66" y="52" width="6" height="3" rx="1.5" fill="#F2C94C" transform="rotate(40 69 53)" />
        </g>

        <!-- Cereza con rabito -->
        <path d="M105 36c4-8 12-10 18-9" fill="none" stroke="#5A8A4E" stroke-width="2.4" stroke-linecap="round" />
        <path d="M123 27c5-2 9 1 9 5-4 1-8-1-9-5z" fill="#6FA25F" />
        <circle cx="105" cy="40" r="9" fill="#E4685D" />
        <circle cx="102" cy="37" r="2.6" fill="#FFFFFF" opacity="0.55" />

        <!-- Cuerpo del vaso -->
        <path
            d="M47 92h106l-13 74a12 12 0 0 1-11.8 10H71.8A12 12 0 0 1 60 166z"
            fill="url(#cupBody)"
        />
        <!-- Borde superior del vaso -->
        <ellipse cx="100" cy="92" rx="53" ry="9" fill="#93D5CF" />
        <ellipse cx="100" cy="92" rx="45" ry="6.5" fill="#3B8C89" opacity="0.35" />
        <!-- Brillo lateral del vaso -->
        <path d="M60 104l9 62a4 4 0 0 1-4 4.4z" fill="#FFFFFF" opacity="0.30" />

        <!-- Corazón blanco en el frente -->
        <path
            d="M100 158c-1.5-1.6-19-14.6-19-26.6 0-6.6 5.1-11.4 11.3-11.4 3.9 0 7.4 1.9 9.4 4.9 2-3 5.5-4.9 9.4-4.9 6.2 0 11.3 4.8 11.3 11.4 0 12-17.5 25-19 26.6z"
            fill="#FFFFFF"
        />
        <path
            d="M92 126c-3 0-5.2 1.6-6.1 3.4 1-3.1 3.4-5.1 6.1-5.1 1.1 0 2.1.3 3 .8-1-.7-2-1.1-3-1.1z"
            fill="#DDF3F0"
            opacity="0.8"
        />
    </svg>
</template>
