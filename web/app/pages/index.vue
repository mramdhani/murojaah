<template>
  <div class="page home-page">
    <!-- Hero Header -->
    <header class="home-header">
      <div class="container">
        <div class="home-header__brand">
          <div class="home-header__icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
              <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
              <path d="M8 7h8M8 11h6M8 15h4" stroke-linecap="round"/>
            </svg>
          </div>
          <div>
            <h1>Murojaah</h1>
            <p>Remote Hafalan Qur'an</p>
          </div>
        </div>
      </div>
    </header>

    <div class="page-content container">
      <!-- Today Stats -->
      <section class="home-stats animate-fade-in" v-if="dashboard">
        <h2 class="section-title">Progress Hari Ini</h2>
        <div class="stats-grid">
          <div class="stat-card stat-card--fluent">
            <span class="stat-card__value">{{ dashboard.today.fluent }}</span>
            <span class="stat-card__label">Lancar</span>
          </div>
          <div class="stat-card stat-card--doubtful">
            <span class="stat-card__value">{{ dashboard.today.doubtful }}</span>
            <span class="stat-card__label">Ragu</span>
          </div>
          <div class="stat-card stat-card--forgot">
            <span class="stat-card__value">{{ dashboard.today.forgot }}</span>
            <span class="stat-card__label">Lupa</span>
          </div>
        </div>
      </section>

      <!-- Loading State -->
      <section v-else class="home-stats">
        <h2 class="section-title">Progress Hari Ini</h2>
        <div class="stats-grid">
          <div class="stat-card" v-for="i in 3" :key="i">
            <div class="skeleton" style="width: 40px; height: 32px; margin-bottom: 6px"></div>
            <div class="skeleton" style="width: 50px; height: 14px"></div>
          </div>
        </div>
      </section>

      <!-- Overall Summary -->
      <section class="home-overall animate-fade-in" style="animation-delay: 0.1s" v-if="dashboard">
        <div class="overall-card">
          <div class="overall-card__row">
            <span class="overall-card__label">Total Surat Dimurajaah</span>
            <span class="overall-card__value">{{ dashboard.overall.surahs_reviewed }}</span>
          </div>
          <div class="overall-card__row">
            <span class="overall-card__label">Total Ayat Direview</span>
            <span class="overall-card__value">{{ dashboard.overall.total_reviewed }}</span>
          </div>
        </div>
      </section>

      <!-- Action Buttons -->
      <section class="home-actions animate-fade-in" style="animation-delay: 0.2s">
        <NuxtLink to="/surahs" class="btn btn-primary btn-block btn-lg">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polygon points="5 3 19 12 5 21 5 3"/>
          </svg>
          Mulai Hafalan
        </NuxtLink>

        <NuxtLink
          v-if="dashboard?.last_session"
          :to="`/remote/${dashboard.last_session.surah_id}/${dashboard.last_session.current_ayah_number}`"
          class="btn btn-outline btn-block btn-lg"
        >
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="1 4 1 10 7 10"/>
            <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
          </svg>
          Lanjutkan {{ dashboard.last_session.surah_name }}
        </NuxtLink>

        <NuxtLink to="/progress" class="btn btn-ghost btn-block btn-lg">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="20" x2="18" y2="10"/>
            <line x1="12" y1="20" x2="12" y2="4"/>
            <line x1="6" y1="20" x2="6" y2="14"/>
          </svg>
          Lihat Progress
        </NuxtLink>
      </section>
    </div>
  </div>
</template>

<script setup lang="ts">
const { apiFetch } = useApi()

interface DashboardData {
  overall: {
    fluent: number
    doubtful: number
    forgot: number
    total_reviewed: number
    surahs_reviewed: number
  }
  today: {
    fluent: number
    doubtful: number
    forgot: number
    total: number
  }
  last_session: {
    id: number
    surah_id: number
    surah_name: string
    surah_name_arabic: string
    surah_number: number
    current_ayah_number: number
    mode: string
  } | null
}

const dashboard = ref<DashboardData | null>(null)

onMounted(async () => {
  try {
    const res = await apiFetch<{ data: DashboardData }>('/dashboard')
    dashboard.value = res.data
  } catch (e) {
    // Silently fail — show empty state
    dashboard.value = {
      overall: { fluent: 0, doubtful: 0, forgot: 0, total_reviewed: 0, surahs_reviewed: 0 },
      today: { fluent: 0, doubtful: 0, forgot: 0, total: 0 },
      last_session: null,
    }
  }
})

useHead({
  title: 'Murojaah — Remote Hafalan Qur\'an',
})
</script>

<style scoped>
.home-header {
  padding: calc(var(--safe-top) + 24px) 20px 32px;
  background: linear-gradient(155deg, #064E3B 0%, #047857 50%, #059669 100%);
  color: white;
  position: relative;
  overflow: hidden;
}

.home-header::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -30%;
  width: 300px;
  height: 300px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.05);
}

.home-header::after {
  content: '';
  position: absolute;
  bottom: -60%;
  left: -20%;
  width: 250px;
  height: 250px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.03);
}

.home-header__brand {
  display: flex;
  align-items: center;
  gap: 14px;
  position: relative;
  z-index: 1;
}

.home-header__icon {
  width: 52px;
  height: 52px;
  border-radius: var(--radius-lg);
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
}

.home-header__brand h1 {
  font-size: 1.75rem;
  font-weight: 700;
  letter-spacing: -0.03em;
}

.home-header__brand p {
  font-size: 0.875rem;
  opacity: 0.75;
  margin-top: 2px;
}

.section-title {
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--color-text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 12px;
}

.home-stats {
  margin-bottom: 20px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
}

.stat-card {
  background: var(--color-bg-card);
  border-radius: var(--radius-md);
  padding: 16px 12px;
  text-align: center;
  box-shadow: var(--shadow-sm);
  border: 1px solid rgba(0, 0, 0, 0.04);
}

.stat-card__value {
  display: block;
  font-size: 1.75rem;
  font-weight: 700;
  line-height: 1;
  margin-bottom: 6px;
}

.stat-card__label {
  font-size: 0.75rem;
  font-weight: 500;
  color: var(--color-text-secondary);
}

.stat-card--fluent .stat-card__value { color: var(--color-fluent); }
.stat-card--doubtful .stat-card__value { color: var(--color-doubtful); }
.stat-card--forgot .stat-card__value { color: var(--color-forgot); }

.home-overall {
  margin-bottom: 24px;
}

.overall-card {
  background: var(--color-bg-card);
  border-radius: var(--radius-md);
  padding: 4px 0;
  box-shadow: var(--shadow-sm);
  border: 1px solid rgba(0, 0, 0, 0.04);
}

.overall-card__row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
}

.overall-card__row + .overall-card__row {
  border-top: 1px solid rgba(0, 0, 0, 0.04);
}

.overall-card__label {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
}

.overall-card__value {
  font-size: 1rem;
  font-weight: 700;
  color: var(--color-primary);
}

.home-actions {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
</style>
