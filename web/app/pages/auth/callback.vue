<template>
  <div class="callback-page">
    <div class="spinner-container">
      <div class="spinner"></div>
      <p>Menyambungkan akun...</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter, useRoute } from '#imports'

const route = useRoute()
const router = useRouter()
const { token, initSession } = useAuth()

onMounted(async () => {
  const tokenParam = route.query.token as string | undefined
  const errorParam = route.query.error as string | undefined

  if (errorParam) {
    // Logout from Google or auth canceled — silently return to profile
    await router.replace('/profile')
    return
  }

  if (tokenParam) {
    token.value = tokenParam
    await initSession()
    await router.replace('/profile')
    return
  }

  // If no token and no error, just go back to profile
  await router.replace('/profile')
})
</script>

<style scoped>
.callback-page {
  height: 100dvh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-bg);
}

.spinner-container {
  text-align: center;
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