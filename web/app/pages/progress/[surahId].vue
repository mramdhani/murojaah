<template>
  <div class="page">
    <header class="page-header">
      <div class="container page-header__content">
        <!-- Left: Back Button & Title Info (Aligned Left) -->
        <div class="page-header__left-group">
          <NuxtLink to="/progress" class="page-header__back" aria-label="Kembali">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="15 18 9 12 15 6"/>
            </svg>
          </NuxtLink>
          <div class="page-header__info" v-if="data">
            <h1>{{ data.surah_name }}</h1>
            <p>{{ data.surah_name_arabic }} · {{ data.total_ayah }} ayat</p>
          </div>
        </div>

        <!-- Right: Navigation Chevrons Group -->
        <div class="page-header__right-nav" v-if="data">
          <!-- Previous Surah Arrow -->
          <NuxtLink
            v-if="currentSurahId > 1"
            :to="`/progress/${currentSurahId - 1}`"
            class="page-header__nav-btn"
            aria-label="Surat Sebelumnya"
          >
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="15 18 9 12 15 6"/>
            </svg>
          </NuxtLink>

          <!-- Next Surah Arrow (hidden if completed) -->
          <NuxtLink
            v-if="currentSurahId < 114 && !isCompleted"
            :to="`/progress/${currentSurahId + 1}`"
            class="page-header__nav-btn"
            aria-label="Surat Selanjutnya"
          >
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="9 18 15 12 9 6"/>
            </svg>
          </NuxtLink>
        </div>
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

      <!-- Completion Congratulatory Card -->
      <div class="completion-banner animate-fade-in" v-if="isCompleted" style="margin-bottom: 24px;">
        <div class="completion-banner__decor">🎉</div>
        <div class="completion-banner__text-wrap">
          <h4 class="completion-banner__title">Maa Syaa Allah, Selesai!</h4>
          <p class="completion-banner__desc">Kakak telah menyelesaikan murojaah surat {{ data.surah_name }} dengan lancar.</p>
        </div>
        <NuxtLink
          v-if="currentSurahId < 114"
          :to="{ path: `/remote/${currentSurahId + 1}/1`, query: { mode: 'learning' } }"
          class="btn btn-primary completion-banner__btn"
        >
          Mulai Murojaah Selanjutnya
        </NuxtLink>
      </div>

      <!-- Action: Murajaah Weak Ayahs -->
      <NuxtLink
        v-if="weakAyah"
        :to="{ path: `/remote/${route.params.surahId}/${weakAyah.ayah_number}`, query: { mode: 'learning' } }"
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
          @click="goToAyah(ayah.ayah_number)"
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

const router = useRouter()

const goToAyah = (ayahNumber: number) => {
  router.push({ path: `/remote/${route.params.surahId}/${ayahNumber}`, query: { mode: 'learning' } })
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

  const currentSurahId = computed(() => Number(route.params.surahId))

  const isCompleted = computed(() => {
    if (!data.value) return false
    return data.value.summary.fluent === data.value.total_ayah
  })

  const fetchProgressDetail = async (id: number) => {
    try {
      const res = await apiFetch<{ data: ProgressDetail }>(`/progress/surahs/${id}`)
      data.value = res.data
    } catch (e) {
      console.error('Failed to load progress:', e)
    }
  }

  watch(
    () => route.params.surahId,
    (newId) => {
      if (newId) {
        fetchProgressDetail(Number(newId))
      }
    }
  )

  onMounted(() => {
    fetchProgressDetail(currentSurahId.value)
  })

  useHead({
    title: computed(() => data.value ? `${data.value.surah_name} Progress — Murojaah` : 'Progress'),
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
  cursor: pointer;
  transition: opacity 0.15s;
}

.ayah-row:active {
  opacity: 0.7;
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

/* ================================================
   PAGE HEADER DYNAMIC SURAH NAVIGATION
   ================================================ */
.page-header__content {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.page-header__left-group {
  display: flex;
  align-items: center;
  gap: 16px;
  min-width: 0;
  flex: 1;
}

.page-header__right-nav {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

.page-header__nav-btn {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.08);
  display: flex;
  align-items: center;
  justify-content: center;
  color: rgba(255, 255, 255, 0.85);
  transition: all 0.2s ease;
  text-decoration: none;
  cursor: pointer;
}

.page-header__nav-btn:hover {
  background: rgba(255, 255, 255, 0.16);
  color: white;
}

.page-header__nav-btn:active {
  transform: scale(0.9);
}

/* ================================================
   SURAH COMPLETION BANNER
   ================================================ */
.completion-banner {
  background: linear-gradient(135deg, #ECFDF5 0%, #D1FAE5 100%);
  border: 1.5px solid #A7F3D0;
  border-radius: 20px;
  padding: 20px;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-top: 24px;
  box-shadow: 0 4px 12px rgba(5, 150, 105, 0.05);
}

.completion-banner__decor {
  font-size: 2.2rem;
  margin-bottom: 8px;
  animation: bounce 2s infinite;
}

.completion-banner__text-wrap {
  margin-bottom: 16px;
}

.completion-banner__title {
  font-size: 1.1rem;
  font-weight: 800;
  color: #065F46;
  margin: 0 0 4px;
}

.completion-banner__desc {
  font-size: 0.8rem;
  color: #047857;
  font-weight: 600;
  line-height: 1.4;
  max-width: 280px;
  margin: 0 auto;
}

.completion-banner__btn {
  width: 100%;
  max-width: 260px;
  background: #10B981 !important;
  border: none !important;
  box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3) !important;
}

.completion-banner__btn:hover {
  background: #059669 !important;
}

@keyframes bounce {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-6px); }
}
</style>
