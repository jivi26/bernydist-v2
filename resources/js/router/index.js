import { createRouter, createWebHistory } from 'vue-router';
import LandingPage   from '@/pages/LandingPage.vue';
import StorefrontPage from '@/pages/StorefrontPage.vue';

const routes = [
    {
        path: '/',
        name: 'landing',
        component: LandingPage,
        meta: { title: 'Berny Distribuidora' },
    },
    {
        path: '/mayoreo',
        name: 'mayoreo',
        component: StorefrontPage,
        meta: { title: 'Berny Mayoreo' },
    },
    // Rutas futuras: /tecnolite, /catalogo, /producto/:id, etc.
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior: () => ({ top: 0 }),
});

router.afterEach((to) => {
    document.title = to.meta.title ?? 'Berny Distribuidora';
});

export default router;
