<template>
  <div class="page">
    <header class="page-header">
      <div class="container">
        <h1>Riwayat Murajaah</h1>
        <p>Aktivitas hafalan yang pernah dilakukan</p>
      </div>
    </header>

    <div class="page-content container">
      <!-- Grouped by Date -->
      <template v-if="groupedLogs.length > 0">
        <div v-for="group in groupedLogs" :key="group.date" class="history-group animate-fade-in">
          <h2 class="history-date">{{ formatDate(group.date) }}</h2>

          <div class="history-list">
            <!-- Iterate over Surah Groups -->
            <div v-for="surahGroup in group.surahGroups" :key="surahGroup.surah_id" class="history-surah-card card">
              <div class="history-surah-card__header">
                <span class="history-surah-card__title">{{ surahGroup.surah_name }}</span>
                <span class="history-surah-card__count">{{ surahGroup.items.length }} Ayat</span>
              </div>
              
              <div class="history-ayah-chips">
                <button 
                  v-for="log in surahGroup.items" 
                  :key="log.id" 
                  class="ayah-chip" 
                  :class="`ayah-chip--${log.status}`"
                  @click="goToAyah(log.surah_id, log.ayah_number)"
                >
                  <span class="ayah-chip__icon">{{ getStatusIcon(log.status) }}</span>
                  <span class="ayah-chip__num">{{ log.ayah_number }}</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- Empty State -->
      <div v-else-if="!loading" class="empty-state">
        <div class="empty-state__icon">🕐</div>
        <h3>Belum ada riwayat</h3>
        <p>Riwayat akan muncul setelah kamu mulai menghafal</p>
        <NuxtLink to="/surahs" class="btn btn-primary" style="margin-top: 16px">Mulai Hafalan</NuxtLink>
      </div>

      <!-- Loading -->
      <div v-if="loading">
        <div v-for="i in 6" :key="i" class="card" style="padding: 16px; margin-bottom: 8px">
          <div class="skeleton" style="width: 120px; height: 16px; margin-bottom: 8px"></div>
          <div class="skeleton" style="width: 80px; height: 14px"></div>
        </div>
      </div>
    </div>
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
    // Convert the surahs record into an array and sort by latest activity or keep order
    const surahGroups = Object.values(surahsRecord)
    return { date, surahGroups }
  }).sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime())
})

const statusLabel = (status: string) => {
  const labels: Record<string, string> = {
    fluent: 'Lancar',
    doubtful: 'Ragu',
    forgot: 'Lupa',
  }
  return labels[status as keyof typeof labels] || status
}

const getStatusIcon = (status: string) => {
  switch (status) {
    case 'fluent': return '✓'
    case 'doubtful': return '~'
    case 'forgot': return '✗'
    default: return '•'
  }
}

const formatDate = (dateStr: string) => {
  const date = new Date(dateStr)
  const today = new Date()
  const yesterday = new Date(today)
  yesterday.setDate(yesterday.getDate() - 1)

  if (dateStr === today.toISOString().split('T')[0]) return 'Hari Ini'
  if (dateStr === yesterday.toISOString().split('T')[0]) return 'Kemarin'

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

const formatTime = (dateTimeStr: string) => {
  const parts = dateTimeStr.split(' ')
  if (parts.length < 2) return ''
  return parts[1].substring(0, 5)
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

useHead({ title: 'Riwayat Murajaah — Murojaah' })
</script>

<style scoped>
.history-group {
  margin-bottom: 24px;
}

.history-date {
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--color-text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 10px;
}

.history-list {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.history-surah-card {
  padding: 16px;
}

.history-surah-card__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.history-surah-card__title {
  font-weight: 700;
  font-size: 1rem;
}

.history-surah-card__count {
  font-size: 0.75rem;
  color: var(--color-text-muted);
  background: var(--color-bg);
  padding: 2px 8px;
  border-radius: var(--radius-full);
}

.history-ayah-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.ayah-chip {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  background: var(--color-bg);
  border: 1px solid var(--border-color);
  padding: 6px 10px;
  border-radius: var(--radius-full);
  font-size: 0.8125rem;
  font-weight: 600;
  cursor: pointer;
  transition: all var(--transition-fast);
  color: var(--color-text);
}

.ayah-chip:active {
  transform: scale(0.95);
}

.ayah-chip__icon {
  font-weight: 900;
  font-size: 0.875rem;
}

/* Status colors for chips */
.ayah-chip--fluent {
  background: var(--color-fluent-bg);
  border-color: var(--color-fluent-border);
}
.ayah-chip--fluent .ayah-chip__icon {
  color: var(--color-fluent);
}

.ayah-chip--doubtful {
  background: rgba(245, 158, 11, 0.1);
  border-color: rgba(245, 158, 11, 0.3);
}
.ayah-chip--doubtful .ayah-chip__icon {
  color: #D97706;
}

.ayah-chip--forgot {
  background: rgba(239, 68, 68, 0.1);
  border-color: rgba(239, 68, 68, 0.3);
}
.ayah-chip--forgot .ayah-chip__icon {
  color: #DC2626;
}
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
