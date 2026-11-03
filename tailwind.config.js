const defaultTheme = require('tailwindcss/defaultTheme');
const defaultColors = require('tailwindcss/colors');

const monochromeColors = Object.fromEntries(
    Object.keys(defaultColors)
        .filter((color) => !['inherit', 'current', 'transparent', 'black', 'white'].includes(color))
        .map((color) => [color, defaultColors.neutral]),
);
const softOrangeColors = {
    50: '#fff8f1',
    100: '#fcebdc',
    200: '#f5d2b5',
    300: '#eab88f',
    400: '#da9967',
    500: '#c47a3c',
    600: '#a8642f',
    700: '#854d28',
    800: '#6b4026',
    900: '#593622',
    950: '#321d12',
};
module.exports = {
    darkMode: 'class', // INI YANG PENTING! Tanpa ini dark mode ga akan jalan

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                ...monochromeColors,
                emerald: softOrangeColors,
                green: softOrangeColors,
                orange: softOrangeColors,
                teal: softOrangeColors,
            },
        },
    },

    plugins: [require('@tailwindcss/forms')],
};
