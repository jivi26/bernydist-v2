<template>
  <div class="min-h-screen flex flex-col bg-gray-100">

    <AppHeader />

    <main class="flex-1">

      <!-- Hero banner -->
      <section class="bg-gradient-to-r from-[#141414] to-[#3a2800] text-white">
        <div class="max-w-screen-xl mx-auto px-4 py-12 flex flex-col sm:flex-row items-center gap-6">
          <div class="flex-1">
            <p class="text-[#f2b02c] text-sm font-semibold uppercase tracking-widest mb-2">
              Catálogo mayorista
            </p>
            <h1 class="text-3xl sm:text-4xl font-extrabold leading-tight mb-4">
              Herramientas y materiales<br>para profesionales
            </h1>
            <p class="text-gray-300 text-base mb-6 max-w-md">
              Precios de mayoreo, envíos a toda la república y atención personalizada.
              {{ user ? `Bienvenido, ${user.nombre}.` : '' }}
            </p>
            <div class="flex flex-wrap gap-3">
              <RouterLink to="/catalogo"
                          class="inline-flex items-center gap-2 bg-[#f37e2b] hover:bg-[#e06d1e] text-white font-bold px-6 py-3 rounded-full transition-colors text-sm">
                Explorar catálogo
                <ArrowRightIcon class="w-4 h-4" />
              </RouterLink>
              <a v-if="!user" href="/login"
                 class="inline-flex items-center gap-2 bg-white/10 hover:bg-white/20 text-white font-semibold px-6 py-3 rounded-full transition-colors text-sm border border-white/20">
                <LockOpenIcon class="w-4 h-4" />
                Iniciar sesión
              </a>
            </div>
          </div>
          <div class="hidden sm:block shrink-0 opacity-60">
            <img src="/images/logo_berny.webp" alt="" aria-hidden="true"
                 class="h-32 w-auto object-contain brightness-0 invert" />
          </div>
        </div>
      </section>

      <!-- Características rápidas -->
      <section class="border-b border-gray-200 bg-white">
        <div class="max-w-screen-xl mx-auto px-4 py-5 grid grid-cols-2 sm:grid-cols-4 gap-4">
          <div v-for="feat in features" :key="feat.label"
               class="flex items-center gap-3 text-[#222222]">
            <div class="w-9 h-9 rounded-full bg-amber-50 flex items-center justify-center shrink-0">
              <component :is="feat.icon" class="w-5 h-5 text-[#f37e2b]" />
            </div>
            <span class="text-sm font-medium">{{ feat.label }}</span>
          </div>
        </div>
      </section>

      <!-- Categorías destacadas -->
      <section class="max-w-screen-xl mx-auto px-4 py-10">
        <h2 class="text-xl font-bold text-[#222222] mb-6">Categorías</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
          <RouterLink v-for="cat in topCategories" :key="cat.slug"
                      :to="`/catalogo?categoria=${cat.slug}`"
                      class="group flex flex-col items-center gap-2 bg-white rounded-xl p-4 border border-gray-100 hover:border-[#f2b02c] hover:shadow-md transition-all text-center">
            <div class="w-10 h-10 rounded-full bg-amber-50 flex items-center justify-center group-hover:bg-[#f2b02c] transition-colors">
              <component :is="cat.icon" class="w-5 h-5 text-[#f37e2b] group-hover:text-[#141414] transition-colors" />
            </div>
            <span class="text-xs font-semibold text-[#222222] group-hover:text-[#f37e2b] leading-tight">
              {{ cat.nombre }}
            </span>
          </RouterLink>
        </div>
      </section>

      <!-- Productos destacados (placeholder — Fase 2) -->
      <section class="max-w-screen-xl mx-auto px-4 pb-12">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-xl font-bold text-[#222222]">Productos destacados</h2>
          <RouterLink to="/catalogo"
                      class="text-sm text-[#f37e2b] hover:text-[#e06d1e] font-medium transition-colors">
            Ver todos →
          </RouterLink>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
          <div v-for="n in 10" :key="n"
               class="bg-white rounded-xl border border-gray-100 overflow-hidden animate-pulse">
            <div class="bg-gray-200 aspect-square"></div>
            <div class="p-3 space-y-2">
              <div class="h-3 bg-gray-200 rounded w-3/4"></div>
              <div class="h-3 bg-gray-200 rounded w-1/2"></div>
              <div class="h-4 bg-gray-200 rounded w-2/3"></div>
            </div>
          </div>
        </div>
      </section>

    </main>

    <AppFooter />

  </div>
</template>

<script setup>
import {
    TruckIcon,
    CreditCardIcon,
    TagIcon,
    PhoneIcon,
    WrenchScrewdriverIcon,
    Cog6ToothIcon,
    BoltIcon,
    HomeModernIcon,
    ShieldCheckIcon,
    SwatchIcon,
    ArrowRightIcon,
    LockOpenIcon,
} from '@heroicons/vue/24/outline';
import AppHeader from '@/layout/AppHeader.vue';
import AppFooter from '@/layout/AppFooter.vue';

const user = window.appData?.user ?? null;

const features = [
    { icon: TruckIcon,     label: 'Envío a toda la república' },
    { icon: CreditCardIcon, label: 'Múltiples formas de pago' },
    { icon: TagIcon,        label: 'Precios de mayoreo' },
    { icon: PhoneIcon,      label: 'Atención personalizada' },
];

const topCategories = [
    { slug: 'herramientas',  nombre: 'Herramientas',  icon: WrenchScrewdriverIcon },
    { slug: 'ferreteria',    nombre: 'Ferretería',    icon: Cog6ToothIcon },
    { slug: 'construccion',  nombre: 'Construcción',  icon: HomeModernIcon },
    { slug: 'electrico',     nombre: 'Eléctrico',     icon: BoltIcon },
    { slug: 'seguridad',     nombre: 'Seguridad',     icon: ShieldCheckIcon },
    { slug: 'pintura',       nombre: 'Pintura',       icon: SwatchIcon },
];
</script>
