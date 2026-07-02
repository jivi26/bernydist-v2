<template>
  <div class="min-h-screen flex flex-col bg-gray-50">

    <AppHeader />

    <main class="flex-1 flex items-start justify-center px-4 py-10">
      <div class="w-full max-w-md">

        <!-- Volver -->
        <button @click="$router.back()"
                class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-[#f37e2b] transition-colors mb-6">
          <ChevronLeftIcon class="w-4 h-4" />
          Volver
        </button>

        <div class="bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden">

          <!-- Logo -->
          <div class="flex justify-center pt-8 pb-6">
            <a href="/">
              <img src="/images/Logo-negro.webp" alt="Berny Distribuidora" class="h-16 w-auto object-contain" />
            </a>
          </div>

          <div class="px-6 pb-8">

            <!-- Subtítulo -->
            <p class="text-center text-sm text-gray-500 mb-6">
              ¿Eres cliente nuevo?
              <a href="/registro" class="text-[#f37e2b] hover:underline font-medium">Empieza aquí.</a>
            </p>

            <!-- Error del servidor -->
            <div v-if="serverError"
                 class="mb-5 px-4 py-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">
              {{ serverError }}
            </div>

            <!-- Formulario -->
            <form method="POST" action="/login" @submit="onSubmit">
              <input type="hidden" name="_token" :value="csrfToken">

              <!-- Correo o clave de cliente -->
              <div class="mb-4">
                <label for="code" class="block text-sm font-medium text-[#222222] mb-1.5">
                  Correo electrónico o código de cliente
                </label>
                <input
                  id="code"
                  name="code"
                  type="text"
                  placeholder="tucorreo@ejemplo.com  o  CLI-00123"
                  :value="oldCode"
                  autocomplete="username"
                  class="w-full px-4 py-3 border-2 rounded-xl text-sm transition-colors focus:outline-none"
                  :class="hasCodeError
                    ? 'border-red-400 focus:border-red-500 bg-red-50'
                    : 'border-gray-200 focus:border-[#f2b02c]'"
                  autofocus
                />
                <p v-if="hasCodeError" class="mt-1 text-xs text-red-600">{{ codeError }}</p>
              </div>

              <!-- Contraseña -->
              <div class="mb-6">
                <label for="password" class="block text-sm font-medium text-[#222222] mb-1.5">
                  Contraseña
                </label>
                <div class="relative">
                  <input
                    id="password"
                    name="password"
                    :type="showPassword ? 'text' : 'password'"
                    placeholder="••••••••"
                    autocomplete="current-password"
                    class="w-full px-4 py-3 pr-11 border-2 rounded-xl text-sm transition-colors focus:outline-none"
                    :class="hasPasswordError
                      ? 'border-red-400 focus:border-red-500 bg-red-50'
                      : 'border-gray-200 focus:border-[#f2b02c]'"
                  />
                  <button type="button"
                          @click="showPassword = !showPassword"
                          class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
                    <EyeIcon v-if="!showPassword" class="w-5 h-5" />
                    <EyeSlashIcon v-else class="w-5 h-5" />
                  </button>
                </div>
                <p v-if="hasPasswordError" class="mt-1 text-xs text-red-600">{{ passwordError }}</p>
              </div>

              <!-- Botón Entrar -->
              <button type="submit"
                      :disabled="submitting"
                      class="w-full py-3 text-white font-bold rounded-full text-sm transition-colors shadow-md"
                      :class="submitting
                        ? 'bg-gray-400 cursor-not-allowed'
                        : 'bg-[#f37e2b] hover:bg-[#e06d1e] active:bg-[#c85d14]'">
                <span v-if="submitting">Entrando…</span>
                <span v-else>Entrar</span>
              </button>
            </form>

            <!-- Recuperar contraseña -->
            <div class="mt-6 pt-5 border-t border-gray-100 text-center text-sm text-gray-500">
              Olvidaste tu contraseña o no cuentas con una,
              <RouterLink to="/recuperar-password" class="text-[#f37e2b] hover:underline font-medium">
                recuperar aquí
              </RouterLink>
            </div>

          </div>
        </div>

      </div>
    </main>

    <AppFooter />

  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { ChevronLeftIcon, EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/outline';
import AppHeader from '@/layout/AppHeader.vue';
import AppFooter from '@/layout/AppFooter.vue';

const csrfToken    = window.appData?.csrfToken ?? '';
const serverErrors = window.appData?.errors ?? {};
const oldInputs    = window.appData?.old ?? {};

const showPassword = ref(false);
const submitting   = ref(false);
const oldCode      = computed(() => oldInputs.code ?? '');

const serverError      = computed(() => serverErrors.code?.[0] ?? serverErrors.password?.[0] ?? '');
const codeError        = computed(() => serverErrors.code?.[0] ?? '');
const passwordError    = computed(() => serverErrors.password?.[0] ?? '');
const hasCodeError     = computed(() => !!codeError.value);
const hasPasswordError = computed(() => !!passwordError.value);

function onSubmit() {
    // Usamos setTimeout(0) para no interferir con la navegación nativa.
    // Vue actualiza el DOM en microtasks; si deshabilitamos el botón antes
    // de que el browser procese el submit, algunos browsers cancelan el form.
    setTimeout(() => { submitting.value = true; }, 0);
}
</script>
