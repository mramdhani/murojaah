<template>
  <div class="page">
    <header class="surah-header">
      <!-- Background arabesque overlay -->
      <div class="surah-header__arabesque"></div>

      <!-- Floating sparkles -->
      <span class="hdr-sparkle hdr-sparkle--1">✦</span>
      <span class="hdr-sparkle hdr-sparkle--2">◈</span>
      <span class="hdr-sparkle hdr-sparkle--3">✧</span>
      <span class="hdr-dot hdr-dot--1"></span>
      <span class="hdr-dot hdr-dot--2"></span>

      <div class="container" style="position: relative; z-index: 2;">
        <div class="surah-header__text">
          <h1 class="surah-title">Pilih Surat</h1>
          <p class="surah-subtitle">
            <span class="hdr-diamond">◆</span>
            Pilih surat untuk memulai hafalan
          </p>
        </div>
      </div>
      <!-- Smooth premium wave -->
      <div class="surah-header__divider" aria-hidden="true">
    <svg
      class="surah-header__divider-svg"
      viewBox="0 0 390 96"
      preserveAspectRatio="none"
      xmlns="http://www.w3.org/2000/svg"
    >
      <defs>
        <linearGradient id="waveCream" x1="0" y1="0" x2="0" y2="96">
          <stop offset="0%" stop-color="#FFFDF6" />
          <stop offset="100%" stop-color="#FAFAF2" />
        </linearGradient>

        <linearGradient id="waveGold" x1="0" y1="0" x2="390" y2="0">
          <stop offset="0%" stop-color="#A87518" />
          <stop offset="32%" stop-color="#F7D979" />
          <stop offset="62%" stop-color="#FFF3B8" />
          <stop offset="100%" stop-color="#C6952B" />
        </linearGradient>

        <filter id="softWaveShadow" x="-20%" y="-50%" width="140%" height="180%">
          <feDropShadow
            dx="0"
            dy="5"
            stdDeviation="5"
            flood-color="#04251A"
            flood-opacity="0.14"
          />
        </filter>
      </defs>

      <!-- Cream area -->
      <path
        d="M0,55
           C58,39 118,37 174,46
           C242,57 304,60 390,37
           L390,96
           L0,96
           Z"
        fill="url(#waveCream)"
        filter="url(#softWaveShadow)"
      />

      <!-- Soft gold glow -->
      <path
        d="M0,55
           C58,39 118,37 174,46
           C242,57 304,60 390,37"
        fill="none"
        stroke="#E7C765"
        stroke-width="6"
        stroke-linecap="round"
        opacity="0.2"
      />

      <!-- Main gold line -->
      <path
        d="M0,55
           C58,39 118,37 174,46
           C242,57 304,60 390,37"
        fill="none"
        stroke="url(#waveGold)"
        stroke-width="2.2"
        stroke-linecap="round"
      />

      <!-- White highlight -->
      <path
        d="M0,51
           C58,35 118,33 174,42
           C242,53 304,56 390,33"
        fill="none"
        stroke="rgba(255,255,255,0.75)"
        stroke-width="1"
        stroke-linecap="round"
      />
    </svg>
  </div>

        <!-- <div class="hdr-diamond-ornament">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
            <defs>
              <linearGradient id="ornGold" x1="2" y1="2" x2="22" y2="22">
                <stop offset="0%" stop-color="#FFF8D6" />
                <stop offset="50%" stop-color="#D4AF37" />
                <stop offset="100%" stop-color="#9A6B0E" />
              </linearGradient>
            </defs>
            <path d="M12 2L22 12L12 22L2 12Z" fill="url(#ornGold)" stroke="#FFF8D6" stroke-width="1.2"/>
            <path d="M12 6L14.2 10L18 12L14.2 14L12 18L9.8 14L6 12L9.8 10Z" fill="#0B5D47"/>
            <circle cx="12" cy="12" r="1.8" fill="#FFF8D6"/>
          </svg>
        </div> -->
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
useScrollRestore('surahs-index')
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
.page-content {
  padding-top: 0 !important;
}

.controls-row {
  display: flex;
  gap: 10px;
  margin-top: 16px; /* Give breathing room below the curved divider and diamond ornament */
  margin-bottom: 20px;
  width: 100%;
  position: sticky;
  top: 8px; /* small gap from top of screen when sticky for floating card effect */
  z-index: 10;
  background: linear-gradient(135deg, var(--color-primary-900) 0%, var(--color-primary-dark) 60%, var(--color-primary) 100%);
  padding: 12px;
  border-radius: var(--radius-lg);
  box-shadow: 0 8px 20px rgba(5, 150, 105, 0.15);
  border: 1px solid var(--color-primary-dark);
}

.search-wrapper {
  position: relative;
  flex: 1;
}

.search-wrapper .search-input {
  background-color: rgba(255, 255, 255, 0.15) !important;
  color: white !important;
  border: 1px solid rgba(255, 255, 255, 0.1) !important;
  padding-left: 44px !important;
}

.search-wrapper .search-input::placeholder {
  color: rgba(255, 255, 255, 0.65) !important;
}

.search-wrapper .search-icon {
  color: rgba(255, 255, 255, 0.8) !important;
}

