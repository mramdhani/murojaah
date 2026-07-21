<template>
  <div class="redirect-screen">
    <div class="spinner"></div>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'

onMounted(() => {
  try {
    const raw = localStorage.getItem('murojaah_last_read')
    if (raw) {
      const lastRead = JSON.parse(raw)
      if (lastRead && lastRead.page) {
        const query: Record<string, any> = {}
        if (lastRead.surah) query.surah = lastRead.surah
        if (lastRead.ayah) query.ayah = lastRead.ayah
        navigateTo({
          path: `/mushaf/${lastRead.page}`,
          query
        })
        return
      }
    }
  } catch (e) {
    console.error('Failed to parse last read:', e)
  }
  // Default fallback to Al-Baqarah (surah=2) page 2
  navigateTo('/mushaf/2?surah=2')
})
</script>

<style scoped>
.redirect-screen {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100dvh;
  background-color: var(--color-bg, #FFFDF8);
}
.spinner {
  width: 36px;
  height: 36px;
  border: 3px solid rgba(5, 150, 105, 0.1);
  border-left-color: var(--color-primary, #064E3B);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>