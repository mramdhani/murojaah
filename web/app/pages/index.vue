<template>
  <div class="page home-page">

    <!-- ===== HEADER ===== -->
    <header class="home-header">
      <!-- Background arabesque overlay -->
      <div class="home-header__arabesque"></div>

      <!-- Floating sparkles -->
      <span class="hdr-sparkle hdr-sparkle--1">✦</span>
      <span class="hdr-sparkle hdr-sparkle--2">◈</span>
      <span class="hdr-sparkle hdr-sparkle--3">✧</span>
      <span class="hdr-dot hdr-dot--1"></span>
      <span class="hdr-dot hdr-dot--2"></span>

      <div class="container">
        <div class="home-header__top">
          <div class="home-header__greeting">
            <span class="greeting-label">Assalamualaikum,</span>
            <h1>{{ user?.name || 'Kakak' }}</h1>
            <p class="home-header__sub">
              <span class="hdr-diamond">◆</span>
              Jaga hafalan Qur'an, murojaah setiap hari ✨
            </p>
          </div>

          <!-- Avatar foto user — hanya tampil jika sudah login (bukan guest) -->
          <NuxtLink to="/profile" class="hdr-avatar-btn" v-if="isLoggedIn">
            <img v-if="user?.avatar" :src="user.avatar" :alt="user.name" class="hdr-avatar-photo" />
            <div v-else class="hdr-avatar-initials">
              {{ user?.name?.charAt(0)?.toUpperCase() || '?' }}
            </div>
          </NuxtLink>
        </div>
      </div>

      <!-- Elegant curved divider -->
      <div class="home-header__divider">
        <div class="home-header__divider-fill"></div>
        <svg class="home-header__divider-svg" viewBox="0 0 390 28" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <linearGradient id="divGold" x1="0" y1="0" x2="390" y2="0" gradientUnits="userSpaceOnUse">
              <stop offset="0%" stop-color="rgba(170,124,17,0)"/>
              <stop offset="20%" stop-color="#D4AF37"/>
              <stop offset="50%" stop-color="#FFF8D6"/>
              <stop offset="80%" stop-color="#D4AF37"/>
              <stop offset="100%" stop-color="rgba(170,124,17,0)"/>
            </linearGradient>
          </defs>
          <!-- Clean single smooth arc -->
          <path d="M0,28 Q195,0 390,28 L390,28 L0,28 Z" fill="#FAFAF5"/>
          <path d="M0,28 Q195,0 390,28" fill="none" stroke="url(#divGold)" stroke-width="1.5"/>
        </svg>
        <!-- Diamond ornament at top center -->
        <div class="hdr-diamond-ornament">◆</div>
      </div>
    </header>

    <div class="page-content container">

      <!-- ===== BADGE HERO + PROGRESS (hanya jika sudah login) ===== -->
      <section class="section section--badge animate-fade-in" v-if="dashboard && isLoggedIn">

        <!-- Dark Green Hero Card -->
        <div class="badge-hero-card">
          <!-- Arabesque texture overlay -->
          <div class="badge-hero-card__pattern"></div>

          <!-- Badge image left -->
          <div class="badge-hero-card__img-wrap">
            <img v-if="userBadge" :src="userBadge.image" :alt="userBadge.name" class="badge-hero-card__img" />
          </div>

          <!-- Info right -->
          <div class="badge-hero-card__info">
            <span class="badge-hero-card__label">LEVEL HAFALAN</span>
            <span class="badge-hero-card__name">{{ userBadge?.name }}</span>
            <span class="badge-hero-card__arabic">{{ userBadge?.arabic }} · {{ userBadge?.meaning }}</span>
          </div>
        </div>

        <!-- White Progress Sub-Card -->
        <div class="badge-progress-card" v-if="nextBadge">
          <!-- Top row: percent + ayat info -->
          <div class="bpc-top">
            <div class="bpc-pct-block">
              <span class="bpc-pct">{{ badgeProgress }}%</span>
              <span class="bpc-pct-label">Progres Hafalan</span>
            </div>
            <div class="bpc-divider"></div>
            <div class="bpc-ayah-block">
              <div class="bpc-book-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/>
                </svg>
              </div>
              <div>
                <span class="bpc-ayah-count">{{ ayahsToNext }} <span class="bpc-ayah-unit">ayat lagi</span></span>
                <span class="bpc-ayah-sub">menuju level berikutnya</span>
              </div>
            </div>
          </div>

          <!-- Progress bar -->
          <div class="bpc-bar-wrap">
            <div class="bpc-bar">
              <div class="bpc-bar-fill" :style="{ width: badgeProgress + '%' }"></div>
            </div>
          </div>

          <!-- Next badge preview -->
          <div class="bpc-next">
            <img :src="nextBadge.image" :alt="nextBadge.name" class="bpc-next-img" />
            <span class="bpc-next-name">{{ nextBadge.name }} · {{ nextBadge.arabic }}</span>
          </div>
        </div>

        <!-- Completed state -->
        <div class="badge-progress-card badge-progress-card--done" v-else>
          <span class="bpc-done-icon">👑</span>
          <span class="bpc-done-text">Selamat! Kakak telah mencapai level tertinggi 🎉</span>
        </div>

        <!-- Badge Roadmap -->
        <div class="badge-roadmap">
          <!-- connecting line -->
          <div class="badge-roadmap__line"></div>

          <div
            v-for="badge in badges"
            :key="badge.level"
            class="badge-step"
            :class="{
              'badge-step--active': badge.level === userBadge?.level,
              'badge-step--done': Number(badge.level) < Number(userBadge?.level),
              'badge-step--locked': Number(badge.level) > Number(userBadge?.level)
            }"
          >
            <div
              class="badge-step__dot"
              :style="Number(badge.level) <= Number(userBadge?.level)
                ? { borderColor: badge.level === userBadge?.level ? '#D4AF37' : badge.color, boxShadow: badge.level === userBadge?.level ? '0 0 0 4px rgba(212,175,55,0.2)' : 'none' }
                : {}"
            >
              <img
                :src="badge.image"
                :alt="badge.name"
                class="badge-step__img"
                :class="{
                  'badge-step__img--active': badge.level === userBadge?.level,
                  'badge-step__img--locked': Number(badge.level) > Number(userBadge?.level)
                }"
              />
            </div>
            <span
              class="badge-step__name"
              :class="{
                'badge-step__name--active': badge.level === userBadge?.level,
                'badge-step__name--locked': Number(badge.level) > Number(userBadge?.level)
              }"
            >{{ badge.name }}</span>
          </div>
        </div>
      </section>

      <!-- ===== BADGE PREVIEW (greyscale, untuk tamu/belum login) ===== -->
      <section v-else-if="!isLoggedIn" class="section section--badge animate-fade-in">

        <!-- Locked Hero Card (greyscale) -->
        <div class="badge-hero-card badge-hero-card--locked">
          <div class="badge-hero-card__pattern"></div>
          <div class="badge-hero-card__img-wrap">
            <img src="/images/badge-1.png" alt="Nur" class="badge-hero-card__img badge-hero-card__img--gs" />
            <!-- Lock icon overlay -->
            <div class="badge-hero-card__lock-icon">🔒</div>
          </div>
          <div class="badge-hero-card__info">
            <span class="badge-hero-card__label badge-hero-card__label--gs">LEVEL HAFALAN</span>
            <span class="badge-hero-card__name badge-hero-card__name--gs">Nur</span>
            <span class="badge-hero-card__arabic badge-hero-card__arabic--gs">نُور · Cahaya</span>
          </div>
        </div>

        <!-- CTA to login/register -->
        <div class="badge-cta-card">
          <div class="badge-cta-card__icon">🏆</div>
          <p class="badge-cta-card__text">
            Kakak bisa dapatkan badge ini dengan rajin murojaah setiap hari.
            <strong>Hubungkan akun untuk mulai melacak progres!</strong>
          </p>
          <NuxtLink to="/profile" class="badge-cta-card__btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/>
            </svg>
            Hubungkan Akun
          </NuxtLink>
        </div>

        <!-- Greyscale Roadmap (all locked) -->
        <div class="badge-roadmap badge-roadmap--gs">
          <div class="badge-roadmap__line"></div>
          <div v-for="badge in badges" :key="badge.level" class="badge-step">
            <div class="badge-step__dot badge-step__dot--gs">
              <img :src="badge.image" :alt="badge.name" class="badge-step__img badge-step__img--locked" />
            </div>
            <span class="badge-step__name badge-step__name--locked">{{ badge.name }}</span>
          </div>
        </div>
      </section>

      <!-- Badge skeleton (loading fallback) -->
      <section v-else class="section">
        <div class="skeleton" style="height: 120px; border-radius: 20px; margin-bottom: 2px;"></div>
        <div class="skeleton" style="height: 110px; border-radius: 0 0 20px 20px; margin-bottom: 12px;"></div>
        <div class="skeleton" style="height: 64px; border-radius: 14px;"></div>
      </section>

      <!-- ===== MUROJAAH HARI INI ===== -->
      <section class="section animate-fade-in" style="animation-delay: 0.06s;" v-if="dashboard">
        <div class="section-header">
          <h2 class="section-title">Murojaah Hari Ini</h2>
          <NuxtLink to="/history" class="section-link">
            Riwayat
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
          </NuxtLink>
        </div>
        <div class="today-stats" v-if="dashboard.today.total > 0">
          <div class="today-stat today-stat--fluent">
            <div class="today-stat__icon-wrap today-stat__icon-wrap--fluent">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
              </svg>
            </div>
            <span class="today-stat__value">{{ dashboard.today.fluent }}</span>
            <span class="today-stat__label">Lancar</span>
          </div>
          <div class="today-stat today-stat--doubtful">
            <div class="today-stat__icon-wrap today-stat__icon-wrap--doubtful">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/>
              </svg>
            </div>
            <span class="today-stat__value">{{ dashboard.today.doubtful }}</span>
            <span class="today-stat__label">Ragu</span>
          </div>
          <div class="today-stat today-stat--forgot">
            <div class="today-stat__icon-wrap today-stat__icon-wrap--forgot">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
              </svg>
            </div>
            <span class="today-stat__value">{{ dashboard.today.forgot }}</span>
            <span class="today-stat__label">Lupa</span>
          </div>
        </div>
        <div v-else class="empty-today">
          <div class="empty-today__icon">📿</div>
          <p class="empty-today__title">Belum ada murojaah hari ini</p>
          <p class="empty-today__sub">Yuk mulai murojaah dan jaga hafalan!</p>
        </div>
      </section>
      <section v-else class="section">
        <div class="section-header"><h2 class="section-title">Murojaah Hari Ini</h2></div>
        <div class="today-stats">
          <div class="skeleton stat-skeleton" v-for="i in 3" :key="i"></div>
        </div>
      </section>

      <!-- ===== QUICK ACTIONS ===== -->
      <section class="section animate-fade-in" style="animation-delay: 0.1s">
        <div class="quick-actions">
          <NuxtLink to="/surahs" class="quick-action quick-action--primary">
            <div class="qa-icon-wrap">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="5 3 19 12 5 21 5 3"/>
              </svg>
            </div>
            <span>Mulai Murojaah</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="opacity:0.6"><polyline points="9 18 15 12 9 6"/></svg>
          </NuxtLink>
          <NuxtLink
            v-if="dashboard?.last_session"
            :to="`/remote/${dashboard.last_session.surah_id}/${dashboard.last_session.current_ayah_number}`"
            class="quick-action quick-action--outline"
          >
            <div class="qa-icon-wrap qa-icon-wrap--outline">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
              </svg>
            </div>
            <span>Lanjut {{ dashboard.last_session.surah_name }}</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="opacity:0.6"><polyline points="9 18 15 12 9 6"/></svg>
          </NuxtLink>
        </div>
      </section>

      <!-- ===== SURAT AKTIF ===== -->
      <section class="section animate-fade-in" style="animation-delay: 0.14s" v-if="activeSurahs.length > 0">
        <div class="section-header">
          <h2 class="section-title">Surat Aktif</h2>
          <NuxtLink to="/progress" class="section-link">
            Semua
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
          </NuxtLink>
        </div>
        <div class="surah-list">
          <div v-for="surah in activeSurahs" :key="surah.id" class="surah-card" @click="goToSurah(surah.id)">
            <div class="surah-card__left">
              <div class="surah-card__number">{{ surah.number }}</div>
              <div>
                <span class="surah-card__name">{{ surah.name_latin }}</span>
                <span class="surah-card__arabic">{{ surah.name_arabic }}</span>
              </div>
            </div>
            <div class="surah-card__right">
              <div class="surah-card__pct">{{ surah.progress.fluent + surah.progress.doubtful + surah.progress.forgot }}/{{ surah.total_ayah }}</div>
              <div class="mini-progress">
                <div class="mini-progress__bar">
                  <div class="mini-progress__seg mini-progress__seg--fluent" :style="{ width: pct(surah, 'fluent') }"></div>
                  <div class="mini-progress__seg mini-progress__seg--doubtful" :style="{ width: pct(surah, 'doubtful') }"></div>
                  <div class="mini-progress__seg mini-progress__seg--forgot" :style="{ width: pct(surah, 'forgot') }"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ===== RINGKASAN ===== -->
      <section class="section animate-fade-in" style="animation-delay: 0.18s" v-if="dashboard">
        <div class="section-header"><h2 class="section-title">Ringkasan</h2></div>
        <div class="summary-grid">
          <div class="summary-card summary-card--surahs">
            <div class="summary-card__icon">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/></svg>
            </div>
            <div class="summary-card__stat">
              <span class="summary-card__value">{{ dashboard.overall.surahs_reviewed }}</span>
              <span class="summary-card__label">Total Surat</span>
            </div>
          </div>
          <div class="summary-card summary-card--verses">
            <div class="summary-card__icon">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
            </div>
            <div class="summary-card__stat">
              <span class="summary-card__value">{{ dashboard.overall.total_reviewed }}</span>
              <span class="summary-card__label">Total Ayat</span>
            </div>
          </div>
          <div class="summary-card summary-card--fluent">
            <div class="summary-card__icon">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <div class="summary-card__stat">
              <span class="summary-card__value summary-card__value--fluent">{{ dashboard.overall.fluent }}</span>
              <span class="summary-card__label">Lancar</span>
            </div>
          </div>
          <div class="summary-card summary-card--struggling">
            <div class="summary-card__icon">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            </div>
            <div class="summary-card__stat">
              <span class="summary-card__value summary-card__value--struggling">{{ dashboard.overall.doubtful + dashboard.overall.forgot }}</span>
              <span class="summary-card__label">Belum Lancar</span>
            </div>
          </div>
        </div>
      </section>

      <!-- ===== DEV INFO ===== -->
      <section class="section animate-fade-in" style="animation-delay: 0.22s">
        <a href="mailto:tuan.ramdhani@gmail.com" class="dev-info__card">
          <div class="dev-info__left">
            <div class="dev-info__icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            </div>
            <div class="dev-info__text">
              <strong>Ada saran atau masukan?</strong>
              <p>Hubungi developer via email</p>
            </div>
          </div>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="color:var(--color-text-muted)"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
      </section>

      <div style="height: 24px"></div>
    </div>
  </div>
