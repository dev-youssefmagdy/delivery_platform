import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

/*
| Two independent entries (Plan §5.1): the admin panel and the public website share no CSS/JS.
| Heavy admin libraries (Leaflet, flatpickr, Tom Select, Chart.js) are lazy-loaded by the modules that need them.
*/
export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/admin.css',
                'resources/js/admin/app.js',
                'resources/css/website.css',
                'resources/js/website/app.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
