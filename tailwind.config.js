const defaultTheme = require('tailwindcss/defaultTheme');
const professionalBlue = {
    50: '#f1f7f9',
    100: '#deebef',
    200: '#c2d9e0',
    300: '#9cc4d0',
    400: '#6ba2b4',
    500: '#3d7e94',
    600: '#265970',
    700: '#1d4457',
    800: '#1b3949',
    900: '#1b303d',
    950: '#101e28',
};
const steelTeal = {
    50: '#f0f6f7',
    100: '#dce9ec',
    200: '#bdd4da',
    300: '#91b7c1',
    400: '#6094a2',
    500: '#4b7484',
    600: '#3c5f6e',
    700: '#344e5b',
    800: '#30424d',
    900: '#2b3942',
    950: '#19252d',
};
module.exports = {
    darkMode: 'class',

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
                emerald: professionalBlue,
                teal: steelTeal,
            },
        },
    },

    plugins: [require('@tailwindcss/forms')],
};
