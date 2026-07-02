import { createRouter, createWebHistory } from 'vue-router';
import LandingPage        from '@/pages/LandingPage.vue';
import StorefrontPage     from '@/pages/StorefrontPage.vue';
import LoginPage          from '@/pages/LoginPage.vue';
import ForgotPasswordPage from '@/pages/ForgotPasswordPage.vue';
import CatalogPage        from '@/pages/CatalogPage.vue';
import ProductDetailPage  from '@/pages/ProductDetailPage.vue';

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
    {
        path: '/login',
        name: 'login',
        component: LoginPage,
        meta: { title: 'Iniciar sesión — Berny' },
    },
    {
        path: '/recuperar-password',
        name: 'forgot-password',
        component: ForgotPasswordPage,
        meta: { title: 'Recuperar contraseña — Berny' },
    },
    {
        path: '/catalogo',
        name: 'catalogo',
        component: CatalogPage,
        meta: { title: 'Catálogo — Berny Mayoreo' },
    },
    {
        path: '/catalogo/:id(\\d+)',
        name: 'producto',
        component: ProductDetailPage,
        meta: { title: 'Producto — Berny' },
    },
    // Rutas futuras: /tecnolite, etc.
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
