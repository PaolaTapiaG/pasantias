/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './resources/**/*.{blade.php,js}',
    './app/View/Components/**/*.php',
    './app/Http/**/*.php',
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        deep: '#001b48',
        navy: '#02457a',
        blue: '#018abe',
        aqua: '#97cadb',
        mist: '#d6e8ee',
        foam: '#f6fbff',
        sand: '#f6ead7',
      },
      fontFamily: {
        display: ['Fraunces', 'serif'],
        sans: ['Manrope', 'sans-serif'],
      },
      animation: {
        float: 'float 7s ease-in-out infinite',
      },
      keyframes: {
        float: {
          '0%,100%': {
            transform: 'translateY(0px)',
          },
          '50%': {
            transform: 'translateY(-12px)',
          },
        },
      },
      boxShadow: {
        'water': '0 28px 70px rgba(0, 27, 72, 0.12)',
        'water-lg': '0 30px 80px rgba(0, 27, 72, 0.16)',
        'water-xl': '0 28px 80px rgba(0, 27, 72, 0.16)',
      },
      backgroundImage: {
        'water-wave': `
          radial-gradient(circle at 20% 22%, rgba(255, 255, 255, .8), transparent 8rem),
          linear-gradient(135deg, #02457a 0%, #018abe 52%, #97cadb 100%)
        `,
        'portal-bg': `
          radial-gradient(circle at 8% 2%, rgba(151, 202, 219, .72), transparent 24rem),
          radial-gradient(circle at 92% 16%, rgba(1, 138, 190, .18), transparent 28rem),
          linear-gradient(180deg, #f6fbff 0%, #ffffff 40%, #eef8fc 100%)
        `,
      },
      spacing: {
        '128': '32rem',
        '144': '36rem',
      },
      borderRadius: {
        '3xl': '1.875rem',
        '4xl': '2.5rem',
        '5xl': '3rem',
      },
    },
  },
  cacheInvalidation: {
    timestamp: true,
  },
  plugins: [],
}
