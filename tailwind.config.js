module.exports = {
  content: [
    './app/Views/**/*.php',
    './public/**/*.php',
    './app/Core/**/*.php'
  ],
  theme: {
    extend: {
      colors: {
        ink: {
          50: '#f8fafc',
          100: '#eef2f7',
          200: '#d9e1ea',
          300: '#b8c5d2',
          400: '#8799aa',
          500: '#637487',
          600: '#48576a',
          700: '#344154',
          800: '#202b3a',
          900: '#111827',
          950: '#070b12'
        },
        brand: {
          50: '#eefdfa',
          100: '#cff8f1',
          200: '#9ef0e3',
          300: '#5edfcc',
          400: '#22c6b4',
          500: '#0ea394',
          600: '#0b8179',
          700: '#0d6761',
          800: '#10524f',
          900: '#123f3e'
        },
        accent: {
          50: '#f5f3ff',
          100: '#ede9fe',
          200: '#ddd6fe',
          300: '#c4b5fd',
          400: '#a78bfa',
          500: '#8b5cf6',
          600: '#7c3aed',
          700: '#6d28d9',
          800: '#5b21b6',
          900: '#4c1d95'
        }
      },
      boxShadow: {
        soft: '0 18px 60px rgba(17, 24, 39, 0.08)',
        card: '0 10px 30px rgba(17, 24, 39, 0.06)'
      }
    }
  },
  plugins: []
};
