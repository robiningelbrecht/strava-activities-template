/** @type {import('tailwindcss').Config} */

module.exports = {
    darkMode: 'class',
    content: [
        "./build/html/*.html",
        "./build/html/**/activity-8837881433.html",
        "./build/html/month/*.html",
        "./node_modules/flowbite/**/*.js"
    ],
    theme: {
        extend: {
            colors: {
                'strava-orange': '#F26722',
            },
        },
    },
    plugins: [
        require('flowbite/plugin')
    ]
}