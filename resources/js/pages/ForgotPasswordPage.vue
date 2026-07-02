<template>
  <div class="min-h-screen flex flex-col bg-gray-50">

    <AppHeader />

    <main class="flex-1 flex items-start justify-center px-4 py-10">
      <div class="w-full max-w-md">

        <!-- Volver al login -->
        <RouterLink to="/login"
                    class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-[#f37e2b] transition-colors mb-6">
          <ChevronLeftIcon class="w-4 h-4" />
          Regresar al inicio de sesión
        </RouterLink>

        <div class="bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden">

          <!-- Logo -->
          <div class="flex justify-center pt-8 pb-6">
            <a href="/">
              <img src="/images/Logo-negro.webp" alt="Berny Distribuidora" class="h-16 w-auto object-contain" />
            </a>
          </div>

          <div class="px-6 pb-8">

            <h2 class="text-center text-lg font-bold text-[#222222] mb-1">Recuperar contraseña</h2>
            <p class="text-center text-sm text-gray-500 mb-6">
              Ingresa tu correo o clave de cliente y te enviaremos instrucciones.
            </p>

            <!-- Mensaje de éxito -->
            <div v-if="successMessage"
                 class="mb-5 px-4 py-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg">
              {{ successMessage }}
            </div>

            <!-- Error -->
            <div v-if="serverError"
                 class="mb-5 px-4 py-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">
              {{ serverError }}
            </div>

            <form v-if="!successMessage" method="POST" action="/recuperar-password" @submit="onSubmit">
              <input type="hidden" name="_token" :value="csrfToken">

              <div class="mb-6">
                <label for="email" class="block text-sm font-medium text-[#222222] mb-1.5">
                  Correo electrónico o código de cliente
                </label>
                <input
                  id="email"
                  name="email"
                  type="text"
                  placeholder="tucorreo@ejemplo.com  o  CLI-00123"
                  :value="oldEmail"
                  autocomplete="email"
                  class="w-full px-4 py-3 border-2 rounded-xl text-sm transition-colors focus:outline-none"
                  :class="hasError
                    ? 'border-red-400 focus:border-red-500 bg-red-50'
                    : 'border-gray-200 focus:border-[#f2b02c]'"
                  autofocus
                />
                <p v-if="hasError" class="mt-1 text-xs text-red-600">{{ fieldError }}</p>
              </div>

              <button type="submit"
                      class="w-full py-3 bg-[#f37e2b] hover:bg-[#e06d1e] active:bg-[#c85d14] text-white font-bold rounded-full text-sm transition-colors shadow-md">
                Reestablecer
              </button>
            </form>

          </div>
        </div>

      </div>
    </main>

    <AppFooter />

  </div>
</template>

<script setup>
import { computed } from 'vue';
import { ChevronLeftIcon } from '@heroicons/vue/24/outline';
import AppHeader from '@/layout/AppHeader.vue';
import AppFooter from '@/layout/AppFooter.vue';

const csrfToken    = window.appData?.csrfToken ?? '';
const serverErrors = window.appData?.errors ?? {};
const oldInputs    = window.appData?.old ?? {};
const flash        = window.appData?.flash ?? {};

const successMessage = computed(() => flash.success ?? '');
const serverError    = computed(() => serverErrors.email?.[0] ?? '');
const fieldError     = computed(() => serverErrors.email?.[0] ?? '');
const hasError       = computed(() => !!fieldError.value);
const oldEmail       = computed(() => oldInputs.email ?? '');
</script>
