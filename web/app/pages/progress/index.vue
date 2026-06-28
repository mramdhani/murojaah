<template>
  <div class="page">
    <header class="page-header">
      <div class="container">
        <h1>Progress Hafalan</h1>
        <p>Lihat perkembangan hafalan per surat</p>
      </div>
    </header>

    <div class="page-content container">
      <div class="progress-list" v-if="surahs.length > 0">
        <NuxtLink
          v-for="surah in reviewedSurahs"
          :key="surah.id"
          :to="`/progress/${surah.id}`"
          class="progress-card card card-interactive animate-fade-in"
        >
          <div class="progress-card__header">
            <div>
              <span class="progress-card__number">{{ surah.number }}.</span>
              <span class="progress-card__name">{{ surah.name_latin }}</span>
            </div>
            <span class="progress-card__arabic">{{ surah.name_arabic }}</span>
          </div>

          <div class="progress-bar" style="height: 8px; margin: 12px 0">
            <div class="progress-bar__segment progress-bar__segment--fluent" :style="{ width: pct(surah, 'fluent') }"></div>
            <div class="progress-bar__segment progress-bar__segment--doubtful" :style="{ width: pct(surah, 'doubtful') }"></div>
            <div class="progress-bar__segment progress-bar__segment--forgot" :style="{ width: pct(surah, 'forgot') }"></div>
          </div>

          <div class="progress-card__stats">
            <span class="progress-stat progress-stat--fluent">{{ surah.progress.fluent }} Lancar</span>
            <span class="progress-stat progress-stat--doubtful">{{ surah.progress.doubtful }} Ragu</span>
            <span class="progress-stat progress-stat--forgot">{{ surah.progress.forgot }} Lupa</span>
          </div>
        </NuxtLink>
      </div>

      <!-- Empty State -->
      <div v-if="!loading && reviewedSurahs.length === 0" class="empty-state">
        <div class="empty-state__icon">📖</div>
        <h3>Belum ada progress</h3>
        <p>Mulai hafalan untuk melihat progress di sini</p>
        <NuxtLink to="/surahs" class="btn btn-primary" style="margin-top: 16px">Mulai Hafalan</NuxtLink>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="progress-list">
        <div v-for="i in 4" :key="i" class="card" style="padding: 20px">
          <div class="skeleton" style="width: 150px; height: 20px; margin-bottom: 12px"></div>
          <div class="skeleton" style="width: 100%; height: 8px; margin-bottom: 12px"></div>
          <div class="skeleton" style="width: 200px; height: 14px"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const { apiFetch } = useApi()

interface SurahProgress {
  id: number
  number: number
  name_latin: string
  name_arabic: string
  total_ayah: number
  progress: { fluent: number; doubtful: number; forgot: number; unreviewed: number }
}

const surahs = ref<SurahProgress[]>([])
const loading = ref(true)

const reviewedSurahs = computed(() =>
  surahs.value.filter(s => s.progress.fluent + s.progress.doubtful + s.progress.forgot > 0)
)

const pct = (surah: SurahProgress, status: string) => {
  const count = surah.progress[status as keyof typeof surah.progress] || 0
  return `${(count / surah.total_ayah) * 100}%`
}

onMounted(async () => {
  try {
    const res = await apiFetch<{ data: SurahProgress[] }>('/surahs')
    surahs.value = res.data
  } catch (e) {
    console.error('Failed to load progress:', e)
  } finally {
    loading.value = false
  }
})

useHead({ title: 'Progress Hafalan — Murojaah' })
</script>

<style scoped>
.progress-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.progress-card {
  padding: 16px;
  text-decoration: none;
}

.progress-card__header {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
}

.progress-card__number {
  font-weight: 600;
  color: var(--color-text-muted);
  margin-right: 4px;
}

.progress-card__name {
  font-weight: 700;
  font-size: 1rem;
}

.progress-card__arabic {
  font-family: var(--font-arabic);
  font-size: 1.125rem;
  color: var(--color-primary-dark);
}

.progress-card__stats {
  display: flex;
  gap: 16px;
}

.progress-stat {
  font-size: 0.75rem;
  font-weight: 600;
}

.progress-stat--fluent { color: var(--color-fluent); }
.progress-stat--doubtful { color: #B45309; }
.progress-stat--forgot { color: var(--color-forgot); }

.empty-state {
  text-align: center;
  padding: 64px 20px;
}

.empty-state__icon {
  font-size: 3rem;
  margin-bottom: 12px;
}

.empty-state h3 {
  font-size: 1.125rem;
  font-weight: 700;
  margin-bottom: 4px;
}

.empty-state p {
  color: var(--color-text-muted);
  font-size: 0.875rem;
}
</style>
