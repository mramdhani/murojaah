<template>
  <div class="page home-page">
    <!-- Greeting Header -->
    <header class="home-header">
      <div class="container">
        <div class="home-header__top">
          <div class="home-header__greeting">
            <span class="greeting-time">{{ greeting }}</span>
            <h1>{{ user?.name || 'Kakak' }}</h1>
          </div>
          <NuxtLink to="/profile" class="avatar-btn" v-if="user?.avatar">
            <img :src="user.avatar" alt="" class="avatar-img" />
          </NuxtLink>
          <div v-else class="avatar-placeholder">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
              <circle cx="12" cy="7" r="4"/>
            </svg>
          </div>
        </div>
        <p class="home-header__subtitle">Jaga hafalan Qur'an, murojaah setiap hari ✨</p>
      </div>
    </header>

    <div class="page-content container">
      <!-- Today's Review Stats -->
      <section class="section animate-fade-in" v-if="dashboard">
        <div class="section-header">
          <h2 class="section-title">Murojaah Hari Ini</h2>
          <NuxtLink to="/history" class="section-link">Riwayat</NuxtLink>
        </div>
        <div class="today-stats">
          <div class="today-stat today-stat--fluent">
            <span class="today-stat__icon">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
              </svg>
            </span>
            <span class="today-stat__value">{{ dashboard.today.fluent }}</span>
            <span class="today-stat__label">Lancar</span>
          </div>
          <div class="today-stat today-stat--doubtful">
            <span class="today-stat__icon">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                <line x1="12" y1="17" x2="12.01" y2="17"/>
              </svg>
            </span>
            <span class="today-stat__value">{{ dashboard.today.doubtful }}</span>
            <span class="today-stat__label">Ragu</span>
          </div>
          <div class="today-stat today-stat--forgot">
            <span class="today-stat__icon">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="23 4 23 10 17 10"/>
                <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
              </svg>
            </span>
            <span class="today-stat__value">{{ dashboard.today.forgot }}</span>
            <span class="today-stat__label">Lupa</span>
          </div>
        </div>
        <div v-if="dashboard.today.total === 0" class="empty-today">
          <p>Belum ada murojaah hari ini. Yuk mulai!</p>
        </div>
      </section>

      <!-- Loading skeleton for stats -->
      <section v-else class="section">
        <div class="section-header">
          <h2 class="section-title">Murojaah Hari Ini</h2>
        </div>
        <div class="today-stats">
          <div class="skeleton stat-skeleton" v-for="i in 3" :key="i"></div>
        </div>
      </section>

      <!-- Continue / Quick Actions -->
      <section class="section animate-fade-in" style="animation-delay: 0.05s">
        <div class="quick-actions">
          <NuxtLink to="/surahs" class="quick-action quick-action--primary">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <polygon points="5 3 19 12 5 21 5 3"/>
            </svg>
            <span>Mulai Hafalan</span>
          </NuxtLink>

          <NuxtLink
            v-if="dashboard?.last_session"
            :to="`/remote/${dashboard.last_session.surah_id}/${dashboard.last_session.current_ayah_number}`"
            class="quick-action quick-action--outline"
          >
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="1 4 1 10 7 10"/>
              <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
            </svg>
            <span>Lanjutkan {{ dashboard.last_session.surah_name }}</span>
          </NuxtLink>
        </div>
      </section>

      <!-- Active Surahs (Surat yang sedang dipelajari) -->
      <section class="section animate-fade-in" style="animation-delay: 0.1s" v-if="activeSurahs.length > 0">
        <div class="section-header">
          <h2 class="section-title">Surat Aktif</h2>
          <NuxtLink to="/progress" class="section-link">Semua</NuxtLink>
        </div>
        <div class="surah-list">
          <div
            v-for="surah in activeSurahs"
            :key="surah.id"
            class="surah-card"
            @click="goToSurah(surah.id)"
          >
            <div class="surah-card__left">
              <div class="surah-card__number">{{ surah.number }}</div>
              <div>
                <span class="surah-card__name">{{ surah.name_latin }}</span>
                <span class="surah-card__arabic">{{ surah.name_arabic }}</span>
              </div>
            </div>
            <div class="surah-card__right">
              <div class="surah-card__progress-text">
                {{ surah.progress.fluent + surah.progress.doubtful + surah.progress.forgot }}/{{ surah.total_ayah }}
              </div>
              <div class="mini-progress">
                <div class="mini-progress__bar">
                  <div class="mini-progress__segment mini-progress__segment--fluent" :style="{ width: pct(surah, 'fluent') }"></div>
                  <div class="mini-progress__segment mini-progress__segment--doubtful" :style="{ width: pct(surah, 'doubtful') }"></div>
                  <div class="mini-progress__segment mini-progress__segment--forgot" :style="{ width: pct(surah, 'forgot') }"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Overall Stats -->
      <section class="section animate-fade-in" style="animation-delay: 0.15s" v-if="dashboard">
        <div class="section-header">
          <h2 class="section-title">Ringkasan</h2>
        </div>
        <div class="overall-card">
          <div class="overall-row">
            <span class="overall-row__label">Total Surat</span>
            <span class="overall-row__value">{{ dashboard.overall.surahs_reviewed }}</span>
          </div>
          <div class="overall-row">
            <span class="overall-row__label">Total Ayat</span>
            <span class="overall-row__value">{{ dashboard.overall.total_reviewed }}</span>
          </div>
          <div class="overall-row">
            <span class="overall-row__label">Lancar</span>
            <span class="overall-row__value overall-row__value--fluent">{{ dashboard.overall.fluent }}</span>
          </div>
          <div class="overall-row">
            <span class="overall-row__label">Belum Lancar</span>
            <span class="overall-row__value overall-row__value--forgot">{{ dashboard.overall.doubtful + dashboard.overall.forgot }}</span>
          </div>
        </div>
      </section>

      <!-- Developer Info -->
      <section class="section dev-info animate-fade-in" style="animation-delay: 0.2s">
        <div class="dev-info__card">
          <div class="dev-info__icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="16 18 22 12 16 6"/>
              <polyline points="8 6 2 12 8 18"/>
            </svg>
          </div>
          <div class="dev-info__text">
            <strong>Ada saran atau masukan?</strong>
            <p>Hubungi developer via email</p>
            <a href="mailto:tuan.ramdhani@gmail.com" class="dev-info__email">tuan.ramdhani@gmail.com</a>
          </div>
        </div>
      </section>

      <!-- Bottom spacing -->
      <div style="height: 24px"></div>
    </div>
  </div>
