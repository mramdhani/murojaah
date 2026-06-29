<template>
  <div class="page">
    <header class="history-header">
      <!-- Background arabesque overlay -->
      <div class="history-header__arabesque"></div>

      <!-- Floating sparkles -->
      <span class="hdr-sparkle hdr-sparkle--1">✦</span>
      <span class="hdr-sparkle hdr-sparkle--2">◈</span>
      <span class="hdr-sparkle hdr-sparkle--3">✧</span>
      <span class="hdr-dot hdr-dot--1"></span>
      <span class="hdr-dot hdr-dot--2"></span>

      <div class="container" style="position: relative; z-index: 2; padding-bottom: 24px;">
        <div class="history-header__text">
          <h1 class="history-title">Riwayat Murojaah</h1>
          <p class="history-subtitle">
            <span class="hdr-diamond">◆</span>
            Aktivitas hafalan yang pernah dilakukan
          </p>
        </div>
      </div>

  <!-- Smooth premium wave -->
  <div class="history-header__divider" aria-hidden="true">
    <svg
      class="history-header__divider-svg"
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
    </header>

    <div class="page-content container">
      <!-- Grouped by Date -->
      <template v-if="groupedLogs.length > 0">
        <div v-for="group in groupedLogs" :key="group.date" class="history-group animate-fade-in">
          <div class="history-group__header">
            <span class="history-group__icon">📅</span>
            <h2 class="history-date">{{ formatDate(group.date) }}</h2>
          </div>

          <div class="history-list">
            <!-- Iterate over Surah Groups -->
            <div v-for="surahGroup in group.surahGroups" :key="surahGroup.surah_id" class="history-surah-card">
              <div class="history-surah-card__header">
                <div class="hsc-title-col">
                  <span class="history-surah-card__title">{{ surahGroup.surah_name }}</span>
                  <span class="history-surah-card__count">{{ surahGroup.items.length }} Ayat</span>
                </div>
                <button
                  class="btn-toggle-view"
                  @click="toggleDetail(`${group.date}-${surahGroup.surah_id}`)"
                  :class="{ 'btn-toggle-view--active': isDetailed(`${group.date}-${surahGroup.surah_id}`) }"
                >
                  <span class="btn-toggle-view__text">
                    {{ isDetailed(`${group.date}-${surahGroup.surah_id}`) ? 'Ringkas' : 'Detail' }}
                  </span>
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" class="btn-toggle-view__icon">
                    <polyline points="6 9 12 15 18 9" />
                  </svg>
                </button>
              </div>
              
              <!-- Chips section -->
              <div class="history-ayah-chips">
                <!-- Grouped View (Default) -->
                <template v-if="!isDetailed(`${group.date}-${surahGroup.surah_id}`)">
                  <button 
                    v-for="range in getGroupedRanges(surahGroup.items)" 
                    :key="range.start + '-' + range.end" 
                    class="ayah-chip ayah-chip--range" 
                    :class="`ayah-chip--${range.status}`"
                    @click="goToAyah(range.surah_id, range.start)"
                  >
                    <span class="ayah-chip__icon">{{ getStatusIcon(range.status) }}</span>
                    <span class="ayah-chip__num">
                      {{ range.start === range.end ? `Ayat ${range.start}` : `Ayat ${range.start} - ${range.end}` }}
                    </span>
                  </button>
                </template>

                <!-- Detailed View -->
                <template v-else>
                  <button 
                    v-for="log in surahGroup.items" 
                    :key="log.id" 
                    class="ayah-chip animate-scale-in" 
                    :class="`ayah-chip--${log.status}`"
                    @click="goToAyah(log.surah_id, log.ayah_number)"
                  >
                    <span class="ayah-chip__icon">{{ getStatusIcon(log.status) }}</span>
                    <span class="ayah-chip__num">{{ log.ayah_number }}</span>
                  </button>
                </template>
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- Empty State -->
      <div v-else-if="!loading" class="empty-state">
        <div class="empty-state__icon">📿</div>
        <h3 class="empty-state__title">Belum ada riwayat</h3>
        <p class="empty-state__desc">Riwayat murojaah Kakak akan muncul di sini</p>
        <NuxtLink to="/surahs" class="btn btn-primary empty-state__btn">Mulai Hafalan</NuxtLink>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="history-list">
        <div v-for="i in 4" :key="i" class="history-surah-card" style="padding: 20px">
          <div class="skeleton" style="width: 140px; height: 18px; margin-bottom: 12px"></div>
          <div class="skeleton" style="width: 100%; height: 40px; border-radius: 12px;"></div>
        </div>
      </div>
    </div>
    <div style="height: 32px"></div>
  </div>
