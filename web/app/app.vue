<template>
  <div id="app">
    <NuxtPage />
    <BottomNav />
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
import { provide, ref } from 'vue'

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
</script>

<style scoped>
#app {
  min-height: 100dvh;
}
</style>
