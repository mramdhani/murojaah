<template>
  <div class="page home-page">
    <!-- Greeting Header -->
    <header class="home-header">
      <div class="container">
    <div class="home-header__top">
      <div class="home-header__greeting">
        <span class="greeting-time">Assalamualaikum,</span>
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

    <!-- Logo medallion in the center of the wave -->
    <div class="home-header__logo-wrap">
      <div class="home-header__logo">
        <img src="/logo.png" alt="Murojaah" class="home-header__logo-img" />
      </div>
    </div>
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

      <!-- Overall Stats (Grid) -->
      <section class="section animate-fade-in" style="animation-delay: 0.15s" v-if="dashboard">
        <div class="section-header">
          <h2 class="section-title">Ringkasan</h2>
        </div>
        <div class="summary-grid">
          <div class="summary-card summary-card--surahs">
            <div class="summary-card__icon">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/>
              </svg>
            </div>
            <div class="summary-card__stat">
              <span class="summary-card__value">{{ dashboard.overall.surahs_reviewed }}</span>
              <span class="summary-card__label">Total Surat</span>
            </div>
          </div>
          <div class="summary-card summary-card--verses">
            <div class="summary-card__icon">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 20h9"/>
                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
              </svg>
            </div>
            <div class="summary-card__stat">
              <span class="summary-card__value">{{ dashboard.overall.total_reviewed }}</span>
              <span class="summary-card__label">Total Ayat</span>
            </div>
          </div>
          <div class="summary-card summary-card--fluent">
            <div class="summary-card__icon">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
              </svg>
            </div>
            <div class="summary-card__stat">
              <span class="summary-card__value summary-card__value--fluent">{{ dashboard.overall.fluent }}</span>
              <span class="summary-card__label">Lancar</span>
            </div>
          </div>
          <div class="summary-card summary-card--struggling">
            <div class="summary-card__icon">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                <line x1="12" y1="17" x2="12.01" y2="17"/>
              </svg>
            </div>
            <div class="summary-card__stat">
              <span class="summary-card__value summary-card__value--struggling">{{ dashboard.overall.doubtful + dashboard.overall.forgot }}</span>
              <span class="summary-card__label">Belum Lancar</span>
            </div>
          </div>
        </div>
      </section>

      <!-- Developer Info -->
      <section class="section dev-info animate-fade-in" style="animation-delay: 0.2s">
        <a href="mailto:tuan.ramdhani@gmail.com" class="dev-info__card">
          <div class="dev-info__left">
            <div class="dev-info__icon">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                <polyline points="22,6 12,13 2,6"/>
              </svg>
            </div>
            <div class="dev-info__text">
              <strong>Ada saran atau masukan?</strong>
              <p>Hubungi developer via email</p>
            </div>
          </div>
          <div class="dev-info__chevron">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="9 18 15 12 9 6"/>
            </svg>
          </div>
        </a>
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
  padding: calc(var(--safe-top) + 40px) 20px 76px !important;
  background: linear-gradient(135deg, var(--color-primary-900) 0%, var(--color-primary-dark) 60%, var(--color-primary) 100%) !important;
  color: white;
  position: relative;
  overflow: visible !important;
  box-shadow: 0 4px 15px rgba(5, 150, 105, 0.12) !important;
}