</template>

<script setup lang="ts">
const router = useRouter()
const { apiFetch } = useApi()

interface ReviewLogItem {
  id: number
  surah_name: string
  surah_name_arabic: string
  surah_id: number
  ayah_number: number
  status: string
  was_revealed: boolean
  reviewed_at: string
}

interface SurahGroup {
  surah_id: number
  surah_name: string
  items: ReviewLogItem[]
}

interface LogGroup {
  date: string
  surahGroups: SurahGroup[]
}

const logs = ref<ReviewLogItem[]>([])
const loading = ref(true)

// Reactive object to track detailed view states
const detailedViews = ref<Record<string, boolean>>({})

const toggleDetail = (key: string) => {
  detailedViews.value[key] = !detailedViews.value[key]
}

const isDetailed = (key: string) => {
  return !!detailedViews.value[key]
}

// Group consecutive ayah numbers with the same status
const getGroupedRanges = (items: ReviewLogItem[]) => {
  if (items.length === 0) return []
  
  // Sort ascending by ayah number to build sequential ranges
  const sorted = [...items].sort((a, b) => a.ayah_number - b.ayah_number)
  
  const ranges: { status: string; start: number; end: number; surah_id: number }[] = []
  
  let currentRange = {
    status: sorted[0].status,
    start: sorted[0].ayah_number,
    end: sorted[0].ayah_number,
    surah_id: sorted[0].surah_id
  }
  
  for (let i = 1; i < sorted.length; i++) {
    const item = sorted[i]
    if (item.ayah_number === currentRange.end + 1 && item.status === currentRange.status) {
      currentRange.end = item.ayah_number
    } else {
      ranges.push({ ...currentRange })
      currentRange = {
        status: item.status,
        start: item.ayah_number,
        end: item.ayah_number,
        surah_id: item.surah_id
      }
    }
  }
  ranges.push({ ...currentRange })
  
  // Sort descending to show highest ranges first
  return ranges.sort((a, b) => b.start - a.start)
}

const groupedLogs = computed<LogGroup[]>(() => {
  const groupsByDate: Record<string, Record<number, SurahGroup>> = {}
  
  for (const log of logs.value) {
    const date = log.reviewed_at.split(' ')[0]
    
    if (!groupsByDate[date]) {
      groupsByDate[date] = {}
    }
    
    if (!groupsByDate[date][log.surah_id]) {
      groupsByDate[date][log.surah_id] = {
        surah_id: log.surah_id,
        surah_name: log.surah_name,
        items: []
      }
    }
    
    groupsByDate[date][log.surah_id].items.push(log)
  }
  
  return Object.entries(groupsByDate).map(([date, surahsRecord]) => {
    const surahGroups = Object.values(surahsRecord).map(surahGroup => {
      const latestPerAyah = new Map<number, ReviewLogItem>()
      for (const item of surahGroup.items) {
        const existing = latestPerAyah.get(item.ayah_number)
        if (!existing || item.reviewed_at > existing.reviewed_at) {
          latestPerAyah.set(item.ayah_number, item)
        }
      }
      const dedupedItems = Array.from(latestPerAyah.values())
        .sort((a, b) => b.ayah_number - a.ayah_number)
      return { ...surahGroup, items: dedupedItems }
    })
    return { date, surahGroups }
  }).sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime())
})

const getStatusIcon = (status: string) => {
  switch (status) {
    case 'fluent': return '✓'
    case 'doubtful': return '⚡'
    case 'forgot': return '✗'
    default: return '•'
  }
}

