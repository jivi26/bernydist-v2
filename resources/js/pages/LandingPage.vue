<template>
  <main class="min-h-screen relative flex flex-col items-center justify-center px-4 py-12 bg-[#141414]"
        :style="bgLoaded ? `background-image: url('/images/fondo.webp'); background-size: cover; background-position: center;` : ''">

    <!-- Overlay -->
    <div class="absolute inset-0 bg-black/60" aria-hidden="true" />

    <div class="relative z-10 w-full max-w-4xl mx-auto flex flex-col items-center gap-10">

      <!-- Bienvenida -->
      <div class="text-center">
        <img src="/images/bienvenido.webp" alt="Bienvenido"
             class="mx-auto h-16 sm:h-24 w-auto object-contain drop-shadow-lg"
             @error="bienvenidoError = true"
             v-show="!bienvenidoError" />
        <h1 v-show="bienvenidoError"
            class="text-3xl sm:text-4xl font-bold text-white drop-shadow-lg">
          Bienvenido
        </h1>
        <p class="mt-3 text-gray-200 text-lg drop-shadow">
          Selecciona el catálogo al que deseas ingresar
        </p>
      </div>

      <!-- Tarjetas de marcas -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 w-full">

        <!-- BernyDist -->
        <button @click="goTo('/mayoreo')"
                class="brand-card group bg-white rounded-2xl p-6 flex flex-col items-center gap-4 shadow-lg hover:shadow-2xl hover:-translate-y-1 transition-all duration-200 cursor-pointer border-2 border-transparent hover:border-[#f2b02c]">
          <div class="w-20 h-20 sm:w-24 sm:h-24 flex items-center justify-center rounded-xl overflow-hidden bg-gray-50">
            <img src="/images/logo_berny.webp" alt="BernyDist"
                 class="w-full h-full object-contain"
                 @error="bernyError = true"
                 v-show="!bernyError" />
            <span v-show="bernyError" class="text-lg font-black text-[#f2b02c]">BERNY</span>
          </div>
          <div class="text-center">
            <p class="font-bold text-[#222222] text-sm sm:text-base">BernyDist</p>
            <p class="text-xs text-[#777777] mt-0.5">Mayoreo</p>
          </div>
          <span class="text-xs font-semibold text-[#f37e2b] group-hover:text-[#e06d1e]">
            Entrar →
          </span>
        </button>

        <!-- Tecnolite -->
        <button @click="goTo('/tecnolite')"
                class="brand-card group bg-white rounded-2xl p-6 flex flex-col items-center gap-4 shadow-lg hover:shadow-2xl hover:-translate-y-1 transition-all duration-200 cursor-pointer border-2 border-transparent hover:border-[#f2b02c]">
          <div class="w-20 h-20 sm:w-24 sm:h-24 flex items-center justify-center rounded-xl overflow-hidden bg-gray-50">
            <img src="/images/logo_tecnolite.webp" alt="Tecnolite"
                 class="w-full h-full object-contain"
                 @error="tecnoError = true"
                 v-show="!tecnoError" />
            <span v-show="tecnoError" class="text-sm font-black text-blue-600">TECNOLITE</span>
          </div>
          <div class="text-center">
            <p class="font-bold text-[#222222] text-sm sm:text-base">Tecnolite</p>
            <p class="text-xs text-[#777777] mt-0.5">Iluminación</p>
          </div>
          <span class="text-xs font-semibold text-[#f37e2b] group-hover:text-[#e06d1e]">
            Entrar →
          </span>
        </button>

        <!-- Weston (externo) -->
        <a href="https://weston.com.mx" target="_blank" rel="noopener"
           class="brand-card group bg-white rounded-2xl p-6 flex flex-col items-center gap-4 shadow-lg hover:shadow-2xl hover:-translate-y-1 transition-all duration-200 border-2 border-transparent hover:border-[#f2b02c] no-underline">
          <div class="w-20 h-20 sm:w-24 sm:h-24 flex items-center justify-center rounded-xl overflow-hidden bg-gray-50">
            <img src="/images/logo_weston.webp" alt="Weston"
                 class="w-full h-full object-contain"
                 @error="westonError = true"
                 v-show="!westonError" />
            <span v-show="westonError" class="text-sm font-black text-red-600">WESTON</span>
          </div>
          <div class="text-center">
            <p class="font-bold text-[#222222] text-sm sm:text-base">Weston</p>
            <p class="text-xs text-[#777777] mt-0.5">Hogar & Cocina</p>
          </div>
          <span class="flex items-center gap-1 text-xs font-semibold text-[#f37e2b] group-hover:text-[#e06d1e]">
            Visitar sitio
            <ArrowTopRightOnSquareIcon class="w-3 h-3" />
          </span>
        </a>

        <!-- Silverline -->
        <button @click="goTo('/silverline')"
                class="brand-card group bg-white rounded-2xl p-6 flex flex-col items-center gap-4 shadow-lg hover:shadow-2xl hover:-translate-y-1 transition-all duration-200 cursor-pointer border-2 border-transparent hover:border-[#f2b02c]">
          <div class="w-20 h-20 sm:w-24 sm:h-24 flex items-center justify-center rounded-xl overflow-hidden bg-gray-50">
            <img src="/images/logo_silverline.webp" alt="Silverline"
                 class="w-full h-full object-contain"
                 @error="silverError = true"
                 v-show="!silverError" />
            <span v-show="silverError" class="text-sm font-black text-[#777777]">SILVERLINE</span>
          </div>
          <div class="text-center">
            <p class="font-bold text-[#222222] text-sm sm:text-base">Silverline</p>
            <p class="text-xs text-[#777777] mt-0.5">Ferretería</p>
          </div>
          <span class="text-xs font-semibold text-[#f37e2b] group-hover:text-[#e06d1e]">
            Entrar →
          </span>
        </button>

      </div>

      <!-- Ayuda -->
      <a href="/ayuda"
         class="flex items-center gap-2 text-gray-300 hover:text-[#f2b02c] text-sm transition-colors drop-shadow">
        <QuestionMarkCircleIcon class="w-5 h-5" />
        ¿Necesitas ayuda?
      </a>

    </div>
  </main>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { ArrowTopRightOnSquareIcon, QuestionMarkCircleIcon } from '@heroicons/vue/24/outline';

const router = useRouter();

const bgLoaded        = ref(false);
const bienvenidoError = ref(false);
const bernyError      = ref(false);
const tecnoError      = ref(false);
const westonError     = ref(false);
const silverError     = ref(false);

function goTo(path) {
    router.push(path);
}

onMounted(() => {
    const img = new Image();
    img.src = '/images/fondo.webp';
    img.onload  = () => { bgLoaded.value = true; };
    img.onerror = () => { bgLoaded.value = false; };
});
</script>
