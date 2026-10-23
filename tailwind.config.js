/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./*.php",
    "./includes/**/*.php",
    "./admin/**/*.php",
    "./landing/**/*.php"
  ],
  theme: {
    extend: {
      colors: {
        // Palette sampled from the ThirstyBD logo: black, warm cream, hot pink (+ peach highlight)
        'brand': {
          50:  '#FFF1F5',
          100: '#FFE1EA',
          200: '#FFC2D4',
          300: '#FF8FB0',
          400: '#FF5A8F',
          500: '#F5106C',   // logo pink (use on black)
          600: '#DB1456',   // primary — 4.9:1 with white text
          700: '#B80E43',   // hover / pressed
          800: '#8F0B35',
          900: '#660826',
        },
        'peach':  '#F8A996',
        'cream':  { DEFAULT: '#F9EFEA', soft: '#FBF6F2' },
        'ink':    { DEFAULT: '#0A0A0A', soft: '#161616' },
        'search-bg': '#f5f5f5',
        'cart-icon-bg': '#f0f0f0',
      },
      fontFamily: {
        'sans': ['Inter', 'Arial', 'Helvetica', 'sans-serif'],
        'heading': ['Poppins', 'Inter', 'Arial', 'sans-serif'],
      },
      boxShadow: {
        'card': '0 1px 3px 0 rgba(16, 24, 40, 0.08), 0 1px 2px 0 rgba(16, 24, 40, 0.04)',
        'card-hover': '0 12px 24px -8px rgba(16, 24, 40, 0.16), 0 4px 8px -4px rgba(16, 24, 40, 0.08)',
      }
    }
  },
  plugins: [],
}