/* Animated layered wave divider with gold gradient */
.home-header::before {
  content: '' !important;
  display: block !important;
  position: absolute !important;
  bottom: 0 !important;
  left: 0 !important;
  width: 100% !important;
  height: 28px !important;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 24' preserveAspectRatio='none'%3E%3Cdefs%3E%3ClinearGradient id='g' x1='0%25' y1='0%25' x2='100%25' y2='0%25'%3E%3Cstop offset='0%25' stop-color='%23AA7C11'/%3E%3Cstop offset='25%25' stop-color='%23D4AF37'/%3E%3Cstop offset='50%25' stop-color='%23FFF8D6'/%3E%3Cstop offset='75%25' stop-color='%23D4AF37'/%3E%3Cstop offset='100%25' stop-color='%23AA7C11'/%3E%3C/linearGradient%3E%3C/defs%3E%3Cpath d='M0,4 C20,20 35,6 50,12 C65,18 80,2 100,8 L100,24 L0,24 Z' fill='%23FAFAF5'/%3E%3Cpath d='M0,4 C20,20 35,6 50,12 C65,18 80,2 100,8' fill='none' stroke='url(%23g)' stroke-width='2'/%3E%3Cpath d='M0,7 C20,23 35,9 50,15 C65,21 80,5 100,11' fill='none' stroke='%23D4AF37' stroke-opacity='0.3' stroke-width='1.2'/%3E%3Cpath d='M0,2 C20,18 35,4 50,10 C65,16 80,0 100,6' fill='none' stroke='%23FFF8D6' stroke-opacity='0.2' stroke-width='0.8'/%3E%3C/svg%3E") !important;
  background-size: 100% 100% !important;
  background-repeat: no-repeat !important;
  z-index: 2 !important;
  top: auto !important;
  right: auto !important;
  border-radius: 0 !important;
  opacity: 1 !important;
  transform: none !important;
  animation: none !important;
}

/* Logo medallion - centered on the wave */
.home-header__logo-wrap {
  position: absolute;
  bottom: -18px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 5;
  filter: drop-shadow(0 6px 16px rgba(0, 0, 0, 0.3));
  animation: logoFloat 3s ease-in-out infinite;
}

.home-header__logo {
  width: 70px;
  height: 70px;
  border-radius: 50%;
  background: linear-gradient(135deg, #FFF8D6 0%, #FAF8F2 40%, #FAF8F2 60%, #FFF8D6 100%);
  border: 2.5px solid #D4AF37;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow:
    0 0 0 4px rgba(212, 175, 55, 0.15),
    0 8px 20px rgba(0, 0, 0, 0.2);
}

.home-header__logo-img {
  width: 65%;
  height: 65%;
  object-fit: contain;
}

@keyframes logoFloat {
  0%, 100% { transform: translateX(-50%) translateY(0); }
  50% { transform: translateX(-50%) translateY(-3px); }
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
  max-width: 90%;
  padding-right: 12px;
  line-height: 1.4;
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
  gap: 12px;
}

.today-stat {
  background: var(--color-bg-card);
  border-radius: var(--radius-lg);
  padding: 18px 12px;
  text-align: center;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.01);
  border: 1px solid rgba(0, 0, 0, 0.05);
  transition: transform var(--transition-fast), border-color var(--transition-fast);
}

.today-stat__icon {
  display: block;
  margin-bottom: 8px;
  line-height: 0;
}

.today-stat--fluent .today-stat__icon { color: var(--color-fluent); }
.today-stat--doubtful .today-stat__icon { color: var(--color-doubtful); }
.today-stat--forgot .today-stat__icon { color: var(--color-forgot); }

.today-stat__value {
  display: block;
  font-size: 1.85rem;
  font-weight: 800;
  line-height: 1;
  margin-bottom: 6px;
}

.today-stat__label {
  font-size: 0.72rem;
  font-weight: 700;
  color: var(--color-text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.02em;
}

.today-stat--fluent .today-stat__value { color: var(--color-fluent); }
.today-stat--doubtful .today-stat__value { color: var(--color-doubtful); }
.today-stat--forgot .today-stat__value { color: var(--color-forgot); }

.empty-today {
  margin-top: 16px;
  text-align: center;
  padding: 20px;
  background: var(--color-bg-card);
  border-radius: var(--radius-lg);
  border: 1px dashed rgba(0, 0, 0, 0.05);
}

.empty-today p {
  font-size: 0.875rem;
  color: var(--color-text-muted);
}

.stat-skeleton {
  height: 90px;
  border-radius: var(--radius-lg);
}

/* ========== QUICK ACTIONS ========== */
.quick-actions {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.quick-action {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 16px;
  border-radius: var(--radius-lg);
  font-weight: 700;
  font-size: 1rem;
  text-decoration: none;
  transition: transform var(--transition-fast), box-shadow var(--transition-fast);
}

.quick-action:active {
  transform: scale(0.98);
}

.quick-action--primary {
  background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark)) !important;
  color: white !important;
  border: 1.5px solid var(--color-primary-dark) !important;
  box-shadow: 0 4px 14px rgba(5, 150, 105, 0.2);
}

