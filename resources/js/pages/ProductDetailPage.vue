<template>
  <div class="min-h-screen flex flex-col bg-[#0f0f0f] text-white">
    <AppHeader />

    <!-- Loading -->
    <div v-if="loading" class="max-w-5xl mx-auto px-4 py-10">
      <div class="animate-pulse space-y-6">
        <div class="h-4 bg-[#1a1a1a] rounded w-1/3" />
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <div class="aspect-square bg-[#1a1a1a] rounded-xl" />
          <div class="space-y-4 pt-2">
            <div class="h-3 bg-[#1a1a1a] rounded w-1/4" />
            <div class="h-6 bg-[#1a1a1a] rounded w-3/4" />
            <div class="h-4 bg-[#1a1a1a] rounded w-1/2" />
            <div class="h-8 bg-[#1a1a1a] rounded w-1/3 mt-4" />
          </div>
        </div>
      </div>
    </div>

    <!-- Not found -->
    <div v-else-if="!product" class="flex flex-col items-center justify-center py-32 text-center px-4">
      <ExclamationTriangleIcon class="w-12 h-12 text-gray-700 mb-4" />
      <p class="text-gray-400 text-lg font-medium">Producto no encontrado</p>
      <RouterLink
        :to="{ name: 'catalogo' }"
        class="mt-6 px-4 py-2 rounded-lg bg-[#222] text-gray-300 hover:text-white text-sm transition-colors inline-flex items-center gap-2"
      >
        <ArrowLeftIcon class="w-4 h-4" />
        Volver al catálogo
      </RouterLink>
    </div>

    <!-- Product detail -->
    <div v-else class="max-w-5xl mx-auto px-4 py-8">

      <!-- Breadcrumb -->
      <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6 flex-wrap">
        <RouterLink :to="{ name: 'catalogo' }" class="hover:text-[#f2b02c] transition-colors">
          Catálogo
        </RouterLink>
        <ChevronRightIcon class="w-4 h-4 shrink-0" />
        <RouterLink
          v-if="product.category"
          :to="{ name: 'catalogo', query: { categoria: product.category.id } }"
          class="hover:text-[#f2b02c] transition-colors"
        >
          {{ product.category.name }}
        </RouterLink>
        <ChevronRightIcon v-if="product.category" class="w-4 h-4 shrink-0" />
        <span class="text-gray-300 truncate max-w-[200px]">{{ product.details }}</span>
      </nav>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-12">

        <!-- Image gallery -->
        <div>
          <div class="aspect-square bg-[#1a1a1a] rounded-xl overflow-hidden flex items-center justify-center border border-gray-800">
            <img
              v-if="activeImage"
              :src="activeImage"
              :alt="product.details"
              class="w-full h-full object-contain p-4"
              @error="activeImage = null"
            />
            <PhotoIcon v-else class="w-20 h-20 text-gray-700" />
          </div>

          <!-- Thumbnails -->
          <div v-if="product.imagenes?.length > 1" class="flex gap-2 mt-3 overflow-x-auto pb-1">
            <button
              v-for="(img, i) in product.imagenes"
              :key="i"
              :class="[
                'shrink-0 w-16 h-16 rounded-lg overflow-hidden border-2 transition-colors bg-[#1a1a1a]',
                activeImage === img ? 'border-[#f2b02c]' : 'border-gray-800 hover:border-gray-600',
              ]"
              @click="activeImage = img"
            >
              <img :src="img" :alt="`${product.details} ${i + 1}`" class="w-full h-full object-contain p-1" />
            </button>
          </div>
        </div>

        <!-- Product info -->
        <div class="flex flex-col gap-4">
          <div>
            <p class="text-xs text-gray-500 font-mono mb-1">{{ product.product_code }}</p>
            <h1 class="text-xl lg:text-2xl font-semibold text-white leading-snug">
              {{ product.details }}
            </h1>
            <p v-if="product.packing" class="text-sm text-gray-400 mt-2">{{ product.packing }}</p>
          </div>

          <!-- Price -->
          <div v-if="product.precio" class="py-3 border-t border-b border-gray-800">
            <p class="text-xs text-gray-500 mb-1">Precio lista</p>
            <p class="text-3xl font-bold text-[#f2b02c]">{{ formatPrice(product.precio) }}</p>
            <p class="text-xs text-gray-600 mt-1">IVA incluido al calcular en pedido</p>
          </div>

          <!-- Details -->
          <div class="space-y-2 text-sm">
            <div v-if="product.empaque_venta && product.empaque_venta > 1" class="flex items-center gap-2 text-gray-400">
              <CubeIcon class="w-4 h-4 text-gray-600 shrink-0" />
              Empaque de venta: <span class="text-white font-medium">{{ product.empaque_venta }} piezas</span>
            </div>
            <div v-if="product.inner && product.inner > 1" class="flex items-center gap-2 text-gray-400">
              <CubeIcon class="w-4 h-4 text-gray-600 shrink-0" />
              Inner: <span class="text-white font-medium">{{ product.inner }} piezas</span>
            </div>
            <div v-if="product.master && product.master > 1" class="flex items-center gap-2 text-gray-400">
              <CubeIcon class="w-4 h-4 text-gray-600 shrink-0" />
              Master: <span class="text-white font-medium">{{ product.master }} piezas</span>
            </div>
            <div v-if="product.barcode" class="flex items-center gap-2 text-gray-400">
              <TagIcon class="w-4 h-4 text-gray-600 shrink-0" />
              Código de barras: <span class="text-white font-mono text-xs">{{ product.barcode }}</span>
            </div>
          </div>

          <!-- CTA -->
          <div class="mt-2">
            <button
              disabled
              class="w-full sm:w-auto flex items-center justify-center gap-2 px-6 py-3 rounded-lg bg-[#f37e2b] text-white font-semibold opacity-40 cursor-not-allowed text-sm"
              title="Disponible próximamente"
            >
              <ShoppingCartIcon class="w-5 h-5" />
              Agregar al carrito
            </button>
            <p class="text-xs text-gray-600 mt-2">Carrito disponible próximamente (Fase 3)</p>
          </div>

          <!-- Back link -->
          <RouterLink
            :to="backRoute"
            class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-[#f2b02c] transition-colors mt-2 w-fit"
          >
            <ArrowLeftIcon class="w-4 h-4" />
            Volver al catálogo
          </RouterLink>
        </div>
      </div>
    </div>

    <AppFooter />
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import {
  PhotoIcon,
  ExclamationTriangleIcon,
  ArrowLeftIcon,
  ChevronRightIcon,
  CubeIcon,
  TagIcon,
  ShoppingCartIcon,
} from '@heroicons/vue/24/outline';
import AppHeader from '@/layout/AppHeader.vue';
import AppFooter from '@/layout/AppFooter.vue';

const route = useRoute();

const product     = ref(null);
const activeImage = ref(null);
const loading     = ref(true);

// Preserve category filter for "back" link
const backRoute = computed(() => {
  const q = {};
  if (product.value?.category?.id) q.categoria = product.value.category.id;
  return { name: 'catalogo', query: q };
});

const formatPrice = (price) =>
  new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(price);

async function fetchProduct(id) {
  loading.value = true;
  product.value = null;
  try {
    const res = await fetch(`/api/catalogo/${id}`);
    if (!res.ok) return;
    const data  = await res.json();
    product.value = data;
    activeImage.value = data.imagenes?.[0] ?? null;
  } finally {
    loading.value = false;
  }
}

watch(() => route.params.id, (id) => { if (id) fetchProduct(id); });

onMounted(() => fetchProduct(route.params.id));
</script>
