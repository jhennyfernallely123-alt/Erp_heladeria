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
        ice: {
          50: '#fdf4f7',
          100: '#fbe8f0',
          200: '#f7d2e2',
          300: '#f1adc9',
          400: '#e67ca6',
          500: '#d75284',
          600: '#c0386c',
          700: '#a32855',
          800: '#872347',
          900: '#71213e',
        },
        // Paleta de la pantalla de login: verdes y turquesas del degradado.
        brand: {
          50: '#f2faf8',
          100: '#dcf1ec',
          200: '#b8e3da',
          300: '#8ad2c5',
          400: '#5fbdae',
          500: '#43a194',
          600: '#35857b',
          700: '#2d6b64',
          800: '#275653',
          900: '#244846',
        },
        mint: {
          50: '#f4fbfa',
          100: '#e2f5f1',
          200: '#c6ebe4',
          300: '#9ddcd2',
          400: '#6ec6ba',
          500: '#4aab9f',
          600: '#3a8f86',
          700: '#31736d',
          800: '#2c5c58',
          900: '#274d4a',
        },
      },
      fontFamily: {
        script: ['Pacifico', 'Brush Script MT', 'Segoe Script', 'cursive'],
        caveat: ['Caveat', 'Segoe Script', 'cursive'],
      },
    },
  },
  plugins: [],
}