.quick-action--primary:active {
  box-shadow: 0 2px 6px rgba(5, 150, 105, 0.1);
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
  gap: 10px;
}

.surah-card {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 18px;
  background: var(--color-bg-card);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  border: 1px solid rgba(0, 0, 0, 0.04);
  cursor: pointer;
  transition: transform var(--transition-fast), border-color var(--transition-fast);
}

.surah-card:active {
  transform: scale(0.99);
}

.surah-card__left {
  display: flex;
  align-items: center;
  gap: 14px;
  min-width: 0;
}

.surah-card__number {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: var(--color-primary-50);
  color: var(--color-primary);
  font-size: 0.8125rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  border: 1.5px solid rgba(5, 150, 105, 0.08);
}

.surah-card__name {
  display: block;
  font-weight: 700;
  font-size: 0.95rem;
  color: var(--color-text);
}

.surah-card__arabic {
  display: block;
  font-family: var(--font-arabic);
  font-size: 0.875rem;
  color: var(--color-text-muted);
  margin-top: 2px;
}

.surah-card__right {
  text-align: right;
  flex-shrink: 0;
  min-width: 80px;
}

.surah-card__progress-text {
  font-size: 0.78rem;
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

/* ========== SUMMARY GRID ========== */
.summary-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.summary-card {
  background: var(--color-bg-card);
  border-radius: var(--radius-lg);
  padding: 18px 16px;
  display: flex;
  align-items: center;
  gap: 14px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  border: 1px solid rgba(0, 0, 0, 0.04);
  transition: transform var(--transition-fast), box-shadow var(--transition-fast);
}

.summary-card:active {
  transform: scale(0.97);
}

.summary-card__icon {
  width: 44px;
  height: 44px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.summary-card--surahs .summary-card__icon {
  background: linear-gradient(135deg, #EEF2FF, #E0E7FF);
  color: #4F46E5;
}

.summary-card--verses .summary-card__icon {
  background: linear-gradient(135deg, #FEF3C7, #FDE68A);
  color: #B45309;
}

.summary-card--fluent .summary-card__icon {
  background: var(--color-primary-50);
  color: var(--color-primary);
  border: 1px solid var(--color-primary-100);
}

.summary-card--struggling .summary-card__icon {
  background: #FFF1F2;
  color: var(--color-forgot);
  border: 1px solid #FECDD3;
}

.summary-card__stat {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.summary-card__value {
  font-size: 1.35rem;
  font-weight: 800;
  line-height: 1;
  color: var(--color-text);
}

.summary-card__value--fluent { color: var(--color-fluent); }
.summary-card__value--struggling { color: var(--color-forgot); }

.summary-card__label {
  font-size: 0.72rem;
  font-weight: 600;
  color: var(--color-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

/* ========== DEV INFO ========== */
.dev-info__card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  background: var(--color-bg-card);
  border-radius: var(--radius-lg);
  border: 1px solid rgba(0, 0, 0, 0.05);
  box-shadow: var(--shadow-sm);
  text-decoration: none;
  transition: transform var(--transition-fast), box-shadow var(--transition-fast);
}

.dev-info__card:active {
  transform: scale(0.98);
}

.dev-info__left {
  display: flex;
  align-items: center;
  gap: 14px;
}

.dev-info__icon {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: var(--color-primary-50);
  color: var(--color-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  border: 1.5px solid rgba(5, 150, 105, 0.15);
}

.dev-info__text strong {
  display: block;
  font-size: 0.9rem;
  font-weight: 700;
  color: var(--color-text);
  margin-bottom: 2px;
}

.dev-info__text p {
  font-size: 0.78rem;
  color: var(--color-text-muted);
  margin-bottom: 0;
}

.dev-info__chevron {
  color: var(--color-text-secondary);
  display: flex;
  align-items: center;
  opacity: 0.8;
}
</style>
