<template>
  <div id="app">
    <!-- Splash Screen during authentication initialization (max 5s timeout) -->
    <div v-if="showSplash" class="app-loading">
      <div class="splash-container">
        <img src="/logo.png" alt="Murojaah Logo" class="splash-logo" />
        <div class="splash-loader">
          <div class="spinner"></div>
          <p>Mempersiapkan murojaah...</p>
        </div>
      </div>
    </div>

    <template v-else>
      <NuxtPage />
      <BottomNav />
      <MurojaahDrawer />
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

const { initSession, loading: authLoading, user, token } = useAuth()
const { initTheme } = useTheme()

// Safety: never show splash longer than 5 seconds
// in case backend is unreachable on VPS
const splashTimedOut = ref(false)
const showSplash = computed(() => {
  // If no auth token exists, do not show splash screen (prevents cold-start blocks for guests/new visitors)
  if (!token.value) return false
  return authLoading.value && !user.value && !splashTimedOut.value
})

onMounted(() => {
  initSession()
  initTheme()
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

.splash-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 28px;
  animation: fadeIn 0.5s ease-out;
}

.splash-logo {
  width: 90px;
  height: 90px;
  object-fit: contain;
  filter: drop-shadow(0 8px 16px rgba(5, 150, 105, 0.15));
  animation: pulse-slow 2s infinite ease-in-out;
}

.splash-loader {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}

.spinner {
  width: 32px;
  height: 32px;
  border: 3px solid rgba(5, 150, 105, 0.12);
  border-left-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

p {
  color: var(--color-text-secondary);
  font-size: 0.875rem;
  font-weight: 550;
  letter-spacing: -0.01em;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@keyframes pulse-slow {
  0%, 100% {
    transform: scale(1);
    filter: drop-shadow(0 8px 16px rgba(5, 150, 105, 0.15));
  }
  50% {
    transform: scale(1.04);
    filter: drop-shadow(0 12px 24px rgba(5, 150, 105, 0.25));
  }
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(12px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
