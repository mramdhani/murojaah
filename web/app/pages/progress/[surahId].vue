<template>
  <div class="page">
    <header class="page-header">
      <div class="container">
        <NuxtLink to="/progress" class="back-link">← Kembali</NuxtLink>
        <h1 v-if="data">{{ data.surah_name }}</h1>
        <p v-if="data">{{ data.surah_name_arabic }} · {{ data.total_ayah }} ayat</p>
      </div>
    </header>

    <div class="page-content container" v-if="data">
      <!-- Summary Cards -->
      <div class="summary-grid animate-fade-in">
        <div class="summary-card summary-card--fluent">
          <span class="summary-card__value">{{ data.summary.fluent }}</span>
          <span class="summary-card__label">Lancar</span>
        </div>
        <div class="summary-card summary-card--doubtful">
          <span class="summary-card__value">{{ data.summary.doubtful }}</span>
          <span class="summary-card__label">Ragu</span>
        </div>
        <div class="summary-card summary-card--forgot">
          <span class="summary-card__value">{{ data.summary.forgot }}</span>
          <span class="summary-card__label">Lupa</span>
        </div>
        <div class="summary-card summary-card--unreviewed">
          <span class="summary-card__value">{{ data.summary.unreviewed }}</span>
          <span class="summary-card__label">Belum</span>
        </div>
      </div>

      <!-- Progress Bar -->
      <div class="progress-bar" style="height: 10px; border-radius: 8px; margin: 20px 0">
        <div class="progress-bar__segment progress-bar__segment--fluent" :style="{ width: pct('fluent') }"></div>
        <div class="progress-bar__segment progress-bar__segment--doubtful" :style="{ width: pct('doubtful') }"></div>
        <div class="progress-bar__segment progress-bar__segment--forgot" :style="{ width: pct('forgot') }"></div>
      </div>

      <!-- Action: Murajaah Weak Ayahs -->
      <NuxtLink
        v-if="weakAyah"
        :to="`/remote/${route.params.surahId}/${weakAyah.ayah_number}`"
        class="btn btn-primary btn-block"
        style="margin-bottom: 20px"
      >
        Murajaah Ayat Lemah ({{ data.summary.forgot + data.summary.doubtful }} ayat)
      </NuxtLink>

      <!-- Ayah List -->
      <div class="ayah-list">
        <div
          v-for="ayah in data.ayahs"
          :key="ayah.ayah_number"
          class="ayah-row"
        >
          <span class="ayah-row__number">Ayat {{ ayah.ayah_number }}</span>
          <div class="ayah-row__right">
            <span class="status-badge" :class="`status-badge--${ayah.status}`">
              {{ statusLabel(ayah.status) }}
            </span>
            <span class="ayah-row__count" v-if="ayah.review_count > 0">
              ×{{ ayah.review_count }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div class="page-content container" v-else>
      <div class="summary-grid">
        <div class="skeleton" style="height: 80px; border-radius: 12px" v-for="i in 4" :key="i"></div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const route = useRoute()
const { apiFetch } = useApi()

interface ProgressDetail {
  surah_id: number
  surah_name: string
  surah_name_arabic: string
  total_ayah: number
  summary: { fluent: number; doubtful: number; forgot: number; unreviewed: number }
  ayahs: Array<{
    ayah_number: number
    status: string
    last_reviewed_at: string | null
    review_count: number
  }>
}

const data = ref<ProgressDetail | null>(null)

const weakAyah = computed(() => {
  if (!data.value) return null
  return data.value.ayahs.find(a => a.status === 'forgot' || a.status === 'doubtful')
})

const pct = (status: string) => {
  if (!data.value) return '0%'
  const count = data.value.summary[status as keyof typeof data.value.summary] || 0
  return `${(count / data.value.total_ayah) * 100}%`
}

const statusLabel = (status: string) => {
  const labels: Record<string, string> = {
    fluent: 'Lancar',
    doubtful: 'Ragu',
    forgot: 'Lupa',
    unreviewed: 'Belum',
  }
  return labels[status] || status
}

onMounted(async () => {
  try {
    const res = await apiFetch<{ data: ProgressDetail }>(`/progress/surahs/${route.params.surahId}`)
    data.value = res.data
  } catch (e) {
    console.error('Failed to load progress:', e)
  }
})

useHead({
  title: computed(() => data.value ? `${data.value.surah_name} Progress — Murojaah` : 'Progress'),
})
</script>

<style scoped>
.back-link {
  display: inline-block;
  font-size: 0.875rem;
  opacity: 0.8;
  margin-bottom: 8px;
  text-decoration: none;
}

.summary-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 8px;
}

.summary-card {
  border-radius: var(--radius-md);
  padding: 14px 8px;
  text-align: center;
}

.summary-card__value {
  display: block;
  font-size: 1.5rem;
  font-weight: 700;
  line-height: 1;
  margin-bottom: 4px;
}

.summary-card__label {
  font-size: 0.6875rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.summary-card--fluent { background: var(--color-fluent-bg); color: var(--color-fluent); }
.summary-card--doubtful { background: var(--color-doubtful-bg); color: #B45309; }
.summary-card--forgot { background: var(--color-forgot-bg); color: var(--color-forgot); }
.summary-card--unreviewed { background: var(--color-unreviewed-bg); color: var(--color-unreviewed); }

.ayah-list {
  display: flex;
  flex-direction: column;
}

.ayah-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid rgba(0, 0, 0, 0.04);
}

.ayah-row__number {
  font-size: 0.875rem;
  font-weight: 500;
}

.ayah-row__right {
  display: flex;
  align-items: center;
  gap: 8px;
}

.ayah-row__count {
  font-size: 0.75rem;
  color: var(--color-text-muted);
  font-weight: 500;
}
</style>
