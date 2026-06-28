<template>
  <div class="page">
    <header class="page-header">
      <div class="container">
        <h1>Pilih Surat</h1>
        <p>Pilih surat untuk memulai hafalan</p>
      </div>
    </header>

    <div class="page-content container">
      <!-- Search & Filter Controls -->
      <div class="controls-row">
        <div class="search-wrapper">
          <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/>
            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
          <input
            type="text"
            class="search-input"
            placeholder="Cari surat..."
            v-model="searchQuery"
            id="search-surah"
          />
        </div>

        <div class="filter-wrapper">
          <select v-model="selectedJuz" class="filter-select">
            <option value="">Semua Juz</option>
            <option v-for="juz in 30" :key="juz" :value="juz">Juz {{ juz }}</option>
          </select>
        </div>
      </div>

      <!-- Surah List -->
      <div class="surah-list" v-if="filteredSurahs.length > 0">
        <NuxtLink
          v-for="(surah, i) in filteredSurahs"
          :key="surah.id"
          :to="`/surahs/${surah.id}`"
          class="surah-card card card-interactive animate-fade-in"
          :style="{ animationDelay: `${Math.min(i * 30, 300)}ms` }"
        >
          <div class="surah-card__number">
            <span>{{ surah.number }}</span>
          </div>
          <div class="surah-card__info">
            <div class="surah-card__header">
              <span class="surah-card__name">{{ surah.name_latin }}</span>
              <span class="surah-card__arabic">{{ surah.name_arabic }}</span>
            </div>
            <div class="surah-card__meta">
              <span class="juz-label" v-if="surah.juz_start">
                Juz {{ surah.juz_start === surah.juz_end ? surah.juz_start : `${surah.juz_start}-${surah.juz_end}` }}
              </span>
              <span v-if="surah.juz_start"> · </span>
              {{ surah.name_translation }} · {{ surah.total_ayah }} ayat · {{ surah.revelation_place === 'meccan' ? 'Makkiyah' : 'Madaniyah' }}
            </div>
            <!-- Mini progress bar -->
            <div class="progress-bar" v-if="surah.progress.fluent + surah.progress.doubtful + surah.progress.forgot > 0">
              <div class="progress-bar__segment progress-bar__segment--fluent" :style="{ width: progressPercent(surah, 'fluent') }"></div>
              <div class="progress-bar__segment progress-bar__segment--doubtful" :style="{ width: progressPercent(surah, 'doubtful') }"></div>
              <div class="progress-bar__segment progress-bar__segment--forgot" :style="{ width: progressPercent(surah, 'forgot') }"></div>
            </div>
          </div>
        </NuxtLink>
      </div>

      <!-- Loading -->
      <div v-else-if="loading" class="surah-list">
        <div v-for="i in 8" :key="i" class="surah-card card">
          <div class="skeleton" style="width: 40px; height: 40px; border-radius: 50%"></div>
          <div class="surah-card__info">
            <div class="skeleton" style="width: 120px; height: 18px; margin-bottom: 8px"></div>
            <div class="skeleton" style="width: 80px; height: 14px"></div>
          </div>
        </div>
      </div>

      <!-- Empty Search -->
      <div v-else class="empty-state">
        <p>Tidak ada surat yang cocok</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const { apiFetch } = useApi()

interface SurahItem {
  id: number
  number: number
  name_latin: string
  name_arabic: string
  name_translation: string
  total_ayah: number
  revelation_place: string
  juz_start: number | null
  juz_end: number | null
  progress: {
    fluent: number
    doubtful: number
    forgot: number
    unreviewed: number
  }
}

const surahs = ref<SurahItem[]>([])
const searchQuery = ref('')
const selectedJuz = ref<number | string>('')
const loading = ref(true)

const filteredSurahs = computed(() => {
  let result = surahs.value

  // 1. Filter by Juz
  if (selectedJuz.value !== '') {
    const juzNum = Number(selectedJuz.value)
    result = result.filter(s => 
      s.juz_start !== null && 
      s.juz_end !== null && 
      juzNum >= s.juz_start && 
      juzNum <= s.juz_end
    )
  }

  // 2. Filter by search query
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    result = result.filter(s =>
      s.name_latin.toLowerCase().includes(q) ||
      s.name_translation.toLowerCase().includes(q) ||
      s.name_arabic.includes(q) ||
      s.number.toString() === q
    )
  }

  return result
})

const progressPercent = (surah: SurahItem, status: string) => {
  const total = surah.total_ayah
  const count = surah.progress[status as keyof typeof surah.progress] || 0
  return `${(count / total) * 100}%`
}

onMounted(async () => {
  try {
    const res = await apiFetch<{ data: SurahItem[] }>('/surahs')
    surahs.value = res.data
  } catch (e) {
    console.error('Failed to load surahs:', e)
  } finally {
    loading.value = false
  }
})

useHead({ title: 'Pilih Surat — Murojaah' })
</script>

<style scoped>
.controls-row {
  display: flex;
  gap: 10px;
  margin-bottom: 16px;
  width: 100%;
}

.search-wrapper {
  position: relative;
  flex: 1;
}

.filter-wrapper {
  flex-shrink: 0;
  width: 130px;
}

.filter-select {
  width: 100%;
  height: 46px;
  border-radius: 12px;
  border: 1px solid var(--border-color);
  background-color: var(--color-bg);
  padding: 0 12px;
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--color-text);
  cursor: pointer;
  outline: none;
  transition: border-color var(--transition-fast);
  appearance: none;
  background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2371717a' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
  background-repeat: no-repeat;
  background-position: right 10px center;
  background-size: 16px;
  padding-right: 32px;
}

.filter-select:focus {
  border-color: var(--color-primary);
}

.juz-label {
  font-weight: 700;
  color: var(--color-primary);
}

.search-icon {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--color-text-muted);
  pointer-events: none;
}

.surah-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.surah-card {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px 16px;
  text-decoration: none;
}

.surah-card__number {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: var(--color-primary-50);
  color: var(--color-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.875rem;
  flex-shrink: 0;
}

.surah-card__info {
  flex: 1;
  min-width: 0;
}

.surah-card__header {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  margin-bottom: 4px;
}

.surah-card__name {
  font-weight: 600;
  font-size: 0.9375rem;
}

.surah-card__arabic {
  font-family: var(--font-arabic);
  font-size: 1.125rem;
  color: var(--color-primary-dark);
}

.surah-card__meta {
  font-size: 0.75rem;
  color: var(--color-text-muted);
  margin-bottom: 6px;
}

.surah-card .progress-bar {
  height: 4px;
}

.empty-state {
  text-align: center;
  padding: 48px 20px;
  color: var(--color-text-muted);
}
</style>
