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
        }
      }
    },
  },
  plugins: [],
}
