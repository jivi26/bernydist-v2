<template>
  <header class="bg-[#141414] shadow-sm sticky top-0 z-50">

    <!-- Barra superior: logo + búsqueda + acciones -->
    <div class="max-w-screen-xl mx-auto px-4 py-2.5 flex items-center gap-4">

      <!-- Hamburger: siempre en móvil, en desktop solo al hacer scroll -->
      <button v-show="showHamburger"
              @click="mobileMenuOpen = !mobileMenuOpen"
              class="p-2 text-gray-300 hover:text-[#f2b02c] transition-colors shrink-0">
        <Bars3Icon v-if="!mobileMenuOpen" class="w-6 h-6" />
        <XMarkIcon v-else class="w-6 h-6" />
      </button>

      <!-- Logo -->
      <RouterLink to="/mayoreo" class="shrink-0">
        <img src="/images/logo-black.png" alt="Berny Distribuidora"
             class="h-11 w-auto object-contain"
             @error="logoError = true"
             v-show="!logoError" />
        <span v-show="logoError" class="text-xl font-bold text-[#f2b02c] tracking-tight">BERNY</span>
      </RouterLink>

      <!-- Búsqueda (desktop/tablet) — centrada con mx-auto -->
      <div class="hidden md:flex flex-1">
        <div class="relative w-full max-w-xl mx-auto">
          <input
            v-model="searchQuery"
            type="search"
            :placeholder="searchPlaceholder"
            class="w-full pl-4 pr-10 py-2 bg-[#222222] border border-gray-600 text-gray-100 placeholder-gray-500 rounded-full text-sm focus:outline-none focus:border-[#f2b02c] focus:ring-1 focus:ring-[#f2b02c]"
            @keyup.enter="search"
          />
          <button @click="search"
                  :disabled="searchQuery.trim().length > 0 && searchQuery.trim().length < 3"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-[#f2b02c] transition-colors disabled:opacity-40">
            <MagnifyingGlassIcon class="w-4 h-4" />
          </button>
          <!-- Hint mínimo 3 chars -->
          <p v-if="searchQuery.trim().length > 0 && searchQuery.trim().length < 3"
             class="absolute left-4 -bottom-5 text-[10px] text-gray-500 pointer-events-none whitespace-nowrap">
            Mínimo 3 caracteres
          </p>
        </div>
      </div>

      <!-- Acciones: búsqueda móvil + carrito + distribuidor + usuario -->
      <div class="flex items-center gap-2 shrink-0 ml-auto">

        <!-- Búsqueda (móvil) -->
        <button @click="mobileSearchOpen = !mobileSearchOpen"
                class="md:hidden p-2 text-gray-300 hover:text-[#f2b02c] transition-colors">
          <MagnifyingGlassIcon class="w-5 h-5" />
        </button>

        <!-- Carrito -->
        <a :href="user ? '/spages/confirmar_pedido' : '/login'"
           class="relative p-2 text-gray-300 hover:text-[#f2b02c] transition-colors">
          <ShoppingCartIcon class="w-6 h-6" />
          <span v-if="cartCount > 0"
                class="absolute -top-1 -right-1 bg-[#f37e2b] text-white text-xs rounded-full w-4 h-4 flex items-center justify-center font-bold leading-none">
            {{ cartCount }}
          </span>
          <span v-if="!user" class="hidden sm:inline text-xs font-medium ml-1 text-gray-400">$0.00</span>
        </a>

        <!-- Nivel de precio (gráfico de pastel) — solo venta de contado -->
        <a v-if="user && user.solo_vta_contado === 'S'"
           href="/panel/pedido"
           title="Nivel de precio alcanzado"
           class="relative p-2 text-gray-300 hover:text-[#f2b02c] transition-colors">
          <ChartPieIcon class="w-6 h-6" />
          <span class="absolute -top-1 -right-1 bg-[#ffd500] text-[#141414] text-[10px] rounded-full w-4 h-4 flex items-center justify-center font-bold leading-none">
            {{ user.price_level_reached }}
          </span>
        </a>

        <!-- Campanita de notificaciones -->
        <div v-if="user" class="relative" ref="bellMenuRef">
          <button @click="bellOpen = !bellOpen"
                  title="Notificaciones"
                  class="relative p-2 transition-colors"
                  :class="notifTotal > 0 ? 'text-[#ffd500]' : 'text-gray-300 hover:text-[#f2b02c]'">
            <BellIcon class="w-6 h-6" />
            <span v-if="notifTotal > 0"
                  class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] rounded-full w-4 h-4 flex items-center justify-center font-bold leading-none">
              {{ notifTotal }}
            </span>
          </button>

          <!-- Dropdown notificaciones -->
          <div v-show="bellOpen"
               class="absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-xl border border-gray-100 py-1 text-sm z-50">
            <div class="px-4 py-3 border-b border-gray-100">
              <p class="font-semibold text-[#141414]">Notificaciones</p>
              <p class="text-xs text-gray-400 mt-0.5">
                {{ notifTotal > 0 ? `${notifTotal} pendiente${notifTotal !== 1 ? 's' : ''}` : 'Al corriente' }}
              </p>
            </div>
            <div v-if="notifTotal === 0" class="px-4 py-3 text-gray-400 text-xs text-center">
              Sin notificaciones pendientes
            </div>
            <template v-else>
              <a v-if="notifications.pedidos > 0" href="/panel/pedidos"
                 class="flex items-center gap-3 px-4 py-2.5 hover:bg-amber-50 text-[#222222]">
                <span class="w-2 h-2 rounded-full bg-red-500 shrink-0"></span>
                ({{ notifications.pedidos }}) Pedido(s) pendiente(s)
              </a>
              <a v-if="notifications.pagos > 0" href="/panel/pedidos/pago"
                 class="flex items-center gap-3 px-4 py-2.5 hover:bg-amber-50 text-[#222222]">
                <span class="w-2 h-2 rounded-full bg-red-500 shrink-0"></span>
                ({{ notifications.pagos }}) Pago(s) en línea
              </a>
              <a v-if="notifications.proceso > 0" href="/panel/pedidos/proceso"
                 class="flex items-center gap-3 px-4 py-2.5 hover:bg-amber-50 text-[#222222]">
                <span class="w-2 h-2 rounded-full bg-red-500 shrink-0"></span>
                ({{ notifications.proceso }}) Pedido(s) en proceso
              </a>
            </template>
          </div>
        </div>

        <!-- Usuario autenticado -->
        <div v-if="user" class="relative" ref="userMenuRef">
          <button @click="userMenuOpen = !userMenuOpen"
                  class="flex items-center gap-1.5 text-sm text-gray-200 hover:text-[#f2b02c] transition-colors">
            <UserCircleIcon class="w-6 h-6 shrink-0" />
            <span class="hidden sm:block font-medium max-w-[100px] truncate">
              {{ firstName }}
            </span>
            <ChevronDownIcon class="w-3.5 h-3.5" />
          </button>

          <!-- Dropdown usuario -->
          <div v-show="userMenuOpen"
               class="absolute right-0 mt-2 w-52 bg-white rounded-lg shadow-xl border border-gray-100 py-1 text-sm z-50">
            <div class="px-4 py-3 border-b border-gray-100">
              <p class="font-semibold text-[#141414] truncate">{{ user.nombre }}</p>
              <p class="text-xs text-gray-400 mt-0.5">{{ user.clave }}</p>
            </div>
            <a href="/panel/mis-datos"
               class="flex items-center gap-2 px-4 py-2 text-[#222222] hover:bg-amber-50 hover:text-[#f37e2b]">
              <UserIcon class="w-4 h-4 shrink-0" /> Mis datos
            </a>
            <a href="/panel"
               class="flex items-center gap-2 px-4 py-2 text-[#222222] hover:bg-amber-50 hover:text-[#f37e2b]">
              <ClipboardDocumentListIcon class="w-4 h-4 shrink-0" /> Mi cuenta
            </a>
            <a v-if="isAsesor" href="/panel/divisiones"
               class="flex items-center gap-2 px-4 py-2 text-[#222222] hover:bg-amber-50 hover:text-[#f37e2b]">
              <BuildingOfficeIcon class="w-4 h-4 shrink-0" /> Divisiones
            </a>
            <hr class="my-1 border-gray-100">
            <form method="POST" action="/logout">
              <input type="hidden" name="_token" :value="csrfToken">
              <button type="submit"
                      class="w-full flex items-center gap-2 px-4 py-2 text-[#222222] hover:bg-amber-50 hover:text-[#f37e2b]">
                <ArrowRightOnRectangleIcon class="w-4 h-4 shrink-0" /> Salir
              </button>
            </form>
          </div>
        </div>

      </div>
    </div>

    <!-- Búsqueda móvil expandida -->
    <div v-show="mobileSearchOpen" class="md:hidden px-4 pb-3">
      <div class="relative">
        <input
          v-model="searchQuery"
          type="search"
          placeholder="Buscar productos..."
          class="w-full pl-4 pr-10 py-2 bg-[#222222] border border-gray-600 text-gray-100 placeholder-gray-500 rounded-full text-sm focus:outline-none focus:border-[#f2b02c] focus:ring-1 focus:ring-[#f2b02c]"
          @keyup.enter="search"
        />
        <button @click="search"
                :disabled="searchQuery.trim().length > 0 && searchQuery.trim().length < 3"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-[#f2b02c] transition-colors disabled:opacity-40">
          <MagnifyingGlassIcon class="w-4 h-4" />
        </button>
        <p v-if="searchQuery.trim().length > 0 && searchQuery.trim().length < 3"
           class="absolute left-4 -bottom-5 text-[10px] text-gray-500 pointer-events-none">
          Mínimo 3 caracteres
        </p>
      </div>
    </div>

    <!-- Nav principal: solo en desktop y sin scroll -->
    <Transition
      enter-active-class="transition-all duration-200 ease-out"
      leave-active-class="transition-all duration-150 ease-in"
      enter-from-class="opacity-0 -translate-y-1"
      enter-to-class="opacity-100 translate-y-0"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 -translate-y-1">
      <nav v-if="showNavBar" class="bg-[#141414] text-white border-t border-gray-700">
        <div class="max-w-screen-xl mx-auto px-4">
          <ul class="flex items-center text-sm">
            <li>
              <RouterLink to="/mayoreo"
                class="block px-4 py-3 hover:bg-[#f2b02c] hover:text-[#141414] transition-colors whitespace-nowrap font-medium"
                active-class="bg-[#f2b02c] text-[#141414]">
                Inicio
              </RouterLink>
            </li>
            <li>
              <a href="/nosotros"
                 class="block px-4 py-3 hover:bg-[#f2b02c] hover:text-[#141414] transition-colors whitespace-nowrap font-medium">
                Nosotros
              </a>
            </li>
            <li v-if="user">
              <RouterLink to="/promociones"
                class="block px-4 py-3 hover:bg-[#f2b02c] hover:text-[#141414] transition-colors whitespace-nowrap font-medium"
                active-class="bg-[#f2b02c] text-[#141414]">
                Promociones
              </RouterLink>
            </li>
            <li v-if="user">
              <a href="/panel"
                 class="block px-4 py-3 hover:bg-[#f2b02c] hover:text-[#141414] transition-colors whitespace-nowrap font-medium">
                Mi cuenta
              </a>
            </li>
            <li v-if="isAsesor">
              <a href="/panel/divisiones"
                 class="block px-4 py-3 hover:bg-[#f2b02c] hover:text-[#141414] transition-colors whitespace-nowrap font-medium">
                Divisiones
              </a>
            </li>
            <li class="flex-1" />
            <!-- Video modal -->
            <li v-if="videoEmbedUrl" class="flex items-center">
              <button @click="videoOpen = true"
                      title="Ver video"
                      class="flex items-center gap-1.5 px-3 py-3 text-[#f2b02c] hover:text-white transition-colors whitespace-nowrap text-sm font-medium">
                <PlayCircleIcon class="w-5 h-5" />
                <span class="hidden xl:inline">Video</span>
              </button>
            </li>
            <!-- Bajar catálogo (solo distribuidores/CR sin login de asesor) -->
            <li v-if="showCatalogo" class="flex items-center px-2">
              <a href="/descargar-catalogo"
                 class="flex items-center gap-1.5 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold px-4 py-1.5 rounded-full transition-colors whitespace-nowrap">
                <ArrowDownTrayIcon class="w-4 h-4" />
                Bajar catálogo
              </a>
            </li>
            <!-- Distribuidor -->
            <li class="flex items-center px-2">
              <a :href="user ? user.distrib_url : '/pages/distributed'"
                 class="block px-4 py-3 text-[#f2b02c] hover:bg-[#f2b02c] hover:text-[#141414] transition-colors whitespace-nowrap font-medium text-sm">
                {{ user ? user.distrib_label : 'Quiero Ser Distribuidor' }}
              </a>
            </li>
            <!-- Iniciar sesión / Salir -->
            <li v-if="!user" class="flex items-center px-3">
              <a href="/login"
                 class="flex items-center gap-1.5 bg-[#f37e2b] hover:bg-[#e06d1e] text-white text-sm font-semibold px-5 py-1.5 rounded-full transition-colors whitespace-nowrap">
                <ArrowRightOnRectangleIcon class="w-4 h-4" />
                Iniciar sesión
              </a>
            </li>
            <li v-else>
              <form method="POST" action="/logout" class="inline">
                <input type="hidden" name="_token" :value="csrfToken">
                <button type="submit"
                        class="block px-4 py-3 text-gray-400 hover:bg-[#f2b02c] hover:text-[#141414] transition-colors whitespace-nowrap font-medium">
                  Salir
                </button>
              </form>
            </li>
          </ul>
        </div>
      </nav>
    </Transition>

    <!-- Menú desplegable: móvil siempre + desktop cuando hay scroll -->
    <Transition
      enter-active-class="transition-all duration-200 ease-out overflow-hidden"
      leave-active-class="transition-all duration-150 ease-in overflow-hidden"
      enter-from-class="opacity-0 -translate-y-2"
      enter-to-class="opacity-100 translate-y-0"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 -translate-y-2">
      <div v-show="mobileMenuOpen" class="bg-[#141414] text-white border-t border-gray-700">
        <ul class="px-4 py-2 space-y-0.5 max-w-screen-xl mx-auto">
          <li>
            <RouterLink to="/mayoreo"
              class="block px-3 py-2.5 rounded text-sm font-medium hover:bg-[#f2b02c] hover:text-[#141414] transition-colors"
              @click="mobileMenuOpen = false">
              Inicio
            </RouterLink>
          </li>
          <li>
            <a href="/nosotros"
               class="block px-3 py-2.5 rounded text-sm font-medium hover:bg-[#f2b02c] hover:text-[#141414] transition-colors">
              Nosotros
            </a>
          </li>
          <li v-if="user">
            <RouterLink to="/promociones"
              class="block px-3 py-2.5 rounded text-sm font-medium hover:bg-[#f2b02c] hover:text-[#141414] transition-colors"
              @click="mobileMenuOpen = false">
              Promociones
            </RouterLink>
          </li>
          <li v-if="user">
            <a href="/panel"
               class="block px-3 py-2.5 rounded text-sm font-medium hover:bg-[#f2b02c] hover:text-[#141414] transition-colors">
              Mi cuenta
            </a>
          </li>
          <li v-if="isAsesor">
            <a href="/panel/divisiones"
               class="block px-3 py-2.5 rounded text-sm font-medium hover:bg-[#f2b02c] hover:text-[#141414] transition-colors">
              Divisiones
            </a>
          </li>
          <!-- Bajar catálogo (móvil) -->
          <li v-if="showCatalogo" class="pt-2 border-t border-gray-700">
            <a href="/descargar-catalogo"
               class="flex items-center gap-2 px-3 py-2.5 rounded text-sm font-bold text-green-400 hover:bg-green-600 hover:text-white transition-colors"
               @click="mobileMenuOpen = false">
              <ArrowDownTrayIcon class="w-4 h-4 shrink-0" />
              Bajar catálogo
            </a>
          </li>
          <li class="pt-2 border-t border-gray-700">
            <a v-if="!user"
               href="/pages/distributed"
               class="block px-3 py-2.5 rounded text-sm font-bold text-[#f37e2b] hover:bg-[#f37e2b] hover:text-white transition-colors">
              Quiero Ser Distribuidor
            </a>
            <a v-else
               :href="user.distrib_url"
               class="block px-3 py-2.5 rounded text-sm font-bold text-[#f2b02c] hover:bg-[#f2b02c] hover:text-[#141414] transition-colors">
              {{ user.distrib_label }}
            </a>
          </li>
          <li>
            <a v-if="!user"
               href="/login"
               class="block px-3 py-2.5 rounded text-sm font-semibold text-[#f2b02c] hover:bg-[#f2b02c] hover:text-[#141414] transition-colors">
              Ingresar
            </a>
            <form v-else method="POST" action="/logout">
              <input type="hidden" name="_token" :value="csrfToken">
              <button type="submit"
                      class="w-full text-left px-3 py-2.5 rounded text-sm font-medium text-gray-400 hover:bg-[#f2b02c] hover:text-[#141414] transition-colors">
                Salir
              </button>
            </form>
          </li>
        </ul>
      </div>
    </Transition>

  </header>

  <!-- Modal de video (teleportado para evitar z-index del sticky header) -->
  <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-200"
      leave-active-class="transition-opacity duration-150"
      enter-from-class="opacity-0"
      leave-to-class="opacity-0">
      <div v-if="videoOpen"
           class="fixed inset-0 z-[200] flex items-center justify-center bg-black/80 backdrop-blur-sm"
           @click.self="closeVideo">
        <div class="relative w-full max-w-3xl mx-4">
          <!-- Botón cerrar -->
          <button @click="closeVideo"
                  class="absolute -top-10 right-0 text-white hover:text-[#f2b02c] transition-colors">
            <XMarkIcon class="w-8 h-8" />
          </button>
          <!-- Iframe YouTube -->
          <div class="aspect-video w-full rounded-xl overflow-hidden shadow-2xl">
            <iframe :src="videoEmbedUrl"
                    class="w-full h-full"
                    frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import {
    MagnifyingGlassIcon,
    ShoppingCartIcon,
    UserCircleIcon,
    UserIcon,
    ChevronDownIcon,
    ClipboardDocumentListIcon,
    ArrowRightOnRectangleIcon,
    BuildingOfficeIcon,
    Bars3Icon,
    XMarkIcon,
    PlayCircleIcon,
    BellIcon,
    ChartPieIcon,
    ArrowDownTrayIcon,
} from '@heroicons/vue/24/outline';

const route            = useRoute();
const router           = useRouter();
const searchQuery      = ref('');
const cartCount        = ref(0);
const userMenuOpen     = ref(false);
const bellOpen         = ref(false);
const mobileMenuOpen   = ref(false);
const mobileSearchOpen = ref(false);
const userMenuRef      = ref(null);
const bellMenuRef      = ref(null);
const logoError        = ref(false);
const scrolled         = ref(false);
const isLargeScreen    = ref(true);
const videoOpen        = ref(false);

const user          = window.appData?.user ?? null;
const csrfToken     = window.appData?.csrfToken ?? '';
const notifications = window.appData?.notifications ?? { total: 0, pedidos: 0, pagos: 0, proceso: 0 };
const notifTotal    = computed(() => notifications.total ?? 0);

const firstName = computed(() => {
    if (!user?.nombre) return 'Mi cuenta';
    return user.nombre.split(' ')[0];
});

const isAsesor = computed(() => {
    return user && !isNaN(parseInt(user.asesor_login_id));
});

// Catálogo: solo para distribuidores (WD=88721, CR=187185) sin login de asesor
const showCatalogo = computed(() => {
    if (!user) return false;
    const tipo = String(user.tipo_cliente_id ?? '');
    return (tipo === '88721' || tipo === '187185') && !user.asesor_login_id;
});

// Nav bar: solo en desktop (lg) y sin scroll
const showNavBar    = computed(() => !scrolled.value && isLargeScreen.value);
// Hamburger: en móvil siempre, en desktop solo al hacer scroll
const showHamburger = computed(() => scrolled.value || !isLargeScreen.value);

const searchPlaceholder = computed(() => 'Buscar productos...');

// Video modal
const rawVideoUrl  = window.appData?.videos?.distribuidor ?? '';
const videoEmbedUrl = computed(() => {
    if (!rawVideoUrl) return '';
    if (rawVideoUrl.includes('youtube.com/embed/')) {
        return rawVideoUrl + (rawVideoUrl.includes('?') ? '&' : '?') + 'autoplay=1&rel=0';
    }
    const watchMatch = rawVideoUrl.match(/[?&]v=([^&]+)/);
    if (watchMatch) return `https://www.youtube.com/embed/${watchMatch[1]}?autoplay=1&rel=0`;
    const shortMatch = rawVideoUrl.match(/youtu\.be\/([^?&]+)/);
    if (shortMatch) return `https://www.youtube.com/embed/${shortMatch[1]}?autoplay=1&rel=0`;
    return '';
});
function closeVideo() { videoOpen.value = false; }

// Mínimo 3 chars antes de buscar — igual que el legacy (header.js:42)
function search() {
    const term = searchQuery.value.trim();
    if (term.length < 3) return;
    mobileSearchOpen.value = false;
    router.push({ name: 'catalogo', query: { q: term } });
}

// Sincronizar input con la búsqueda activa cuando se está en /catalogo
watch(() => route.name, (name) => {
    if (name === 'catalogo') {
        searchQuery.value = route.query.q ?? '';
    } else {
        searchQuery.value = '';
    }
}, { immediate: true });

watch(() => route.query.q, (q) => {
    if (route.name === 'catalogo') {
        searchQuery.value = q ?? '';
    }
});

function handleScroll() {
    scrolled.value = window.scrollY > 60;
}

function handleResize() {
    isLargeScreen.value = window.innerWidth >= 1024;
}

function handleClickOutside(e) {
    if (userMenuRef.value && !userMenuRef.value.contains(e.target)) {
        userMenuOpen.value = false;
    }
    if (bellMenuRef.value && !bellMenuRef.value.contains(e.target)) {
        bellOpen.value = false;
    }
}

// Al volver al tope (nav bar reaparece), cerrar el menú desplegable
watch(showNavBar, (visible) => {
    if (visible) mobileMenuOpen.value = false;
});

onMounted(() => {
    isLargeScreen.value = window.innerWidth >= 1024;
    window.addEventListener('scroll', handleScroll, { passive: true });
    window.addEventListener('resize', handleResize, { passive: true });
    document.addEventListener('click', handleClickOutside);
});

onBeforeUnmount(() => {
    window.removeEventListener('scroll', handleScroll);
    window.removeEventListener('resize', handleResize);
    document.removeEventListener('click', handleClickOutside);
});
</script>
