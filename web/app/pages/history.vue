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
            <div v-for="log in group.items" :key="log.id" class="history-item card">
              <div class="history-item__left">
                <span class="history-item__surah">{{ log.surah_name }}</span>
                <span class="history-item__ayah">Ayat {{ log.ayah_number }}</span>
              </div>
              <div class="history-item__right">
                <span class="status-badge" :class="`status-badge--${log.status}`">
                  {{ statusLabel(log.status) }}
                </span>
                <span class="history-item__time">{{ formatTime(log.reviewed_at) }}</span>
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

interface LogGroup {
  date: string
  items: ReviewLogItem[]
}

const logs = ref<ReviewLogItem[]>([])
const loading = ref(true)

const groupedLogs = computed<LogGroup[]>(() => {
  const groups: Record<string, ReviewLogItem[]> = {}
  for (const log of logs.value) {
    const date = log.reviewed_at.split(' ')[0]
    if (!groups[date]) groups[date] = []
    groups[date].push(log)
  }
  return Object.entries(groups).map(([date, items]) => ({ date, items }))
})

const statusLabel = (status: string) => {
  const labels: Record<string, string> = {
    fluent: 'Lancar',
    doubtful: 'Ragu',
    forgot: 'Lupa',
  }
  return labels[status] || status
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

const formatTime = (dateTimeStr: string) => {
  const parts = dateTimeStr.split(' ')
  if (parts.length < 2) return ''
  return parts[1].substring(0, 5)
}

onMounted(async () => {
  try {
    const res = await apiFetch<{ data: ReviewLogItem[] }>('/review-logs')
    logs.value = res.data
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

.history-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
}

.history-item__surah {
  font-weight: 600;
  font-size: 0.9375rem;
  display: block;
}

.history-item__ayah {
  font-size: 0.75rem;
  color: var(--color-text-muted);
  margin-top: 2px;
  display: block;
}

.history-item__right {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 4px;
}

.history-item__time {
  font-size: 0.6875rem;
  color: var(--color-text-muted);
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
