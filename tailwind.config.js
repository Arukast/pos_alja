import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    darkMode: 'class',

    theme: {
        extend: {
            fontFamily: {
                sans: ['Outfit', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // ALJA CMYK Brand Palette
                'alja': {
                    'magenta':  '#E91E8C',
                    'magenta-hover': '#D01A7E',
                    'magenta-light': '#F74EAA',
                    'cyan':     '#00AEEF',
                    'cyan-hover': '#0099D6',
                    'cyan-light': '#33C1F5',
                    'yellow':   '#FFF200',
                    'yellow-hover': '#E6DA00',
                    'key':      '#1A1A2E',
                },
                // Dark surfaces
                'surface': {
                    '900': '#0A0A16',
                    '800': '#0D0D1A',
                    '700': '#111122',
                    '600': '#141428',
                    '500': '#1A1A32',
                    '400': '#22223D',
                    '300': '#2A2A48',
                    '200': '#333358',
                    '100': '#3D3D65',
                },
            },
            boxShadow: {
                'glow-magenta': '0 0 20px -5px rgba(233, 30, 140, 0.35)',
                'glow-cyan': '0 0 20px -5px rgba(0, 174, 239, 0.35)',
                'glow-yellow': '0 0 20px -5px rgba(255, 242, 0, 0.25)',
                'card': '0 4px 24px -4px rgba(0, 0, 0, 0.3)',
                'card-hover': '0 8px 32px -4px rgba(0, 0, 0, 0.4)',
            },
            borderColor: {
                'alja-subtle': 'rgba(233, 30, 140, 0.12)',
            },
            animation: {
                'glow-pulse': 'glow-pulse 3s ease-in-out infinite',
                'slide-in': 'slide-in 0.4s ease-out',
                'fade-in': 'fade-in 0.3s ease-out',
            },
            keyframes: {
                'glow-pulse': {
                    '0%, 100%': { opacity: '0.6' },
                    '50%': { opacity: '1' },
                },
                'slide-in': {
                    '0%': { transform: 'translateY(10px)', opacity: '0' },
                    '100%': { transform: 'translateY(0)', opacity: '1' },
                },
                'fade-in': {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
            },
        },
    },

    plugins: [forms],
};
