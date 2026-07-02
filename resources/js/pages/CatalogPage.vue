<template>
  <div class="min-h-screen flex flex-col bg-[#0f0f0f]">

    <AppHeader />

    <main class="flex-1">
      <div class="max-w-screen-xl mx-auto px-4 py-6 flex gap-6">

        <!-- ── Overlay móvil ──────────────────────────────────── -->
        <Transition name="fade">
          <div
            v-if="sidebarOpen"
            class="fixed inset-0 bg-black/60 z-30 lg:hidden"
            @click="sidebarOpen = false"
          />
        </Transition>

        <!-- ── Sidebar ────────────────────────────────────────── -->
        <aside
          :class="[
            'fixed lg:static inset-y-0 left-0 z-40 lg:z-auto top-0',
            'w-64 shrink-0',
            'bg-[#141414]',
            'overflow-y-auto',
            'transform transition-transform duration-300 lg:transform-none',
            'lg:rounded-xl lg:h-fit lg:sticky lg:top-20',
            sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
          ]"
        >
          <div class="flex items-center justify-between px-4 py-3 border-b border-gray-800">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Filtros</span>
            <button class="lg:hidden p-1 text-gray-500 hover:text-white" @click="sidebarOpen = false">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>

          <div v-if="hasActiveFilters" class="px-4 py-2 border-b border-gray-800">
            <button
              class="text-xs text-[#f2b02c] hover:text-[#f37e2b] transition-colors flex items-center gap-1"
              @click="clearFilters"
            >
              <XMarkIcon class="w-3.5 h-3.5" />
              Limpiar filtros
            </button>
          </div>

          <!-- Divisiones -->
          <div class="border-b border-gray-800">
            <button
              class="w-full flex items-center justify-between px-4 py-3 text-sm font-semibold text-gray-200 hover:text-white transition-colors"
              @click="openDivisiones = !openDivisiones"
            >
              Divisiones
              <ChevronDownIcon :class="['w-4 h-4 transition-transform text-gray-500', openDivisiones ? 'rotate-180' : '']" />
            </button>
            <div v-show="openDivisiones" class="pb-2 max-h-64 overflow-y-auto">
              <button
                :class="['w-full text-left px-4 py-1.5 text-sm transition-colors',
                  activeDivision === null ? 'text-[#f2b02c] font-medium' : 'text-gray-400 hover:text-white']"
                @click="selectDivision(null)"
              >Todas las divisiones</button>
              <button
                v-for="div in divisions"
                :key="div.id"
                :class="['w-full text-left px-4 py-1.5 text-sm transition-colors',
                  activeDivision === div.id ? 'text-[#f2b02c] font-medium' : 'text-gray-400 hover:text-white']"
                @click="selectDivision(div.id)"
              >{{ div.name }}</button>
            </div>
          </div>

          <!-- Categorías -->
          <div class="border-b border-gray-800">
            <button
              class="w-full flex items-center justify-between px-4 py-3 text-sm font-semibold text-gray-200 hover:text-white transition-colors"
              @click="openCategorias = !openCategorias"
            >
              Categorías
              <ChevronDownIcon :class="['w-4 h-4 transition-transform text-gray-500', openCategorias ? 'rotate-180' : '']" />
            </button>
            <div v-show="openCategorias" class="pb-2 max-h-72 overflow-y-auto">
              <button
                v-if="activeCategory !== null"
                :class="['w-full text-left px-4 py-1.5 text-sm transition-colors text-gray-400 hover:text-white']"
                @click="selectCategory(null)"
              >← Todas las categorías</button>
              <button
                v-for="cat in categories"
                :key="cat.id"
                :class="['w-full text-left px-4 py-1.5 text-sm transition-colors',
                  activeCategory === cat.id ? 'text-[#f2b02c] font-medium' : 'text-gray-400 hover:text-white']"
                @click="selectCategory(cat.id)"
              >{{ cat.name }}</button>
            </div>
          </div>
        </aside>

        <!-- ── Contenido principal ────────────────────────────── -->
        <section class="flex-1 min-w-0">

          <!-- ══ MODO CATEGORÍAS ══ -->
          <template v-if="!isProductMode">

            <!-- Barra superior -->
            <div class="flex items-center justify-between mb-4 gap-3">
              <div class="flex items-center gap-3">
                <button
                  class="lg:hidden p-2 rounded-lg bg-[#1a1a1a] hover:bg-[#222] text-gray-400 hover:text-white transition-colors"
                  @click="sidebarOpen = true"
                >
                  <FunnelIcon class="w-5 h-5" />
                </button>
                <p class="text-sm text-gray-500">
                  <template v-if="!loadingCategories">
                    <span class="text-white font-medium">{{ categories.length.toLocaleString('es-MX') }}</span> categorías
                  </template>
                  <span v-else>Cargando…</span>
                </p>
              </div>

              <!-- Chip de división activa -->
              <div v-if="activeDivision" class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs bg-[#f2b02c]/10 text-[#f2b02c] border border-[#f2b02c]/20">
                  {{ divisionName }}
                  <button @click="selectDivision(null)"><XMarkIcon class="w-3 h-3" /></button>
                </span>
              </div>
            </div>

            <!-- Skeletons -->
            <div v-if="loadingCategories" class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-4">
              <div v-for="n in 24" :key="n" class="bg-[#1a1a1a] rounded-xl overflow-hidden animate-pulse">
                <div class="aspect-square bg-[#252525]" />
                <div class="p-3 space-y-2">
                  <div class="h-4 bg-[#252525] rounded w-3/4" />
                  <div class="h-3 bg-[#252525] rounded w-2/5" />
                </div>
              </div>
            </div>

            <!-- Grid de categorías -->
            <div v-else class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-4">
              <button
                v-for="cat in pagedCategories"
                :key="cat.id"
                class="group bg-[#1a1a1a] rounded-xl overflow-hidden hover:bg-[#222] transition-colors border border-transparent hover:border-[#f2b02c]/20 text-left w-full"
                @click="selectCategory(cat.id)"
              >
                <div class="aspect-square bg-[#0d0d0d] overflow-hidden flex items-center justify-center">
                  <img
                    :src="`https://www.berny.mx/uploads/categoriesweb/${cat.id}.webp`"
                    :alt="cat.name"
                    loading="lazy"
                    class="w-full h-full object-contain p-3 transition-transform duration-300 group-hover:scale-105"
                    @error="(e) => e.target.style.display = 'none'"
                  />
                </div>
                <div class="p-3">
                  <p class="text-sm text-gray-200 font-medium leading-snug group-hover:text-white transition-colors line-clamp-2">
                    {{ cat.name }}
                  </p>
                  <p v-if="cat.number_products" class="text-[11px] text-gray-500 mt-1">
                    {{ cat.number_products.toLocaleString('es-MX') }} productos
                  </p>
                </div>
              </button>
            </div>

            <!-- Paginación categorías -->
            <div v-if="categoryLastPage > 1" class="flex items-center justify-center gap-1 mt-8">
              <button
                :disabled="categoryPage === 1"
                class="px-3 py-2 rounded-lg text-sm bg-[#1a1a1a] text-gray-400 hover:bg-[#222] disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                @click="goToCategoryPage(categoryPage - 1)"
              ><ChevronLeftIcon class="w-4 h-4" /></button>

              <template v-for="p in categoryPaginationPages" :key="p">
                <span v-if="p === '...'" class="px-2 py-2 text-gray-600 text-sm select-none">…</span>
                <button
                  v-else
                  :class="['w-9 h-9 rounded-lg text-sm font-medium transition-colors',
                    p === categoryPage
                      ? 'bg-[#f2b02c] text-black'
                      : 'bg-[#1a1a1a] text-gray-400 hover:bg-[#222] hover:text-white']"
                  @click="goToCategoryPage(p)"
                >{{ p }}</button>
              </template>

              <button
                :disabled="categoryPage === categoryLastPage"
                class="px-3 py-2 rounded-lg text-sm bg-[#1a1a1a] text-gray-400 hover:bg-[#222] disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                @click="goToCategoryPage(categoryPage + 1)"
              ><ChevronRightIcon class="w-4 h-4" /></button>
            </div>
          </template>

          <!-- ══ MODO PRODUCTOS ══ -->
          <template v-else>

            <!-- Migas + botón volver -->
            <div class="flex items-center gap-2 mb-4 text-sm">
              <button
                class="flex items-center gap-1 text-gray-400 hover:text-[#f2b02c] transition-colors"
                @click="selectCategory(null)"
              >
                <ChevronLeftIcon class="w-4 h-4" />
                Catálogo
              </button>
              <span class="text-gray-700">/</span>
              <span class="text-gray-300 truncate">
                {{ categoryName || (searchQuery ? `"${searchQuery}"` : 'Resultados') }}
              </span>
            </div>

            <!-- Barra de resultados + chips -->
            <div class="flex items-center justify-between mb-4 gap-3">
              <div class="flex items-center gap-3">
                <button
                  class="lg:hidden p-2 rounded-lg bg-[#1a1a1a] hover:bg-[#222] text-gray-400 hover:text-white transition-colors"
                  @click="sidebarOpen = true"
                >
                  <FunnelIcon class="w-5 h-5" />
                </button>
                <p class="text-sm text-gray-500">
                  <template v-if="!loading">
                    <span class="text-white font-medium">{{ total.toLocaleString('es-MX') }}</span> productos
                  </template>
                  <span v-else>Cargando…</span>
                </p>
              </div>

              <div class="flex items-center gap-2 flex-wrap justify-end">
                <span
                  v-if="activeDivision"
                  class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs bg-[#f2b02c]/10 text-[#f2b02c] border border-[#f2b02c]/20"
                >
                  {{ divisionName }}
                  <button @click="selectDivision(null)"><XMarkIcon class="w-3 h-3" /></button>
                </span>
                <span
                  v-if="activeCategory"
                  class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs bg-[#f2b02c]/10 text-[#f2b02c] border border-[#f2b02c]/20"
                >
                  {{ categoryName }}
                  <button @click="selectCategory(null)"><XMarkIcon class="w-3 h-3" /></button>
                </span>
                <span
                  v-if="searchQuery"
                  class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs bg-[#f2b02c]/10 text-[#f2b02c] border border-[#f2b02c]/20"
                >
                  "{{ searchQuery }}"
                  <button @click="clearSearch"><XMarkIcon class="w-3 h-3" /></button>
                </span>
              </div>
            </div>

            <!-- Skeletons productos -->
            <div v-if="loading" class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-4">
              <div v-for="n in 12" :key="n" class="bg-[#1a1a1a] rounded-xl overflow-hidden animate-pulse">
                <div class="aspect-square bg-[#252525]" />
                <div class="p-3 space-y-2">
                  <div class="h-3 bg-[#252525] rounded w-1/3" />
                  <div class="h-4 bg-[#252525] rounded w-4/5" />
                  <div class="h-5 bg-[#252525] rounded w-1/2 mt-1" />
                </div>
              </div>
            </div>

            <!-- Sin resultados -->
            <div v-else-if="products.length === 0" class="flex flex-col items-center justify-center py-24 text-center">
              <MagnifyingGlassIcon class="w-12 h-12 text-gray-700 mb-4" />
              <p class="text-gray-400 text-lg font-medium">Sin resultados</p>
              <p class="text-gray-600 text-sm mt-1">Intenta con otro término o categoría</p>
              <button
                class="mt-6 px-4 py-2 rounded-lg bg-[#222] text-gray-300 hover:text-white text-sm transition-colors"
                @click="selectCategory(null)"
              >Ver todas las categorías</button>
            </div>

            <!-- Grid productos -->
            <div v-else class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-4">
              <RouterLink
                v-for="p in products"
                :key="p.id"
                :to="{ name: 'producto', params: { id: p.id } }"
                class="group bg-[#1a1a1a] rounded-xl overflow-hidden hover:bg-[#222] transition-colors border border-transparent hover:border-[#f2b02c]/20 flex flex-col"
              >
                <div class="aspect-square bg-[#111] overflow-hidden">
                  <img
                    v-if="p.imagen_url"
                    :src="p.imagen_url"
                    :alt="p.details"
                    loading="lazy"
                    class="w-full h-full object-contain p-2 transition-transform duration-300 group-hover:scale-105"
                    @error="(e) => e.target.style.display = 'none'"
                  />
                  <div v-else class="w-full h-full flex items-center justify-center">
                    <PhotoIcon class="w-10 h-10 text-gray-700" />
                  </div>
                </div>
                <div class="p-3 flex flex-col flex-1">
                  <p class="text-[10px] text-gray-500 font-mono mb-1">{{ p.product_code }}</p>
                  <p class="text-sm text-gray-200 leading-snug line-clamp-2 group-hover:text-white transition-colors flex-1">
                    {{ p.details }}
                  </p>
                  <p v-if="p.precio" class="text-base font-semibold text-[#f2b02c] mt-2">
                    {{ formatPrice(p.precio) }}
                  </p>
                </div>
              </RouterLink>
            </div>

            <!-- Paginación productos -->
            <div v-if="lastPage > 1" class="flex items-center justify-center gap-1 mt-8">
              <button
                :disabled="currentPage === 1"
                class="px-3 py-2 rounded-lg text-sm bg-[#1a1a1a] text-gray-400 hover:bg-[#222] disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                @click="goToPage(currentPage - 1)"
              ><ChevronLeftIcon class="w-4 h-4" /></button>

              <template v-for="p in paginationPages" :key="p">
                <span v-if="p === '...'" class="px-2 py-2 text-gray-600 text-sm select-none">…</span>
                <button
                  v-else
                  :class="['w-9 h-9 rounded-lg text-sm font-medium transition-colors',
                    p === currentPage
                      ? 'bg-[#f2b02c] text-black'
                      : 'bg-[#1a1a1a] text-gray-400 hover:bg-[#222] hover:text-white']"
                  @click="goToPage(p)"
                >{{ p }}</button>
              </template>

              <button
                :disabled="currentPage === lastPage"
                class="px-3 py-2 rounded-lg text-sm bg-[#1a1a1a] text-gray-400 hover:bg-[#222] disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                @click="goToPage(currentPage + 1)"
              ><ChevronRightIcon class="w-4 h-4" /></button>
            </div>

          </template>

        </section>
      </div>
    </main>

    <AppFooter />
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import {
  MagnifyingGlassIcon,
  PhotoIcon,
  FunnelIcon,
  XMarkIcon,
  ChevronDownIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
} from '@heroicons/vue/24/outline';
import AppHeader from '@/layout/AppHeader.vue';
import AppFooter from '@/layout/AppFooter.vue';

const route  = useRoute();
const router = useRouter();

const CATS_PER_PAGE = 24;

// Estado
const products         = ref([]);
const divisions        = ref([]);
const categories       = ref([]);
const loading          = ref(false);
const loadingCategories = ref(true);
const total            = ref(0);
const currentPage      = ref(1);
const lastPage         = ref(1);
const categoryPage     = ref(1);
const sidebarOpen      = ref(false);
const openDivisiones   = ref(true);
const openCategorias   = ref(true);

// Filtros activos (sincronizados con URL)
const activeDivision = ref(null);
const activeCategory = ref(null);
const searchQuery    = ref('');

// Modo actual
const isProductMode = computed(() =>
  activeCategory.value !== null || searchQuery.value !== ''
);

const hasActiveFilters = computed(() =>
  activeDivision.value !== null || activeCategory.value !== null || searchQuery.value !== ''
);

const divisionName = computed(() =>
  divisions.value.find(d => d.id === activeDivision.value)?.name ?? ''
);

const categoryName = computed(() =>
  categories.value.find(c => c.id === activeCategory.value)?.name ?? ''
);

// Paginación del grid de categorías (client-side)
const categoryLastPage = computed(() =>
  Math.max(1, Math.ceil(categories.value.length / CATS_PER_PAGE))
);

const pagedCategories = computed(() => {
  const start = (categoryPage.value - 1) * CATS_PER_PAGE;
  return categories.value.slice(start, start + CATS_PER_PAGE);
});

const categoryPaginationPages = computed(() => buildPages(categoryPage.value, categoryLastPage.value));

const paginationPages = computed(() => buildPages(currentPage.value, lastPage.value));

function buildPages(cur, tot) {
  if (tot <= 7) return Array.from({ length: tot }, (_, i) => i + 1);
  const pages = new Set([1, tot, cur - 1, cur, cur + 1].filter(p => p >= 1 && p <= tot));
  const sorted = [...pages].sort((a, b) => a - b);
  const result = [];
  for (let i = 0; i < sorted.length; i++) {
    if (i > 0 && sorted[i] - sorted[i - 1] > 1) result.push('...');
    result.push(sorted[i]);
  }
  return result;
}

const formatPrice = (price) =>
  new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(price);

// Fetch
async function fetchProducts() {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (activeDivision.value) params.set('division', activeDivision.value);
    if (activeCategory.value)  params.set('categoria', activeCategory.value);
    if (searchQuery.value)     params.set('q', searchQuery.value);
    if (currentPage.value > 1) params.set('page', currentPage.value);

    const res  = await fetch(`/api/catalogo?${params}`);
    const data = await res.json();
    products.value    = data.data;
    total.value       = data.total;
    currentPage.value = data.current_page;
    lastPage.value    = data.last_page;
  } finally {
    loading.value = false;
  }
}

async function fetchDivisions() {
  const res = await fetch('/api/divisiones');
  divisions.value = await res.json();
}

async function fetchCategories() {
  loadingCategories.value = true;
  categoryPage.value = 1;
  try {
    const params = activeDivision.value ? `?division=${activeDivision.value}` : '';
    const res = await fetch(`/api/categorias${params}`);
    categories.value = await res.json();
  } finally {
    loadingCategories.value = false;
  }
}

// Sincronizar URL → estado
function syncFromRoute() {
  activeDivision.value = route.query.division  ? Number(route.query.division)  : null;
  activeCategory.value = route.query.categoria ? Number(route.query.categoria) : null;
  searchQuery.value    = route.query.q ?? '';
  currentPage.value    = route.query.page ? Number(route.query.page) : 1;
}

function pushRoute() {
  const query = {};
  if (activeDivision.value) query.division  = activeDivision.value;
  if (activeCategory.value) query.categoria = activeCategory.value;
  if (searchQuery.value)    query.q         = searchQuery.value;
  if (currentPage.value > 1) query.page     = currentPage.value;
  router.push({ name: 'catalogo', query });
}

// Acciones
async function selectDivision(id) {
  activeDivision.value = id;
  activeCategory.value = null;
  currentPage.value    = 1;
  sidebarOpen.value    = false;
  await fetchCategories();
  if (isProductMode.value) fetchProducts();
  pushRoute();
}

function selectCategory(id) {
  activeCategory.value = id;
  currentPage.value    = 1;
  sidebarOpen.value    = false;
  pushRoute();
}

function clearFilters() {
  activeDivision.value = null;
  activeCategory.value = null;
  searchQuery.value    = '';
  currentPage.value    = 1;
  fetchCategories();
  pushRoute();
}

function clearSearch() {
  searchQuery.value = '';
  currentPage.value = 1;
  pushRoute();
}

function goToPage(page) {
  currentPage.value = page;
  pushRoute();
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function goToCategoryPage(page) {
  categoryPage.value = page;
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Watch cambios de URL
watch(() => route.query, async (q, prev) => {
  const divChanged = q.division !== prev?.division;
  syncFromRoute();
  if (divChanged) await fetchCategories();
  if (isProductMode.value) fetchProducts();
}, { immediate: false });

onMounted(async () => {
  syncFromRoute();
  await Promise.all([fetchDivisions(), fetchCategories()]);
  if (isProductMode.value) fetchProducts();
});
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from,
.fade-leave-to     { opacity: 0; }
</style>