</template>

<script setup lang="ts">
const router = useRouter()
const { apiFetch } = useApi()
const { user, isLoggedIn } = useAuth()

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

const { getBadge, getNextBadge, getProgressToNext, getAyahsToNext, badges } = useBadge()

const dashboard = ref<DashboardData | null>(null)
const surahs = ref<SurahProgress[]>([])

const activeSurahs = computed(() =>
  surahs.value.filter(s => s.progress.fluent + s.progress.doubtful + s.progress.forgot > 0)
)

const userBadge = computed(() => {
  if (!dashboard.value) return null
  return getBadge(dashboard.value.overall.total_reviewed)
})

const nextBadge = computed(() => {
  if (!dashboard.value) return null
  return getNextBadge(dashboard.value.overall.total_reviewed)
})

const badgeProgress = computed(() => {
  if (!dashboard.value) return 0
  return getProgressToNext(dashboard.value.overall.total_reviewed)
})

const ayahsToNext = computed(() => {
  if (!dashboard.value) return 0
  return getAyahsToNext(dashboard.value.overall.total_reviewed)
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
/* ================================================
   HEADER
   ================================================ */
.home-header {
  padding: calc(var(--safe-top) + 36px) 0 0 !important;
  background: linear-gradient(160deg, #052e1c 0%, #064E3B 45%, #0a6349 100%) !important;
  color: white;
  position: relative;
  overflow: hidden;
}

/* Subtle arabesque/geometric overlay */
.home-header__arabesque {
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

.hdr-sparkle--1 {
  top: 18%;
  right: 18%;
  font-size: 1rem;
  color: #FFF8D6;
  opacity: 0.4;
  animation: sparkFloat 4s ease-in-out infinite;
}

.hdr-sparkle--2 {
  top: 55%;
  left: 6%;
  font-size: 0.75rem;
  color: #D4AF37;
  opacity: 0.35;
  animation: sparkFloat 5.5s ease-in-out infinite reverse;
}

.hdr-sparkle--3 {
  top: 28%;
  left: 22%;
  font-size: 0.65rem;
  color: rgba(255, 255, 255, 0.5);
  opacity: 0.3;
  animation: sparkFloat 3.5s ease-in-out infinite;
  animation-delay: 1.5s;
}

.hdr-dot {
  position: absolute;
  border-radius: 50%;
  pointer-events: none;
}

.hdr-dot--1 {
  width: 5px;
  height: 5px;
  top: 62%;
  right: 28%;
  background: rgba(212, 175, 55, 0.3);
  animation: sparkFloat 6s ease-in-out infinite;
  animation-delay: 0.8s;
}

.hdr-dot--2 {
  width: 3px;
  height: 3px;
  top: 38%;
  left: 32%;
  background: rgba(255, 255, 255, 0.25);
  animation: sparkFloat 4.5s ease-in-out infinite;
  animation-delay: 2s;
}

@keyframes sparkFloat {
  0%, 100% { transform: translateY(0) rotate(0deg); opacity: 0.35; }
  50% { transform: translateY(-7px) rotate(12deg); opacity: 0.7; }
}

/* Header content */
.home-header .container {
  padding: 0 20px;
  position: relative;
  z-index: 2;
  padding-bottom: 28px;
}

.home-header__top {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
}

.home-header__greeting {
  flex: 1;
  min-width: 0;
}

.greeting-label {
  display: block;
  font-size: 0.8125rem;
  font-weight: 500;
  opacity: 0.65;
  margin-bottom: 3px;
  letter-spacing: 0.01em;
}

.home-header__top h1 {
  font-size: 1.65rem;
  font-weight: 800;
  letter-spacing: -0.03em;
  line-height: 1.1;
  margin-bottom: 8px;
  color: #fff;
}

.home-header__sub {
  font-size: 0.8125rem;
  opacity: 0.6;
  line-height: 1.4;
  display: flex;
  align-items: flex-start;
  gap: 6px;
}

.hdr-diamond {
  color: #D4AF37;
  opacity: 0.8;
  font-size: 0.6rem;
  margin-top: 3px;
  flex-shrink: 0;
}

/* Avatar/Badge button */
/* Avatar button (logged in) */
.hdr-avatar-btn {
  width: 52px;
  height: 52px;
  border-radius: 50%;
  border: 2.5px solid rgba(212, 175, 55, 0.6);
  background: rgba(255, 255, 255, 0.08);
  box-shadow: 0 0 0 4px rgba(212, 175, 55, 0.12), 0 6px 16px rgba(0, 0, 0, 0.25);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  overflow: hidden;
  text-decoration: none;
  position: relative;
}

.hdr-avatar-photo {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 50%;
}

.hdr-avatar-initials {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.15rem;
  font-weight: 800;
  color: white;
  background: linear-gradient(135deg, rgba(212, 175, 55, 0.4) 0%, rgba(255, 255, 255, 0.15) 100%);
  letter-spacing: 0;
}

/* Elegant curved divider */
.home-header__divider {
  position: relative;
  z-index: 2;
  line-height: 0;
  margin-top: 4px;
}

.home-header__divider-fill {
  height: 0;
}

.home-header__divider-svg {
  width: 100%;
  height: 28px;
  display: block;
}

.hdr-diamond-ornament {
  position: absolute;
  top: -7px;
  left: 50%;
  transform: translateX(-50%);
  font-size: 0.85rem;
  color: #D4AF37;
  text-shadow: 0 0 10px rgba(212, 175, 55, 0.6), 0 0 20px rgba(212, 175, 55, 0.3);
  z-index: 3;
  line-height: 1;
}

/* ================================================
   SECTIONS
   ================================================ */
.section {
  margin-bottom: 20px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.section-title {
  font-size: 0.75rem;
  font-weight: 800;
  color: var(--color-text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.07em;
}

.section-link {
  display: flex;
  align-items: center;
  gap: 2px;
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--color-primary);
  text-decoration: none;
}

/* ================================================
   BADGE HERO CARD (dark green)
   ================================================ */
.badge-hero-card {
  display: flex;
  align-items: center;
  gap: 20px;
  padding: 22px 20px;
  border-radius: 20px 20px 0 0;
  background: linear-gradient(135deg, #064E3B 0%, #065f46 50%, #047857 100%);
  border: 1.5px solid rgba(212, 175, 55, 0.3);
  border-bottom: none;
  position: relative;
  overflow: hidden;
}

.badge-hero-card__pattern {
  position: absolute;
  inset: 0;
  background-image:
    radial-gradient(ellipse at 5% 50%, rgba(212, 175, 55, 0.08) 0%, transparent 50%),
    url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='40' height='40'%3E%3Ccircle cx='20' cy='20' r='12' fill='none' stroke='rgba(255,255,255,0.03)' stroke-width='1'/%3E%3C/svg%3E");
  background-size: auto, 40px 40px;
  pointer-events: none;
}

.badge-hero-card__img-wrap {
  position: relative;
  flex-shrink: 0;
  z-index: 1;
}

.badge-hero-card__img {
  width: 90px;
  height: 90px;
  object-fit: contain;
  filter: drop-shadow(0 8px 16px rgba(0,0,0,0.35));
  animation: heroFloat 3s ease-in-out infinite;
}

@keyframes heroFloat {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-5px); }
}

.badge-hero-card__info {
  flex: 1;
  min-width: 0;
  position: relative;
  z-index: 1;
  color: white;
}

.badge-hero-card__label {
  display: block;
  font-size: 0.6rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  color: rgba(255, 255, 255, 0.55);
  text-transform: uppercase;
  margin-bottom: 4px;
}

.badge-hero-card__name {
  display: block;
  font-size: 2rem;
  font-weight: 900;
  letter-spacing: -0.03em;
  line-height: 1;
  color: #fff;
  margin-bottom: 4px;
  text-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

.badge-hero-card__arabic {
  display: block;
  font-size: 0.8rem;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.65);
}

/* ================================================
   BADGE PROGRESS CARD (white, rounded bottom)
   ================================================ */
.badge-progress-card {
  background: var(--color-bg-card);
  border-radius: 0 0 20px 20px;
  border: 1.5px solid rgba(212, 175, 55, 0.2);
  border-top: none;
  padding: 18px 20px 16px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
  margin-bottom: 12px;
}

.badge-progress-card--done {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 20px;
}

.bpc-done-icon { font-size: 1.5rem; }
.bpc-done-text { font-size: 0.875rem; font-weight: 600; color: var(--color-text-secondary); }

/* Top row: percent + ayat */
.bpc-top {
  display: flex;
  align-items: center;
  gap: 0;
  margin-bottom: 14px;
}

.bpc-pct-block {
  flex: 1;
  padding-right: 20px;
}

.bpc-pct {
  display: block;
  font-size: 2rem;
  font-weight: 900;
  letter-spacing: -0.04em;
  line-height: 1;
  color: var(--color-text);
  margin-bottom: 2px;
}

.bpc-pct-label {
  display: block;
  font-size: 0.7rem;
  font-weight: 600;
  color: var(--color-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.bpc-divider {
  width: 1px;
  height: 44px;
  background: rgba(0, 0, 0, 0.08);
  flex-shrink: 0;
  margin-right: 20px;
}

.bpc-ayah-block {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1.2;
}

.bpc-book-icon {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  background: #FEF3C7;
  border: 1.5px solid #FDE68A;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #B45309;
  flex-shrink: 0;
}

.bpc-ayah-count {
  display: block;
  font-size: 1.1rem;
  font-weight: 900;
  color: var(--color-text);
  letter-spacing: -0.02em;
  line-height: 1.1;
}

.bpc-ayah-unit {
  font-size: 0.8rem;
  font-weight: 700;
}

.bpc-ayah-sub {
  display: block;
  font-size: 0.68rem;
  color: var(--color-text-muted);
  font-weight: 500;
  margin-top: 2px;
}

/* Progress bar */
.bpc-bar-wrap {
  margin-bottom: 14px;
}

.bpc-bar {
  height: 8px;
  background: rgba(0, 0, 0, 0.06);
  border-radius: 99px;
  overflow: hidden;
}

.bpc-bar-fill {
  height: 100%;
  border-radius: 99px;
  background: linear-gradient(90deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
  transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
}

.bpc-bar-fill::after {
  content: '';
  position: absolute;
  right: 0;
  top: 0;
  width: 8px;
  height: 100%;
  background: rgba(255, 255, 255, 0.5);
  border-radius: 99px;
  filter: blur(2px);
}

/* Next badge preview */
.bpc-next {
  display: flex;
  align-items: center;
  gap: 8px;
}

.bpc-next-img {
  width: 28px;
  height: 28px;
  object-fit: contain;
  opacity: 0.55;
  filter: grayscale(0.3);
}

.bpc-next-name {
  font-size: 0.82rem;
  font-weight: 700;
  color: var(--color-text-secondary);
}

/* ================================================
   BADGE ROADMAP
   ================================================ */
.badge-roadmap {
  position: relative;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 12px 4px 4px;
  background: var(--color-bg-card);
  border-radius: 16px;
  border: 1px solid rgba(0,0,0,0.04);
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
  padding: 16px 8px 12px;
}

.badge-roadmap__line {
  position: absolute;
  top: 33px;
  left: 30px;
  right: 30px;
  height: 2px;
  background: linear-gradient(90deg, var(--color-primary-100) 0%, #E5E7EB 100%);
  z-index: 0;
  border-radius: 99px;
}

.badge-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  position: relative;
  z-index: 1;
  flex: 1;
}

.badge-step__dot {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid #E5E7EB;
  background: white;
  transition: all 0.25s ease;
  box-shadow: 0 2px 6px rgba(0,0,0,0.06);
  overflow: hidden;
}

.badge-step__img {
  width: 26px;
  height: 26px;
  object-fit: contain;
}

.badge-step__img--active {
  animation: imgPulse 2.5s ease-in-out infinite;
}

@keyframes imgPulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.08); }
}

.badge-step__img--locked {
  opacity: 0.22;
  filter: grayscale(1);
}

.badge-step__name {
  font-size: 0.58rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  text-align: center;
  color: var(--color-text-secondary);
}

.badge-step__name--active {
  color: var(--color-primary-dark);
  font-weight: 800;
}

.badge-step__name--locked {
  color: #D1D5DB;
}

.badge-step--active .badge-step__dot {
  width: 40px;
  height: 40px;
  border-width: 2.5px;
}

/* ================================================
   TODAY STATS
   ================================================ */
.today-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
}

.today-stat {
  background: var(--color-bg-card);
  border-radius: 16px;
  padding: 16px 10px;
  text-align: center;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  border: 1px solid rgba(0, 0, 0, 0.04);
  position: relative;
  overflow: hidden;
}

.today-stat::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  border-radius: 99px 99px 0 0;
}

.today-stat--fluent::before { background: var(--color-fluent); }
.today-stat--doubtful::before { background: var(--color-doubtful); }
.today-stat--forgot::before { background: var(--color-forgot); }

.today-stat__icon-wrap {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 10px;
}

.today-stat__icon-wrap--fluent { background: var(--color-fluent-bg); color: var(--color-fluent); border: 1px solid var(--color-fluent-border); }
.today-stat__icon-wrap--doubtful { background: var(--color-doubtful-bg); color: var(--color-doubtful); border: 1px solid var(--color-doubtful-border); }
.today-stat__icon-wrap--forgot { background: var(--color-forgot-bg); color: var(--color-forgot); border: 1px solid var(--color-forgot-border); }

.today-stat__value {
  display: block;
  font-size: 2rem;
  font-weight: 900;
  line-height: 1;
  margin-bottom: 4px;
  letter-spacing: -0.03em;
}

.today-stat__label {
  font-size: 0.67rem;
  font-weight: 700;
  color: var(--color-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.today-stat--fluent .today-stat__value { color: var(--color-fluent); }
.today-stat--doubtful .today-stat__value { color: var(--color-doubtful); }
.today-stat--forgot .today-stat__value { color: var(--color-forgot); }

.empty-today {
  background: var(--color-bg-card);
  border-radius: 16px;
  padding: 28px 20px;
  text-align: center;
  border: 1.5px dashed rgba(5, 150, 105, 0.15);
}

.empty-today__icon { font-size: 2rem; margin-bottom: 8px; }
.empty-today__title { font-weight: 700; font-size: 0.9375rem; color: var(--color-text); margin-bottom: 3px; }
.empty-today__sub { font-size: 0.8125rem; color: var(--color-text-muted); }

.stat-skeleton { height: 100px; border-radius: 16px; }

/* ================================================
   QUICK ACTIONS
   ================================================ */
.quick-actions { display: flex; flex-direction: column; gap: 10px; }

.quick-action {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px 18px;
  border-radius: 16px;
  font-weight: 700;
  font-size: 0.9375rem;
  text-decoration: none;
  transition: transform var(--transition-fast), opacity var(--transition-fast);
}

.quick-action span { flex: 1; }
.quick-action:active { transform: scale(0.98); opacity: 0.9; }

.qa-icon-wrap {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.2);
  flex-shrink: 0;
}

.qa-icon-wrap--outline {
  background: var(--color-primary-50);
  color: var(--color-primary);
  border: 1px solid var(--color-primary-100);
}

.quick-action--primary {
  background: linear-gradient(135deg, #059669 0%, #047857 60%, #065f46 100%) !important;
  color: white !important;
  border: none !important;
  box-shadow: 0 6px 20px rgba(5, 150, 105, 0.28), inset 0 1px 0 rgba(255,255,255,0.15);
}

.quick-action--outline {
  background: var(--color-bg-card);
  color: var(--color-text);
  border: 1.5px solid rgba(0, 0, 0, 0.06);
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

/* ================================================
   SURAH LIST
   ================================================ */
.surah-list { display: flex; flex-direction: column; gap: 8px; }

.surah-card {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px 16px;
  background: var(--color-bg-card);
  border-radius: 14px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
  border: 1px solid rgba(0,0,0,0.04);
  cursor: pointer;
  transition: transform var(--transition-fast);
}

.surah-card:active { transform: scale(0.99); }

.surah-card__left { display: flex; align-items: center; gap: 12px; min-width: 0; }

.surah-card__number {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: var(--color-primary-50);
  color: var(--color-primary-dark);
  font-size: 0.8rem;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  border: 1px solid var(--color-primary-100);
}

.surah-card__name { display: block; font-weight: 700; font-size: 0.9375rem; }
.surah-card__arabic { display: block; font-family: var(--font-arabic); font-size: 0.85rem; color: var(--color-text-muted); margin-top: 1px; }
.surah-card__right { text-align: right; flex-shrink: 0; min-width: 72px; }
.surah-card__pct { font-size: 0.75rem; font-weight: 700; color: var(--color-text-secondary); margin-bottom: 5px; }

.mini-progress { height: 5px; }
.mini-progress__bar { display: flex; height: 100%; border-radius: 99px; overflow: hidden; background: var(--color-unreviewed-bg); }
.mini-progress__seg { height: 100%; transition: width 0.3s ease; }
.mini-progress__seg--fluent { background: var(--color-fluent); }
.mini-progress__seg--doubtful { background: var(--color-doubtful); }
.mini-progress__seg--forgot { background: var(--color-forgot); }

/* ================================================
   SUMMARY GRID
   ================================================ */
.summary-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }

.summary-card {
  background: var(--color-bg-card);
  border-radius: 16px;
  padding: 16px 14px;
  display: flex;
  align-items: center;
  gap: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
  border: 1px solid rgba(0,0,0,0.04);
  transition: transform var(--transition-fast);
}

.summary-card:active { transform: scale(0.97); }

.summary-card__icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.summary-card--surahs .summary-card__icon { background: linear-gradient(135deg, #EEF2FF, #E0E7FF); color: #4F46E5; }
.summary-card--verses .summary-card__icon { background: linear-gradient(135deg, #FEF3C7, #FDE68A); color: #B45309; }
.summary-card--fluent .summary-card__icon { background: var(--color-primary-50); color: var(--color-primary); border: 1px solid var(--color-primary-100); }
.summary-card--struggling .summary-card__icon { background: #FFF1F2; color: var(--color-forgot); border: 1px solid #FECDD3; }

.summary-card__stat { display: flex; flex-direction: column; gap: 2px; }
.summary-card__value { font-size: 1.4rem; font-weight: 900; line-height: 1; color: var(--color-text); letter-spacing: -0.02em; }
.summary-card__value--fluent { color: var(--color-fluent); }
.summary-card__value--struggling { color: var(--color-forgot); }
.summary-card__label { font-size: 0.68rem; font-weight: 700; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: 0.04em; }

/* ================================================
   DEV INFO
   ================================================ */
.dev-info__card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 18px;
  background: var(--color-bg-card);
  border-radius: 16px;
  border: 1px solid rgba(0,0,0,0.05);
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
  text-decoration: none;
  transition: transform var(--transition-fast);
}

.dev-info__card:active { transform: scale(0.98); }
.dev-info__left { display: flex; align-items: center; gap: 12px; }

.dev-info__icon {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: var(--color-primary-50);
  color: var(--color-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  border: 1px solid var(--color-primary-100);
}

.dev-info__text strong { display: block; font-size: 0.875rem; font-weight: 700; color: var(--color-text); margin-bottom: 1px; }
.dev-info__text p { font-size: 0.75rem; color: var(--color-text-muted); }

/* ================================================
   BADGE GUEST / LOCKED PREVIEW (greyscale)
   ================================================ */
.badge-hero-card--locked {
  background: linear-gradient(135deg, #9CA3AF 0%, #9CA3AF 100%) !important;
  border-color: rgba(156, 163, 175, 0.35) !important;
}

.badge-hero-card--locked .badge-hero-card__pattern {
  opacity: 0.35;
}

.badge-hero-card__img--gs {
  filter: grayscale(1) brightness(0.7) drop-shadow(0 8px 16px rgba(0,0,0,0.35)) !important;
  animation: none !important;
}

.badge-hero-card__lock-icon {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: rgba(31, 41, 55, 0.9);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
  z-index: 3;
  border: 2px solid rgba(255, 255, 255, 0.15);
  box-shadow: 0 2px 8px rgba(0,0,0,0.3);
}

.badge-hero-card__label--gs {
  color: rgba(255, 255, 255, 0.4) !important;
}

.badge-hero-card__name--gs {
  color: rgba(255, 255, 255, 0.5) !important;
  text-shadow: none !important;
}

.badge-hero-card__arabic--gs {
  color: rgba(255, 255, 255, 0.35) !important;
}

/* CTA card between hero and roadmap */
.badge-cta-card {
  background: var(--color-bg-card);
  border: 1.5px dashed rgba(107, 114, 128, 0.3);
  border-top: none;
  border-bottom: none;
  padding: 18px 20px;
  text-align: center;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
}

.badge-cta-card__icon {
  font-size: 1.8rem;
  margin-bottom: 8px;
}

.badge-cta-card__text {
  font-size: 0.8125rem;
  color: var(--color-text-secondary);
  line-height: 1.5;
  margin-bottom: 14px;
}

.badge-cta-card__text strong {
  display: block;
  margin-top: 4px;
  color: var(--color-primary-dark);
  font-weight: 700;
}

.badge-cta-card__btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  border-radius: 12px;
  background: linear-gradient(135deg, #059669 0%, #047857 60%, #065f46 100%);
  color: white;
  font-size: 0.875rem;
  font-weight: 700;
  text-decoration: none;
  box-shadow: 0 4px 14px rgba(5, 150, 105, 0.25);
  transition: transform var(--transition-fast), opacity var(--transition-fast);
}

.badge-cta-card__btn:active {
  transform: scale(0.97);
  opacity: 0.9;
}

/* Greyscale roadmap */
.badge-roadmap--gs {
  background: rgba(243, 244, 246, 0.5);
  border-color: rgba(0, 0, 0, 0.04);
}

.badge-roadmap--gs .badge-roadmap__line {
  background: linear-gradient(90deg, #D1D5DB 0%, #E5E7EB 100%);
}

.badge-step__dot--gs {
  border-color: #D1D5DB !important;
  background: #F3F4F6 !important;
}
</style>