</template>

<script setup lang="ts">
const router = useRouter()
const { apiFetch } = useApi()
const { user } = useAuth()

interface SurahProgress {
  id: number
  number: number
  name_latin: string
  name_arabic: string
  total_ayah: number
  progress: { fluent: number; doubtful: number; forgot: number; unreviewed: number }
}

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
const surahs = ref<SurahProgress[]>([])

const activeSurahs = computed(() =>
  surahs.value.filter(s => s.progress.fluent + s.progress.doubtful + s.progress.forgot > 0)
)

const greeting = computed(() => {
  const hour = new Date().getHours()
  if (hour < 11) return 'Selamat Pagi'
  if (hour < 15) return 'Selamat Siang'
  if (hour < 18) return 'Selamat Sore'
  return 'Selamat Malam'
})

const pct = (surah: SurahProgress, status: string) => {
  const count = surah.progress[status as keyof typeof surah.progress] || 0
  return `${(count / surah.total_ayah) * 100}%`
}

const goToSurah = (surahId: number) => {
  router.push(`/progress/${surahId}`)
}

onMounted(async () => {
  try {
    const [dashRes, surahRes] = await Promise.all([
      apiFetch<{ data: DashboardData }>('/dashboard'),
      apiFetch<{ data: SurahProgress[] }>('/surahs'),
    ])
    dashboard.value = dashRes.data
    surahs.value = surahRes.data
  } catch (e) {
    console.error('Failed to load dashboard:', e)
  }
})

useHead({
  title: 'Murojaah — Remote Hafalan Qur\'an',
})
</script>

<style scoped>
/* ========== HEADER ========== */
.home-header {
  padding: calc(var(--safe-top) + 20px) 20px 28px;
  background: linear-gradient(145deg, #064E3B 0%, #047857 60%, #059669 100%);
  color: white;
  position: relative;
  overflow: hidden;
}

.home-header::before {
  content: '';
  position: absolute;
  top: -60%;
  right: -25%;
  width: 320px;
  height: 320px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.06);
}

.home-header::after {
  content: '';
  position: absolute;
  bottom: -50%;
  left: -20%;
  width: 200px;
  height: 200px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.03);
}

.home-header__top {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  position: relative;
  z-index: 1;
}

.greeting-time {
  display: block;
  font-size: 0.8125rem;
  font-weight: 500;
  opacity: 0.7;
  margin-bottom: 2px;
}

.home-header__top h1 {
  font-size: 1.5rem;
  font-weight: 700;
  letter-spacing: -0.02em;
}

.home-header__subtitle {
  font-size: 0.875rem;
  opacity: 0.65;
  margin-top: 6px;
  position: relative;
  z-index: 1;
}

.avatar-btn {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  overflow: hidden;
  border: 2px solid rgba(255, 255, 255, 0.3);
  flex-shrink: 0;
  display: block;
}

.avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-placeholder {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  color: rgba(255, 255, 255, 0.8);
}