const formatDate = (dateStr: string) => {
  const date = new Date(dateStr)
  const today = new Date()
  const yesterday = new Date(today)
  yesterday.setDate(yesterday.getDate() - 1)

  const dateOnly = date.toISOString().split('T')[0]
  const todayOnly = today.toISOString().split('T')[0]
  const yesterdayOnly = yesterday.toISOString().split('T')[0]

  if (dateStr === todayOnly) return 'Hari Ini'
  if (dateStr === yesterdayOnly) return 'Kemarin'

  return date.toLocaleDateString('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  })
}

const goToAyah = (surahId: number, ayahNumber: number) => {
  router.push(`/surahs/${surahId}?ayah=${ayahNumber}`)
}

onMounted(async () => {
  try {
    const res = await apiFetch<{ data: ReviewLogItem[] }>('/review-logs')
    logs.value = res.data ?? []
  } catch (e) {
    console.error('Failed to load history:', e)
  } finally {
    loading.value = false
  }
})

useHead({ title: 'Riwayat Murojaah — Murojaah' })
</script>

<style scoped>
/* ================================================
   HEADER (Mirrors homepage layout)
   ================================================ */
.history-header {
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
.history-header__arabesque {
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

.history-header .container {
  padding: 0 20px;
}

.history-title {
  font-size: 1.8rem;
  font-weight: 800;
  letter-spacing: -0.03em;
  line-height: 1.1;
  margin-bottom: 8px;
  color: #fff;
}

.history-subtitle {
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
.history-header__divider {
  position: relative;
  z-index: 2;
  line-height: 0;
  margin-top: -8px;
  transform: translateY(1px);
  border: none !important;
  border-bottom: none !important;
}

.history-header__divider-fill {
  height: 0;
}

.history-header__divider-svg {
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

/* ================================================
   CONTENT LIST
   ================================================ */
.history-group {
  margin-bottom: 28px;
}

.history-group__header {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 12px;
}

.history-group__icon {
  font-size: 0.9rem;
}

.history-date {
  font-size: 0.78rem;
  font-weight: 800;
  color: var(--color-text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.06em;
  margin: 0;
}

.history-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.history-surah-card {
  background: var(--color-bg-card);
  border-radius: 16px;
  padding: 16px 20px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.03);
  border: 1px solid rgba(0,0,0,0.04);
}

.history-surah-card__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 14px;
}

.hsc-title-col {
  display: flex;
  align-items: baseline;
  gap: 8px;
}

.history-surah-card__title {
  font-weight: 800;
  font-size: 1.05rem;
  color: var(--color-text);
  letter-spacing: -0.01em;
}

.history-surah-card__count {
  font-size: 0.72rem;
  font-weight: 700;
  color: var(--color-primary-dark);
  background: var(--color-primary-50);
  padding: 2px 8px;
  border-radius: 99px;
  border: 1px solid var(--color-primary-100);
}

/* Toggle detail view button */
.btn-toggle-view {
  background: var(--color-bg-subtle);
  border: 1px solid rgba(0,0,0,0.05);
  color: var(--color-text-secondary);
  border-radius: 8px;
  padding: 6px 12px;
  font-size: 0.72rem;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 4px;
  transition: all var(--transition-fast);
}

.btn-toggle-view:hover {
  background: var(--color-primary-50);
  border-color: var(--color-primary-100);
  color: var(--color-primary-dark);
}

.btn-toggle-view__icon {
  transition: transform var(--transition-fast);
}

.btn-toggle-view--active .btn-toggle-view__icon {
  transform: rotate(180deg);
}

/* Chips list */
.history-ayah-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.ayah-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: var(--color-bg-subtle);
  border: 1.5px solid rgba(0,0,0,0.04);
  padding: 6px 12px;
  border-radius: 10px;
  font-size: 0.8rem;
  font-weight: 700;
  cursor: pointer;
  transition: all var(--transition-fast);
  color: var(--color-text);
}

.ayah-chip--range {
  font-weight: 800;
}

.ayah-chip:active {
  transform: scale(0.96);
}

.ayah-chip__icon {
  font-weight: 900;
  font-size: 0.8rem;
}

/* Status colors for chips (Mirrors homepage stats colors) */
.ayah-chip--fluent {
  background: var(--color-fluent-bg);
  border-color: var(--color-fluent-border);
  color: var(--color-fluent);
}
.ayah-chip--fluent .ayah-chip__icon {
  color: var(--color-fluent);
}

.ayah-chip--doubtful {
  background: var(--color-doubtful-bg);
  border-color: var(--color-doubtful-border);
  color: var(--color-doubtful);
}
.ayah-chip--doubtful .ayah-chip__icon {
  color: var(--color-doubtful);
}

.ayah-chip--forgot {
  background: var(--color-forgot-bg);
  border-color: var(--color-forgot-border);
  color: var(--color-forgot);
}
.ayah-chip--forgot .ayah-chip__icon {
  color: var(--color-forgot);
}

/* ================================================
   EMPTY STATE
   ================================================ */
.empty-state {
  text-align: center;
  padding: 80px 20px;
  background: var(--color-bg-card);
  border-radius: 20px;
  border: 1.5px dashed rgba(5,150,105,0.15);
  margin-top: 20px;
}

.empty-state__icon {
  font-size: 3rem;
  margin-bottom: 16px;
  animation: floatAnim 4s ease-in-out infinite;
}

.empty-state__title {
  font-size: 1.25rem;
  font-weight: 800;
  color: var(--color-text);
  margin-bottom: 6px;
}

.empty-state__desc {
  color: var(--color-text-muted);
  font-size: 0.9rem;
  margin-bottom: 24px;
}

.empty-state__btn {
  padding: 12px 28px;
  border-radius: 99px;
}

@keyframes floatAnim {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-4px); }
}
</style>
