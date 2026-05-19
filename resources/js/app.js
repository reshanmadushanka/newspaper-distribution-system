import '../css/app.css'
import 'vue3-toastify/dist/index.css'
import 'sweetalert2/dist/sweetalert2.min.css'

import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import Singlish from 'singlish-pro';

window.addEventListener('DOMContentLoaded', () => {
    new Singlish({
        showUI: true, // Shows the floating toggle button
        enabled: false // Starts in English mode
    });
});

createInertiaApp({
    title: (title) => title ? `${title} - Newspaper Distribution` : 'Newspaper Distribution',
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true })

        return pages[`./Pages/${name}.vue`]
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el)
    },
})
