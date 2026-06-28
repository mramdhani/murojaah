<template>
  <div id="app">
    <!-- Splash Screen during authentication initialization (max 5s timeout) -->
    <div v-if="showSplash" class="app-loading">
      <div class="spinner-container">
        <div class="spinner"></div>
        <p>Mempersiapkan Murojaah...</p>
      </div>
    </div>

    <template v-else>
      <NuxtPage />
      <BottomNav />
    </template>

    <Teleport to="body">
      <TransitionGroup name="toast">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          class="toast"
          :class="`toast--${toast.type}`"
        >
          {{ toast.message }}
        </div>
      </TransitionGroup>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { provide, ref, onMounted } from 'vue'

export interface ToastItem {
  id: number
  message: string
  type: 'fluent' | 'doubtful' | 'forgot' | 'info'
}

const toasts = ref<ToastItem[]>([])
let toastId = 0

const showToast = (message: string, type: ToastItem['type'] = 'info') => {
  const id = ++toastId
  toasts.value.push({ id, message, type })
  setTimeout(() => {
    toasts.value = toasts.value.filter(t => t.id !== id)
  }, 2000)
}

provide('showToast', showToast)

const { initSession, loading: authLoading, user } = useAuth()

// Safety: never show splash longer than 5 seconds
// in case backend is unreachable on VPS
const splashTimedOut = ref(false)
const showSplash = computed(() => authLoading.value && !user.value && !splashTimedOut.value)

onMounted(() => {
  initSession()
  // Force-hide splash after 5s even if backend is down
  setTimeout(() => { splashTimedOut.value = true }, 5000)
})
</script>

<style scoped>
#app {
  min-height: 100dvh;
}

.app-loading {
  position: fixed;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-bg);
  z-index: 9999;
}

.spinner-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}

.spinner {
  width: 48px;
  height: 48px;
  border: 4px solid rgba(5, 150, 105, 0.1);
  border-left-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

p {
  color: var(--color-text-secondary);
  font-size: 0.9375rem;
  font-weight: 500;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
