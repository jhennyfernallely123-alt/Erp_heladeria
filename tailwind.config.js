/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      colors: {
        // Paleta de la pantalla de login, tomada de la imagen de referencia.
        aguamarina: {
          50: '#F2FAF9',
          100: '#DDF3F0',
          200: '#BCE6E2',
          300: '#93D5CF',
          400: '#6BC2BB',
          500: '#4BA9A6',
          600: '#45AAA7',
          700: '#3B8C89',
          800: '#33706E',
          900: '#2C5A59',
        },
        // Azul petróleo del texto principal.
        petrol: {
          50: '#F0F6F7',
          100: '#DAEAED',
          200: '#B5D2D8',
          300: '#86B4BE',
          400: '#54929E',
          500: '#3B7884',
          600: '#2E6672',
          700: '#205B66',
          800: '#1A4A54',
          900: '#163C44',
        },
        // Gris azulado del texto secundario.
        niebla: {
          200: '#C2D0D6',
          300: '#9BAFBA',
          400: '#718A98',
          500: '#5A7382',
        },
        // Fondo de las tarjetas: blanco ligeramente grisáceo.
        papel: '#FCFDFD',
      },
      fontFamily: {
        sans: [
            'Plus Jakarta Sans',
            'ui-sans-serif',
            'system-ui',
            '-apple-system',
            'Segoe UI',
            'Roboto',
            'sans-serif',
        ],
        script: ['Pacifico', 'Brush Script MT', 'Segoe Script', 'cursive'],
        caveat: ['Caveat', 'Segoe Script', 'cursive'],
      },
      boxShadow: {
        card: '0 18px 45px -18px rgba(32, 91, 102, 0.18)',
        suave: '0 8px 24px -12px rgba(32, 91, 102, 0.14)',
      },
    },
  },
  plugins: [],
}
