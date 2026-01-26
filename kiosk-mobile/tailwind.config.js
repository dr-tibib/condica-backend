/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ["./app/**/*.{js,jsx,ts,tsx}", "./src/**/*.{js,jsx,ts,tsx}"],
  presets: [require("nativewind/preset")],
  theme: {
    extend: {
      colors: {
        primary: '#3b82f6',
        'background-light': '#f3f4f6',
        'background-dark': '#0f172a',
      },
      fontFamily: {
        display: ['System'],
      },
    },
  },
  plugins: [],
}