.filter-wrapper {
  flex-shrink: 0;
  width: 130px;
}

.filter-select {
  width: 100%;
  height: 46px;
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  background-color: rgba(255, 255, 255, 0.15);
  padding: 0 12px;
  font-size: 0.875rem;
  font-weight: 600;
  color: white;
  cursor: pointer;
  outline: none;
  transition: border-color var(--transition-fast);
  appearance: none;
  background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23ffffff' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
  background-repeat: no-repeat;
  background-position: right 10px center;
  background-size: 16px;
  padding-right: 32px;
}

.filter-select option {
  color: var(--color-text) !important;
  background-color: var(--color-bg-card) !important;
}

.filter-select:focus {
  border-color: rgba(255, 255, 255, 0.3);
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
  font-size: 1.25rem;
  color: var(--color-primary-dark);
  direction: rtl;
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

/* ================================================
   HEADER (Mirrors homepage & progress)
   ================================================ */
.surah-header {
  padding: calc(var(--safe-top) + 36px) 0 0 !important;
  background: linear-gradient(160deg, #052e1c 0%, #064E3B 45%, #0a6349 100%) !important;
  color: white;
  position: relative;
  overflow: hidden;
  border: none !important;
  border-bottom: none !important;
  box-shadow: none !important;
}

/* Subtle arabesque/geometric overlay */
.surah-header__arabesque {
  position: absolute;
  inset: 0;
  background-image:
    radial-gradient(ellipse at 10% 60%, rgba(212, 175, 55, 0.07) 0%, transparent 55%),
    radial-gradient(ellipse at 90% 20%, rgba(255, 255, 255, 0.04) 0%, transparent 45%),
    url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='60' height='60'%3E%3Cpath d='M30 0 L35 25 L60 30 L35 35 L30 60 L25 35 L0 30 L25 25 Z' fill='none' stroke='rgba(212,175,55,0.04)' stroke-width='1'/%3E%3C/svg%3E");
  background-size: auto, auto, 60px 60px;
  pointer-events: none;
}

/* Floating sparkles */
.hdr-sparkle {
  position: absolute;
  pointer-events: none;
  z-index: 1;
}

.hdr-sparkle--1 { top: 18%; right: 18%; font-size: 1rem; color: #FFF8D6; opacity: 0.4; animation: sparkFloat 4s ease-in-out infinite; }
.hdr-sparkle--2 { top: 55%; left: 6%; font-size: 0.75rem; color: #D4AF37; opacity: 0.35; animation: sparkFloat 5.5s ease-in-out infinite reverse; }
.hdr-sparkle--3 { top: 28%; left: 22%; font-size: 0.65rem; color: rgba(255, 255, 255, 0.5); opacity: 0.3; animation: sparkFloat 3.5s ease-in-out infinite; animation-delay: 1.5s; }

.hdr-dot {
  position: absolute;
  border-radius: 50%;
  pointer-events: none;
}

.hdr-dot--1 { width: 5px; height: 5px; top: 62%; right: 28%; background: rgba(212, 175, 55, 0.3); animation: sparkFloat 6s ease-in-out infinite; animation-delay: 0.8s; }
.hdr-dot--2 { width: 3px; height: 3px; top: 38%; left: 32%; background: rgba(255, 255, 255, 0.25); animation: sparkFloat 4.5s ease-in-out infinite; animation-delay: 2s; }

@keyframes sparkFloat {
  0%, 100% { transform: translateY(0) rotate(0deg); opacity: 0.35; }
  50% { transform: translateY(-7px) rotate(12deg); opacity: 0.7; }
}

.surah-header .container {
  padding: 0 20px;
}

.surah-title {
  font-size: 1.8rem;
  font-weight: 800;
  letter-spacing: -0.03em;
  line-height: 1.1;
  margin-bottom: 8px;
  color: #fff;
}

.surah-subtitle {
  font-size: 0.85rem;
  opacity: 0.75;
  line-height: 1.4;
  display: flex;
  align-items: flex-start;
  gap: 6px;
  margin-bottom: 24px;
}

.hdr-diamond {
  color: #D4AF37;
  opacity: 0.8;
  font-size: 0.65rem;
  margin-top: 3px;
  flex-shrink: 0;
}

/* Elegant curved divider */
.surah-header__divider {
  position: relative;
  z-index: 2;
  line-height: 0;
  margin-top: -8px;
  transform: translateY(1px);
  border: none !important;
  border-bottom: none !important;
}

.surah-header__divider-fill {
  height: 0;
}

.surah-header__divider-svg {
  width: 100%;
  height: 72px;
  display: block;
  border: none !important;
  outline: none !important;
}

.hdr-diamond-ornament {
  position: absolute;
  top: 22px;
  left: 50%;
  transform: translateX(-50%);
  width: 34px;
  height: 34px;
  border-radius: 999px;
  background: #FAFAF5;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 5;
  box-shadow:
    0 6px 16px rgba(5, 46, 28, 0.14),
    0 0 0 4px rgba(250, 250, 245, 0.95),
    0 0 0 5.5px rgba(212, 175, 55, 0.18);
}
</style>
