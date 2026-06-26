<template>
  <header class="bg-white shadow-sm sticky top-0 z-50">

    <!-- Barra superior: logo + búsqueda + acciones -->
    <div class="max-w-screen-xl mx-auto px-4 py-3 flex items-center gap-4">

      <!-- Logo -->
      <RouterLink to="/mayoreo" class="shrink-0">
        <img src="/images/logo_berny.webp" alt="Berny Distribuidora"
             class="h-12 w-auto object-contain"
             @error="logoError = true"
             v-show="!logoError" />
        <span v-show="logoError"
              class="text-xl font-bold text-[#f2b02c] tracking-tight">
          BERNY
        </span>
      </RouterLink>

      <!-- Búsqueda -->
      <div class="flex-1 max-w-xl">
        <div class="relative">
          <input
            v-model="searchQuery"
            type="search"
            placeholder="Buscar productos..."
            class="w-full pl-4 pr-10 py-2 border border-gray-300 rounded-full text-sm focus:outline-none focus:border-[#f2b02c] focus:ring-1 focus:ring-[#f2b02c]"
            @keyup.enter="search"
          />
          <button @click="search"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-[#f37e2b] transition-colors">
            <MagnifyingGlassIcon class="w-4 h-4" />
          </button>
        </div>
      </div>

      <!-- Acciones: carrito + usuario -->
      <div class="flex items-center gap-3 shrink-0">

        <!-- Carrito -->
        <button class="relative p-2 text-gray-600 hover:text-[#f37e2b] transition-colors">
          <ShoppingCartIcon class="w-6 h-6" />
          <span v-if="cartCount > 0"
                class="absolute -top-1 -right-1 bg-[#f37e2b] text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold">
            {{ cartCount }}
          </span>
        </button>

        <!-- Usuario autenticado -->
        <div v-if="user" class="relative" ref="userMenuRef">
          <button @click="userMenuOpen = !userMenuOpen"
                  class="flex items-center gap-2 text-sm text-[#222222] hover:text-[#f37e2b] transition-colors">
            <UserCircleIcon class="w-6 h-6" />
            <span class="hidden sm:block font-medium max-w-[120px] truncate">
              {{ user.nombre || 'Mi cuenta' }}
            </span>
            <ChevronDownIcon class="w-4 h-4" />
          </button>

          <!-- Dropdown usuario -->
          <div v-show="userMenuOpen"
               class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-100 py-1 text-sm">
            <a href="/panel"
               class="flex items-center gap-2 px-4 py-2 text-[#222222] hover:bg-amber-50 hover:text-[#f37e2b]">
              <UserIcon class="w-4 h-4" /> Mi cuenta
            </a>
            <a href="/panel/pedidos"
               class="flex items-center gap-2 px-4 py-2 text-[#222222] hover:bg-amber-50 hover:text-[#f37e2b]">
              <ClipboardDocumentListIcon class="w-4 h-4" /> Mis pedidos
            </a>
            <hr class="my-1 border-gray-100">
            <form method="POST" action="/logout">
              <input type="hidden" name="_token" :value="csrfToken">
              <button type="submit"
                      class="w-full flex items-center gap-2 px-4 py-2 text-[#222222] hover:bg-amber-50 hover:text-[#f37e2b]">
                <ArrowRightOnRectangleIcon class="w-4 h-4" /> Cerrar sesión
              </button>
            </form>
          </div>
        </div>

        <!-- No autenticado -->
        <a v-else href="/login"
           class="flex items-center gap-1.5 bg-[#f37e2b] hover:bg-[#e06d1e] text-white text-sm font-semibold px-4 py-2 rounded-full transition-colors">
          <ArrowRightOnRectangleIcon class="w-4 h-4" />
          Iniciar sesión
        </a>
      </div>
    </div>

    <!-- Barra de categorías -->
    <nav class="bg-[#141414] text-white">
      <div class="max-w-screen-xl mx-auto px-4">
        <ul class="flex items-center text-sm overflow-x-auto scrollbar-hide">
          <li v-for="cat in navCategories" :key="cat.id">
            <RouterLink
              :to="`/catalogo?categoria=${cat.id}`"
              class="block px-4 py-2.5 hover:bg-[#f2b02c] hover:text-[#141414] transition-colors whitespace-nowrap font-medium">
              {{ cat.nombre }}
            </RouterLink>
          </li>
          <li>
            <RouterLink to="/catalogo"
                        class="block px-4 py-2.5 text-[#f2b02c] hover:bg-[#f2b02c] hover:text-[#141414] transition-colors whitespace-nowrap font-semibold">
              Ver todo →
            </RouterLink>
          </li>
        </ul>
      </div>
    </nav>

  </header>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import {
    MagnifyingGlassIcon,
    ShoppingCartIcon,
    UserCircleIcon,
    UserIcon,
    ChevronDownIcon,
    ClipboardDocumentListIcon,
    ArrowRightOnRectangleIcon,
} from '@heroicons/vue/24/outline';

const router       = useRouter();
const searchQuery  = ref('');
const cartCount    = ref(0);
const userMenuOpen = ref(false);
const userMenuRef  = ref(null);
const logoError    = ref(false);

const user      = window.appData?.user ?? null;
const csrfToken = window.appData?.csrfToken ?? '';

const navCategories = ref([
    { id: 1, nombre: 'Herramientas' },
    { id: 2, nombre: 'Ferretería' },
    { id: 3, nombre: 'Construcción' },
    { id: 4, nombre: 'Eléctrico' },
    { id: 5, nombre: 'Plomería' },
    { id: 6, nombre: 'Seguridad' },
    { id: 7, nombre: 'Pintura' },
    { id: 8, nombre: 'Jardín' },
]);

function search() {
    if (searchQuery.value.trim()) {
        router.push({ path: '/catalogo', query: { q: searchQuery.value.trim() } });
    }
}

function handleClickOutside(e) {
    if (userMenuRef.value && !userMenuRef.value.contains(e.target)) {
        userMenuOpen.value = false;
    }
}

onMounted(() => document.addEventListener('click', handleClickOutside));
onBeforeUnmount(() => document.removeEventListener('click', handleClickOutside));
</script>
