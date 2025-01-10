import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3' 
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'

const appName = 'Self and Peer Assessment'

createInertiaApp({
    title: (title) => `${title} ${appName}`,
    
    resolve: (name) => resolvePageComponent(
        `./Pages/${name}.vue`,
        import.meta.glob('./Pages/**/*.vue')
    ),
    
    setup({ el, App, props, plugin }) {
        const app = createApp({ 
            render: () => h(App, props)
        })
        
        app.use(plugin)
        app.mount(el)
        
        return app
    }
})