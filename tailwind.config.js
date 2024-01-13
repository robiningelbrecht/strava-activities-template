/** @type {import('tailwindcss').Config} */

module.exports = {
    darkMode: 'class',
    content: [
        "./build/html/*.html",
        "./build/html/fetch-json/activity-data-table.json",
        "./build/html/fetch-json/segment-data-table.json",
        "./build/html/**/activity-8837881433.html",
        "./build/html/month/*.html",
        "./node_modules/flowbite/**/*.js"
    ],
    theme: {
        extend: {
            colors: {
                'strava-orange': '#F26722',
            },
            aria: {
                asc: 'sort="ascending"',
                desc: 'sort="descending"',
            },
        },
    },
    plugins: [
        require('flowbite/plugin')
    ]
}