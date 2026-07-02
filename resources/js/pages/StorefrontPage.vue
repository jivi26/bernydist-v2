<template>
  <div class="min-h-screen flex flex-col bg-gray-100">

    <AppHeader />

    <main class="flex-1">

      <!-- Carrusel hero -->
      <section class="relative overflow-hidden bg-[#141414]" style="height: 480px;">

        <!-- Track de slides -->
        <div class="flex h-full transition-transform duration-700 ease-in-out"
             :style="`transform: translateX(-${activeSlide * 100}%)`">

          <!-- Slide 0: slide de presentación (texto + botones) -->
          <div class="w-full h-full shrink-0 bg-gradient-to-r from-[#141414] via-[#1f1400] to-[#3a2800] flex items-center">
            <div class="max-w-screen-xl mx-auto px-6 sm:px-10 w-full">
              <p class="text-[#f2b02c] text-xs sm:text-sm font-semibold uppercase tracking-widest mb-2">
                Catálogo Mayorista
              </p>
              <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white leading-tight mb-4">
                Herramientas y materiales<br class="hidden sm:block">
                para profesionales
              </h1>
              <p class="text-gray-300 text-sm sm:text-base mb-7 max-w-lg leading-relaxed">
                Precios de mayoreo, envíos a toda la república y atención personalizada.
                <span v-if="user">Bienvenido, {{ firstName }}.</span>
              </p>
              <div class="flex flex-wrap gap-3">
                <RouterLink to="/catalogo"
                            class="inline-flex items-center gap-2 bg-[#f37e2b] hover:bg-[#e06d1e] text-white font-bold px-6 py-3 rounded-full transition-colors text-sm shadow-lg">
                  Explorar catálogo
                  <ArrowRightIcon class="w-4 h-4" />
                </RouterLink>
                <a v-if="!user" href="/login"
                   class="inline-flex items-center gap-2 bg-white/10 hover:bg-white/20 text-white font-semibold px-6 py-3 rounded-full transition-colors text-sm border border-white/30">
                  <LockOpenIcon class="w-4 h-4" />
                  Iniciar sesión
                </a>
                <a href="https://wa.me/529993991507?text=Hola,%20me%20interesa%20información%20sobre%20productos%20mayoreo"
                   target="_blank" rel="noopener"
                   class="inline-flex items-center gap-2 bg-[#25D366] hover:bg-[#1ebe5d] text-white font-semibold px-6 py-3 rounded-full transition-colors text-sm shadow-lg">
                  <WhatsAppIcon class="w-4 h-4" />
                  Contáctanos
                </a>
              </div>
            </div>
          </div>

          <!-- Slides 1–5: banners de imagen -->
          <div v-for="(banner, i) in banners" :key="i"
               class="w-full h-full shrink-0 overflow-hidden">
            <img :src="banner.src" :alt="banner.alt"
                 class="w-full h-full object-cover object-center" />
          </div>

        </div>

        <!-- Flecha izquierda -->
        <button @click="prevSlide"
                class="absolute left-3 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-black/40 hover:bg-black/70 text-white flex items-center justify-center transition-colors focus:outline-none">
          <ChevronLeftIcon class="w-5 h-5" />
        </button>

        <!-- Flecha derecha -->
        <button @click="nextSlide"
                class="absolute right-3 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-black/40 hover:bg-black/70 text-white flex items-center justify-center transition-colors focus:outline-none">
          <ChevronRightIcon class="w-5 h-5" />
        </button>

        <!-- Indicadores (dots) — total = 1 texto + 5 banners -->
        <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2">
          <button v-for="(_, i) in totalSlides" :key="i"
                  @click="goToSlide(i)"
                  :class="i === activeSlide
                    ? 'bg-[#f2b02c] w-6'
                    : 'bg-white/50 hover:bg-white/80 w-2'"
                  class="h-2 rounded-full transition-all duration-300 focus:outline-none" />
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

      <!-- Productos destacados (placeholder — Fase 2b) -->
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
import { ref, computed, onMounted, onBeforeUnmount, defineComponent, h } from 'vue';
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
    ChevronLeftIcon,
    ChevronRightIcon,
} from '@heroicons/vue/24/outline';
import AppHeader from '@/layout/AppHeader.vue';
import AppFooter from '@/layout/AppFooter.vue';

// Inline WhatsApp icon (no está en Heroicons — usamos SVG del logo oficial)
const WhatsAppIcon = defineComponent({
    name: 'WhatsAppIcon',
    render: () => h('svg', { viewBox: '0 0 24 24', fill: 'currentColor', class: 'w-4 h-4' }, [
        h('path', { d: 'M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z' })
    ]),
});

const user = window.appData?.user ?? null;

const firstName = computed(() => {
    if (!user?.nombre) return '';
    return user.nombre.split(' ')[0];
});

// Carrusel — slide 0 es el bloque de texto; slides 1-5 son banners de imagen
const banners = [
    { src: '/images/banner-10.webp', alt: 'Herramientas y materiales Berny' },
    { src: '/images/banner-20.webp', alt: 'Catálogo mayorista Berny' },
    { src: '/images/banner-30.webp', alt: 'Distribuidora Berny' },
    { src: '/images/banner-40.webp', alt: 'Productos profesionales Berny' },
    { src: '/images/banner-50.webp', alt: 'Precios de mayoreo Berny' },
];

const totalSlides = banners.length + 1; // 1 texto + 5 imágenes
const activeSlide = ref(0);
let autoPlayTimer = null;

function nextSlide() {
    activeSlide.value = (activeSlide.value + 1) % totalSlides;
    resetAutoPlay();
}

function prevSlide() {
    activeSlide.value = (activeSlide.value - 1 + totalSlides) % totalSlides;
    resetAutoPlay();
}

function goToSlide(i) {
    activeSlide.value = i;
    resetAutoPlay();
}

function resetAutoPlay() {
    clearInterval(autoPlayTimer);
    autoPlayTimer = setInterval(nextSlide, 5000);
}

onMounted(() => {
    autoPlayTimer = setInterval(nextSlide, 5000);
});

onBeforeUnmount(() => {
    clearInterval(autoPlayTimer);
});

const features = [
    { icon: TruckIcon,      label: 'Envío a toda la república' },
    { icon: CreditCardIcon, label: 'Múltiples formas de pago' },
    { icon: TagIcon,         label: 'Precios de mayoreo' },
    { icon: PhoneIcon,       label: 'Atención personalizada' },
];

const topCategories = [
    { slug: 'herramientas', nombre: 'Herramientas',  icon: WrenchScrewdriverIcon },
    { slug: 'ferreteria',   nombre: 'Ferretería',    icon: Cog6ToothIcon },
    { slug: 'construccion', nombre: 'Construcción',  icon: HomeModernIcon },
    { slug: 'electrico',    nombre: 'Eléctrico',     icon: BoltIcon },
    { slug: 'seguridad',    nombre: 'Seguridad',     icon: ShieldCheckIcon },
    { slug: 'pintura',      nombre: 'Pintura',       icon: SwatchIcon },
];
</script>