/* ========== SECTIONS ========== */
.section {
  margin-bottom: 24px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.section-title {
  font-size: 0.8125rem;
  font-weight: 700;
  color: var(--color-text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.section-link {
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--color-primary);
  text-decoration: none;
}

/* ========== TODAY STATS ========== */
.today-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
}

.today-stat {
  background: var(--color-bg-card);
  border-radius: var(--radius-md);
  padding: 16px 12px;
  text-align: center;
  box-shadow: var(--shadow-sm);
  border: 1.5px solid rgba(0, 0, 0, 0.04);
}

.today-stat__icon {
  display: block;
  margin-bottom: 6px;
  line-height: 0;
}

.today-stat--fluent .today-stat__icon { color: var(--color-fluent); }
.today-stat--doubtful .today-stat__icon { color: var(--color-doubtful); }
.today-stat--forgot .today-stat__icon { color: var(--color-forgot); }

.today-stat__value {
  display: block;
  font-size: 1.75rem;
  font-weight: 800;
  line-height: 1;
  margin-bottom: 4px;
}

.today-stat__label {
  font-size: 0.6875rem;
  font-weight: 600;
  color: var(--color-text-secondary);
}

.today-stat--fluent .today-stat__value { color: var(--color-fluent); }
.today-stat--doubtful .today-stat__value { color: var(--color-doubtful); }
.today-stat--forgot .today-stat__value { color: var(--color-forgot); }

.empty-today {
  margin-top: 12px;
  text-align: center;
}

.empty-today p {
  font-size: 0.8125rem;
  color: var(--color-text-muted);
}

.stat-skeleton {
  height: 78px;
  border-radius: var(--radius-md);
}

/* ========== QUICK ACTIONS ========== */
.quick-actions {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.quick-action {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 16px;
  border-radius: var(--radius-md);
  font-weight: 700;
  font-size: 1rem;
  text-decoration: none;
  transition: transform 0.12s;
}

.quick-action:active {
  transform: scale(0.98);
}

.quick-action--primary {
  background: var(--color-primary);
  color: white;
  box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25);
}

.quick-action--outline {
  background: var(--color-bg-card);
  color: var(--color-text);
  border: 1.5px solid rgba(0, 0, 0, 0.06);
  box-shadow: var(--shadow-sm);
}

/* ========== SURAH LIST ========== */
.surah-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.surah-card {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px 16px;
  background: var(--color-bg-card);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
  border: 1px solid rgba(0, 0, 0, 0.04);
  cursor: pointer;
  transition: opacity 0.15s;
}

.surah-card:active {
  opacity: 0.7;
}

.surah-card__left {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 0;
}

.surah-card__number {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: var(--color-primary-50);
  color: var(--color-primary);
  font-size: 0.75rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.surah-card__name {
  display: block;
  font-weight: 700;
  font-size: 0.9375rem;
  color: var(--color-text);
}

.surah-card__arabic {
  display: block;
  font-family: var(--font-arabic);
  font-size: 0.8125rem;
  color: var(--color-text-muted);
  margin-top: 1px;
}

.surah-card__right {
  text-align: right;
  flex-shrink: 0;
  min-width: 80px;
}

.surah-card__progress-text {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--color-text-secondary);
  margin-bottom: 6px;
}

.mini-progress {
  height: 6px;
}

.mini-progress__bar {
  display: flex;
  height: 100%;
  border-radius: 99px;
  overflow: hidden;
  background: var(--color-unreviewed-bg);
}

.mini-progress__segment {
  height: 100%;
  transition: width 0.3s ease;
}

.mini-progress__segment--fluent { background: var(--color-fluent); }
.mini-progress__segment--doubtful { background: var(--color-doubtful); }
.mini-progress__segment--forgot { background: var(--color-forgot); }

/* ========== OVERALL ========== */
.overall-card {
  background: var(--color-bg-card);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
  border: 1px solid rgba(0, 0, 0, 0.04);
  overflow: hidden;
}

.overall-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px 16px;
}

.overall-row + .overall-row {
  border-top: 1px solid rgba(0, 0, 0, 0.04);
}

.overall-row__label {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
  font-weight: 500;
}

.overall-row__value {
  font-size: 1rem;
  font-weight: 700;
  color: var(--color-text);
}

.overall-row__value--fluent { color: var(--color-fluent); }
.overall-row__value--forgot { color: var(--color-forgot); }

/* ========== DEV INFO ========== */
.dev-info__card {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px;
  background: var(--color-bg-subtle);
  border-radius: var(--radius-md);
  border: 1px dashed rgba(5, 150, 105, 0.2);
}

.dev-info__icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: var(--color-primary-50);
  color: var(--color-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.dev-info__text strong {
  display: block;
  font-size: 0.875rem;
  color: var(--color-text);
  margin-bottom: 2px;
}

.dev-info__text p {
  font-size: 0.75rem;
  color: var(--color-text-muted);
  margin-bottom: 4px;
}

.dev-info__email {
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--color-primary);
  text-decoration: none;
}
</style>
