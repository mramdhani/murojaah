<template>
  <div class="page">
    <header class="page-header">
      <div class="container page-header__content">
        <NuxtLink to="/surahs" class="page-header__back" aria-label="Kembali" @click="triggerHaptic">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="15 18 9 12 15 6"/>
          </svg>
        </NuxtLink>
        <div class="page-header__info">
          <h1 v-if="surah">{{ surah.name_latin }}</h1>
          <p v-if="surah">Pilih ayat awal — {{ surah.total_ayah }} ayat · {{ surah.name_arabic }}</p>
        </div>
      </div>
    </header>

    <div class="page-content container">
      <!-- Legend -->
      <div class="legend">
        <div class="legend__item"><span class="legend__dot legend__dot--fluent"></span> Lancar</div>
        <div class="legend__item"><span class="legend__dot legend__dot--doubtful"></span> Ragu</div>
        <div class="legend__item"><span class="legend__dot legend__dot--forgot"></span> Lupa</div>
        <div class="legend__item"><span class="legend__dot legend__dot--unreviewed"></span> Belum</div>
      </div>

      <!-- Ayah Grid -->
      <div class="ayah-grid" v-if="ayahs.length > 0">
        <NuxtLink
          v-for="ayah in ayahs"
          :key="ayah.id"
          :to="`/remote/${route.params.id}/${ayah.ayah_number}`"
          class="ayah-cell"
          :class="`ayah-cell--${ayah.progress_status}`"
          @click="triggerHaptic"
        >
          {{ ayah.ayah_number }}
        </NuxtLink>
      </div>

      <!-- Loading -->
      <div class="ayah-grid" v-else-if="loading">
        <div v-for="i in 20" :key="i" class="skeleton ayah-cell" style="border: none"></div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const route = useRoute()
const { apiFetch } = useApi()

const triggerHaptic = () => {
  if (typeof navigator !== 'undefined' && navigator.vibrate) {
    navigator.vibrate(40)
  }
}

interface SurahDetail {
  id: number
  number: number
  name_latin: string
  name_arabic: string
  total_ayah: number
}

interface AyahItem {
  id: number
  surah_id: number
  ayah_number: number
  progress_status: string
}

const surah = ref<SurahDetail | null>(null)
const ayahs = ref<AyahItem[]>([])
const loading = ref(true)

onMounted(async () => {
  try {
    const [surahRes, ayahsRes] = await Promise.all([
      apiFetch<{ data: SurahDetail }>(`/surahs/${route.params.id}`),
      apiFetch<{ data: AyahItem[] }>(`/surahs/${route.params.id}/ayahs`),
    ])
    surah.value = surahRes.data
    ayahs.value = ayahsRes.data
  } catch (e) {
    console.error('Failed to load ayahs:', e)
  } finally {
    loading.value = false
  }
})

useHead({
  title: computed(() => surah.value ? `${surah.value.name_latin} — Pilih Ayat` : 'Pilih Ayat'),
})
</script>

<style scoped>
.page-header__content {
  display: flex;
  align-items: center;
  gap: 16px;
}

.page-header__back {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.12);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  flex-shrink: 0;
  transition: background var(--transition-fast);
  text-decoration: none;
}

.page-header__back:active {
  background: rgba(255, 255, 255, 0.25);
}

.page-header__info {
  flex: 1;
  min-width: 0;
}

.legend {
  display: flex;
  gap: 16px;
  margin-bottom: 20px;
  flex-wrap: wrap;
}

.legend__item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.75rem;
  color: var(--color-text-secondary);
}

.legend__dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
}

.legend__dot--fluent { background: var(--color-fluent); }
.legend__dot--doubtful { background: var(--color-doubtful); }
.legend__dot--forgot { background: var(--color-forgot); }
.legend__dot--unreviewed { background: var(--color-unreviewed); }

.ayah-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 8px;
}

.ayah-cell {
  aspect-ratio: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: var(--radius-md);
  font-weight: 600;
  font-size: 0.9375rem;
  text-decoration: none;
  transition: all var(--transition-fast);
  border: 2px solid transparent;
}

.ayah-cell:active {
  transform: scale(0.92);
}

.ayah-cell--fluent {
  background: var(--color-fluent-bg);
  color: var(--color-fluent);
  border-color: var(--color-fluent-border);
}

.ayah-cell--doubtful {
  background: var(--color-doubtful-bg);
  color: #B45309;
  border-color: var(--color-doubtful-border);
}

.ayah-cell--forgot {
  background: var(--color-forgot-bg);
  color: var(--color-forgot);
  border-color: var(--color-forgot-border);
}

.ayah-cell--unreviewed {
  background: var(--color-bg-subtle);
  color: var(--color-text-secondary);
  border-color: transparent;
}

@media (min-width: 480px) {
  .ayah-grid {
    grid-template-columns: repeat(7, 1fr);
  }
}

@media (min-width: 768px) {
  .ayah-grid {
    grid-template-columns: repeat(10, 1fr);
  }
}
</style>
