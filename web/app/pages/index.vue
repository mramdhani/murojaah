<template>
  <div class="page home-page">

    <!-- ===== HEADER ===== -->
    <header class="home-header">
  <!-- Background ornament -->
  <div class="home-header__arabesque" aria-hidden="true"></div>
  <div class="home-header__aura" aria-hidden="true"></div>

  <!-- Floating sparkles -->
  <span class="hdr-sparkle hdr-sparkle--1" aria-hidden="true">✦</span>
  <span class="hdr-sparkle hdr-sparkle--2" aria-hidden="true">✧</span>
  <span class="hdr-sparkle hdr-sparkle--3" aria-hidden="true">✦</span>
  <span class="hdr-dot hdr-dot--1" aria-hidden="true"></span>
  <span class="hdr-dot hdr-dot--2" aria-hidden="true"></span>

  <div class="container">
    <div class="home-header__top">
      <div class="home-header__greeting">
        <span class="greeting-label">Assalamualaikum,</span>

        <h1>{{ user?.name || 'Kakak' }}</h1>

        <p class="home-header__sub">
          <span class="hdr-diamond" aria-hidden="true">◆</span>
          <span>Jaga hafalan Qur'an, murojaah setiap hari ✨</span>
        </p>
      </div>

      <div class="home-header__right" v-if="isLoggedIn">
        <NuxtLink to="/profile" class="hdr-avatar-btn">
          <img
            v-if="user?.avatar"
            :src="user.avatar"
            :alt="user.name"
            class="hdr-avatar-photo"
            referrerpolicy="no-referrer"
          />
          <div v-else class="hdr-avatar-initials">
            {{ user?.name?.charAt(0)?.toUpperCase() || '?' }}
          </div>
        </NuxtLink>
      </div>
    </div>
  </div>

  <!-- Smooth premium wave -->
  <div class="home-header__divider" aria-hidden="true">
    <svg
      class="home-header__divider-svg"
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

      <!-- ===== BADGE HERO + PROGRESS (hanya jika sudah login) ===== -->
      <!-- Klik badge untuk lihat progress lengkap -->
      <div class="section section--badge animate-fade-in" v-if="dashboard && isLoggedIn">

        <!-- Dark Green Hero Card -->
        <div class="badge-hero-card" @click="openBadgeDetail(userBadge)" style="cursor: pointer;" title="Klik untuk detail level & badge">
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
            <span class="badge-hero-card__arabic">
              {{ userBadge?.arabic }} · {{ userBadge?.meaning }}
              <span class="badge-hero-card__juz">({{ userBadge?.juzRange }})</span>
            </span>
          </div>
        </div>

        <!-- White Progress Sub-Card -->
        <NuxtLink to="/progress" class="badge-progress-card-link" v-if="nextBadge">
          <div class="badge-progress-card">
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
                  <span class="bpc-ayah-count">{{ ayahsToNext }}</span>
                  <span class="bpc-ayah-sub">Ayat Lagi</span>
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
              <div class="bpc-next-left">
                <img :src="nextBadge.image" :alt="nextBadge.name" class="bpc-next-img" />
                <span class="bpc-next-name">{{ nextBadge.name }} · {{ nextBadge.arabic }}</span>
              </div>
              <div class="bpc-next-right">
                <span class="bpc-next-link-text">Lihat Progress</span>
                <svg class="bpc-next-arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
              </div>
            </div>
          </div>
        </NuxtLink>

        <!-- Completed state -->
        <NuxtLink to="/progress" class="badge-progress-card-link" v-else>
          <div class="badge-progress-card badge-progress-card--done">
            <div class="bpc-done-left">
              <span class="bpc-done-icon">👑</span>
              <span class="bpc-done-text">Selamat! Level tertinggi tercapai 🎉</span>
            </div>
            <div class="bpc-next-right">
              <span class="bpc-next-link-text">Lihat Progress</span>
              <svg class="bpc-next-arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="9 18 15 12 9 6"></polyline>
              </svg>
            </div>
          </div>
        </NuxtLink>

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
            @click="openBadgeDetail(badge)"
            style="cursor: pointer;"
            title="Klik untuk detail level"
          >
            <div class="badge-step__dot-wrap">
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
      </div>

      <!-- ===== BADGE PREVIEW (greyscale, untuk tamu/belum login) ===== -->
      <section v-else-if="!isLoggedIn" class="section section--badge animate-fade-in">

        <!-- Locked Hero Card (greyscale) -->
        <div class="badge-hero-card badge-hero-card--locked">
          <div class="badge-hero-card__pattern"></div>
          <div class="badge-hero-card__img-wrap">
            <img :src="badges[0].image" alt="Nur" class="badge-hero-card__img badge-hero-card__img--gs" />
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
            <div class="badge-step__dot-wrap">
              <div class="badge-step__dot badge-step__dot--gs">
                <img :src="badge.image" :alt="badge.name" class="badge-step__img badge-step__img--locked" />
              </div>
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
          <button type="button" class="btn btn-primary empty-today__btn" @click="openDrawer('learning')">
            Mulai Murojaah
          </button>
        </div>
      </section>

      <!-- ===== ANTRIAN MUROJAAH HARI INI (Spaced Repetition) ===== -->
      <section class="section animate-fade-in" style="animation-delay: 0.08s;" v-if="dashboard && isLoggedIn">
        <div class="section-header">
          <h2 class="section-title">Antrian Murojaah</h2>
          <span class="section-badge" v-if="dashboard.due_today.length > 0">
            {{ dashboard.due_today.length }} Surat
          </span>
        </div>

        <div class="due-list" v-if="dashboard.due_today.length > 0">
          <div
            v-for="surah in dashboard.due_today"
            :key="surah.id"
            class="due-card"
            @click="goToRemote(surah)"
          >
            <div class="due-card__left">
              <div class="due-card__number">{{ surah.number }}</div>
              <div class="due-card__info">
                <span class="due-card__name">{{ surah.surah_name }}</span>
                <span class="due-card__desc">{{ surah.due_count }} ayat jatuh tempo</span>
              </div>
            </div>
            <div class="due-card__right">
              <span class="due-card__arabic">{{ surah.surah_name_arabic }}</span>
              <button class="due-card__btn">
                Mulai
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="5" y1="12" x2="19" y2="12"></line>
                  <polyline points="12 5 19 12 12 19"></polyline>
                </svg>
              </button>
            </div>
          </div>
        </div>
        <div v-else class="empty-due">
          <div class="empty-due__icon">🎉</div>
          <p class="empty-due__title">Semua hafalan sudah aman!</p>
          <p class="empty-due__sub">Hebat, belum ada jadwal review lagi hari ini.</p>
        </div>
      </section>
      <section v-else class="section">
        <div class="section-header"><h2 class="section-title">Murojaah Hari Ini</h2></div>
        <div class="today-stats">
          <div class="skeleton stat-skeleton" v-for="i in 3" :key="i"></div>
        </div>
      </section>

      <!-- ===== QUICK ACTIONS ===== -->
      <section class="section animate-fade-in" style="animation-delay: 0.1s" v-if="dashboard?.last_session || lastReadAyah || ayahBookmarks.length">
        <div class="quick-actions">
          <NuxtLink
            v-if="dashboard?.last_session"
            :to="{ path: `/remote/${dashboard.last_session.surah_id}/${dashboard.last_session.current_ayah_number}`, query: { mode: 'learning' } }"
            class="quick-action quick-action--primary"
          >
            <div class="qa-icon-wrap">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
              </svg>
            </div>
            <span>Lanjut Murojaah {{ dashboard.last_session.surah_name }}</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="opacity:0.6"><polyline points="9 18 15 12 9 6"/></svg>
          </NuxtLink>

          <NuxtLink
            v-if="lastReadAyah"
            :to="{ path: `/mushaf/${lastReadAyah.page}`, query: { surah: lastReadAyah.surah, ayah: lastReadAyah.ayah } }"
            class="quick-action quick-action--outline"
          >
            <div class="qa-icon-wrap qa-icon-wrap--outline">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/>
              </svg>
            </div>
            <span>Lanjut Baca Mushaf {{ lastReadAyah.surah_name || `Surat ${lastReadAyah.surah}` }}:{{ lastReadAyah.ayah }}</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="opacity:0.6"><polyline points="9 18 15 12 9 6"/></svg>
          </NuxtLink>

          <NuxtLink
            v-if="ayahBookmarks.length"
            to="/bookmarks"
            class="quick-action quick-action--outline"
          >
            <div class="qa-icon-wrap qa-icon-wrap--outline">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/>
                <path d="m9 10 2 2 4-4"/>
              </svg>
            </div>
            <span>Bookmark Ayat ({{ ayahBookmarks.length }})</span>
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

  <!-- Badge Details Drawer -->
  <Transition name="drawer-fade">
    <div class="badge-drawer-wrapper" v-if="isBadgeDrawerOpen">
      <!-- Backdrop overlay -->
      <div class="drawer-backdrop" @click="closeBadgeDrawer"></div>

      <!-- Drawer Panel -->
      <div class="drawer-panel" :style="badgeSheet.sheetStyle.value">
        <!-- Drag handle indicator -->
        <div class="drawer-handle-bar" v-bind="badgeSheet.bindHandle">
          <div class="drawer-handle"></div>
        </div>

        <!-- Header -->
        <div class="drawer-header">
          <div class="drawer-title-wrap">
            <span class="drawer-icon">🏆</span>
            <div>
              <h2 class="drawer-title">Level & Badge Hafalan</h2>
              <p class="drawer-subtitle">Klik badge untuk melihat detail persyaratan</p>
            </div>
          </div>
          <button class="drawer-close" @click="closeBadgeDrawer" aria-label="Tutup">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>

        <!-- Scrollable content -->
        <div class="drawer-body">
          <!-- Horizontal selector row -->
          <div class="badge-selector-row">
            <button
              v-for="b in badges"
              :key="b.level"
              class="badge-selector-item"
              :class="{
                active: selectedBadge?.level === b.level,
                current: userBadge?.level === b.level,
                locked: totalAyah < b.minAyah
              }"
              @click="selectedBadge = b"
            >
              <div class="selector-badge-img-wrap">
                <img :src="b.image" :alt="b.name" class="selector-badge-img" />
                <span class="current-indicator" v-if="userBadge?.level === b.level">✔</span>
                <span class="lock-indicator" v-if="totalAyah < b.minAyah">🔒</span>
              </div>
              <span class="selector-badge-lbl">{{ b.name }}</span>
            </button>
          </div>

          <!-- Central Details Card -->
          <div class="badge-detail-card" v-if="selectedBadge">
            <!-- Glow background overlay behind the badge -->
            <div class="badge-glow-bg" :style="{ background: `radial-gradient(circle, ${selectedBadge.color}25 0%, transparent 70%)` }"></div>

            <div class="badge-img-preview-wrap">
              <img :src="selectedBadge.image" :alt="selectedBadge.name" class="detail-badge-img animate-badge-float" />
            </div>

            <div class="badge-info-block">
              <span class="badge-juz-tag" :style="{ color: selectedBadge.color, borderColor: `${selectedBadge.color}30` }">{{ selectedBadge.juzRange }}</span>
              <h3 class="badge-name-large">{{ selectedBadge.name }}</h3>
              <span class="badge-arabic-large">{{ selectedBadge.arabic }}</span>
              <p class="badge-meaning-label">Artinya: {{ selectedBadge.meaning }}</p>
            </div>

            <p class="badge-desc-text">
              {{ selectedBadge.description }}
            </p>

            <!-- Status Indicator Panel -->
            <div class="badge-status-panel">
              <div class="status-metric">
                <span class="status-metric-lbl">Syarat Minimal</span>
                <span class="status-metric-val">{{ selectedBadge.minAyah }} Ayat</span>
              </div>
              <div class="status-metric">
                <span class="status-metric-lbl">Status</span>
                <span
                  class="status-tag"
                  :class="{
                    active: userBadge?.level === selectedBadge.level,
                    unlocked: totalAyah >= selectedBadge.minAyah && userBadge?.level !== selectedBadge.level,
                    locked: totalAyah < selectedBadge.minAyah
                  }"
                >
                  {{ userBadge?.level === selectedBadge.level ? 'Aktif' : (totalAyah >= selectedBadge.minAyah ? 'Terbuka' : 'Terkunci') }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
const router = useRouter()
const { apiFetch } = useApi()
const { user, isLoggedIn } = useAuth()
const { open: openDrawer } = useMurojaahDrawer()

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
const { bookmarks: ayahBookmarks, loadBookmarks: loadAyahBookmarks } = useAyahBookmarks()

import type { Badge } from '~/composables/useBadge'

const isBadgeDrawerOpen = ref(false)
const selectedBadge = ref<Badge | null>(null)

const badgeSheet = useBottomSheet({
  mode: 'dismiss',
  closeThreshold: 80,
  onClose: () => { isBadgeDrawerOpen.value = false }
})

watch(isBadgeDrawerOpen, (val) => {
  if (val) badgeSheet.reset()
})

const openBadgeDetail = (badge: Badge) => {
  if (typeof navigator !== 'undefined' && navigator.vibrate) {
    navigator.vibrate(60)
  }
  selectedBadge.value = badge
  isBadgeDrawerOpen.value = true
}

const closeBadgeDrawer = () => {
  isBadgeDrawerOpen.value = false
}

const totalAyah = computed(() => dashboard.value?.overall.total_reviewed || 0)

const dashboard = ref<DashboardData | null>(null)
const surahs = ref<SurahProgress[]>([])

interface LastReadAyah {
  surah: number
  ayah: number
  verse_key: string
  page: number
  surah_name?: string
}

const lastReadAyah = ref<LastReadAyah | null>(null)

const loadLastReadAyah = () => {
  if (process.server) return
  try {
    const raw = localStorage.getItem('murojaah_last_read')
    lastReadAyah.value = raw ? JSON.parse(raw) : null
  } catch {
    lastReadAyah.value = null
  }
}

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
  if (typeof navigator !== 'undefined' && navigator.vibrate) {
    navigator.vibrate(30)
  }
  router.push({ path: `/remote/${surahId}/1`, query: { mode: 'learning' } })
}

const goToRemote = (surah: any) => {
  if (typeof navigator !== 'undefined' && navigator.vibrate) {
    navigator.vibrate(30)
  }
  router.push({ path: `/remote/${surah.id}/1`, query: { mode: 'learning' } })
}

onMounted(async () => {
  loadLastReadAyah()
  loadAyahBookmarks()
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
  border: none !important;
  border-bottom: none !important;
  box-shadow: none !important;
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
  align-items: center;
  gap: 16px;
}

.home-header__greeting {
  flex: 1;
  min-width: 0;
}

.greeting-label {
  display: block;
  font-size: 0.8125rem;
  font-weight: 700;
  color: #D4AF37; /* Yellow gold */
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
  line-height: 1.6;
}

/* Avatar button */
.hdr-avatar-btn {
  flex-shrink: 0;
  width: 44px;
  height: 44px;
  border-radius: 50%;
  overflow: hidden;
  border: 2px solid rgba(212, 175, 55, 0.4);
  transition: border-color 0.2s, transform 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.1);
}

.hdr-avatar-btn:active {
  transform: scale(0.93);
}

.hdr-avatar-photo {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.hdr-avatar-initials {
  font-size: 1.1rem;
  font-weight: 700;
  color: #fff;
}

/* Wave divider */
.home-header__divider {
  position: relative;
  z-index: 2;
  line-height: 0;
  margin-top: -8px;
  transform: translateY(1px);
  border: none !important;
  border-bottom: none !important;
}

.home-header__divider-svg {
  width: 100%;
  height: 72px;
  display: block;
  border: none !important;
  outline: none !important;
}

/* ================================================
   BADGE SECTION
   ================================================ */
.section--badge-link {
  text-decoration: none;
  display: block;
  color: inherit;
}

/* Dark green hero card */
.badge-hero-card {
  background: linear-gradient(145deg, #064E3B, #0A5D44);
  border-radius: 20px 20px 0 0;
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 16px;
  position: relative;
  overflow: hidden;
}

.badge-hero-card__pattern {
  position: absolute;
  inset: 0;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='40' height='40'%3E%3Cpath d='M20 0 L23 17 L40 20 L23 23 L20 40 L17 23 L0 20 L17 17 Z' fill='none' stroke='rgba(212,175,55,0.05)' stroke-width='1'/%3E%3C/svg%3E");
  background-size: 40px 40px;
  pointer-events: none;
}

.badge-hero-card__img-wrap {
  flex-shrink: 0;
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.08);
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  z-index: 1;
}

.badge-hero-card__img {
  width: 72px;
  height: 72px;
  object-fit: contain;
}

.badge-hero-card__info {
  display: flex;
  flex-direction: column;
  gap: 2px;
  position: relative;
  z-index: 1;
  min-width: 0;
}

.badge-hero-card__label {
  font-size: 0.65rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  color: #D4AF37;
  margin-bottom: 2px;
}

.badge-hero-card__name {
  font-size: 1.15rem;
  font-weight: 800;
  color: #fff;
  line-height: 1.2;
}

.badge-hero-card__arabic {
  font-size: 0.8rem;
  color: rgba(255, 255, 255, 0.7);
  line-height: 1.3;
}

.badge-hero-card__juz {
  opacity: 0.6;
}

/* Locked state (greyscale) */
.badge-hero-card--locked {
  filter: grayscale(1);
  opacity: 0.85;
}

.badge-hero-card__img--gs {
  filter: grayscale(1);
}

.badge-hero-card__label--gs,
.badge-hero-card__name--gs,
.badge-hero-card__arabic--gs {
  opacity: 0.6;
}

.badge-hero-card__lock-icon {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: 1.6rem;
  filter: none;
  z-index: 2;
}

/* White progress sub-card */
.badge-progress-card {
  background: #fff;
  padding: 16px 20px 18px;
  border-radius: 0 0 20px 20px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
  margin-bottom: 12px;
}

.bpc-top {
  display: flex;
  align-items: center;
  width: 100%;
  margin-bottom: 12px;
}

.bpc-pct-block {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.bpc-pct {
  font-size: 1.6rem;
  font-weight: 800;
  color: #064E3B;
  line-height: 1;
}

.bpc-pct-label {
  font-size: 0.65rem;
  color: #6B7280;
  font-weight: 600;
  letter-spacing: 0.02em;
}

.bpc-divider {
  width: 1px;
  height: 36px;
  background: #E5E7EB;
  flex-shrink: 0;
}

.bpc-ayah-block {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

.bpc-book-icon {
  color: #D4AF37;
  flex-shrink: 0;
}

.bpc-ayah-count {
  display: block;
  font-size: 1.15rem;
  font-weight: 700;
  color: #1F2937;
  line-height: 1.1;
}

.bpc-ayah-sub {
  font-size: 0.65rem;
  color: #6B7280;
  font-weight: 600;
}

/* Progress bar */
.bpc-bar-wrap {
  margin-bottom: 12px;
}

.bpc-bar {
  height: 6px;
  background: #E5E7EB;
  border-radius: 99px;
  overflow: hidden;
}

.bpc-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #D4AF37, #F7D979);
  border-radius: 99px;
  transition: width 0.6s ease;
}

/* Next badge preview */
.bpc-next {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-top: 10px;
  border-top: 1px solid #F3F4F6;
}

.bpc-next-left {
  display: flex;
  align-items: center;
  gap: 8px;
}

.bpc-next-img {
  width: 28px;
  height: 28px;
  object-fit: contain;
  opacity: 0.6;
}

.bpc-next-name {
  font-size: 0.75rem;
  color: #6B7280;
  font-weight: 600;
}

.bpc-next-right {
  display: flex;
  align-items: center;
  gap: 4px;
  color: #064E3B;
  font-weight: 700;
  font-size: 0.72rem;
  transition: transform 0.2s ease;
}

.badge-progress-card-link:hover .bpc-next-right {
  transform: translateX(3px);
}

/* Completed state */
.badge-progress-card--done {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 16px;
  color: #064E3B;
  font-weight: 700;
  font-size: 0.8rem;
}

.bpc-done-left {
  display: flex;
  align-items: center;
  gap: 8px;
}

.bpc-done-icon {
  font-size: 1.5rem;
}

.bpc-done-text {
  font-size: 0.85rem;
  font-weight: 600;
  color: #064E3B;
}

/* CTA card for guests */
.badge-cta-card {
  background: #fff;
  border-radius: 0 0 20px 20px;
  padding: 20px;
  text-align: center;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
  margin-bottom: 12px;
}

.badge-cta-card__icon {
  font-size: 2rem;
  margin-bottom: 8px;
}

.badge-cta-card__text {
  font-size: 0.85rem;
  color: #4B5563;
  line-height: 1.5;
  margin-bottom: 14px;
}

.badge-cta-card__btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: #064E3B;
  color: #fff;
  padding: 10px 22px;
  border-radius: 99px;
  font-size: 0.85rem;
  font-weight: 700;
  text-decoration: none;
  transition: background 0.2s, transform 0.15s;
}

.badge-cta-card__btn:active {
  background: #052e1c;
  transform: scale(0.96);
}

/* Badge roadmap */
.badge-roadmap {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  position: relative;
  padding: 16px 0 4px;
}

.badge-roadmap__line {
  position: absolute;
  top: 32px;
  left: 10%;
  right: 10%;
  height: 2px;
  background: #E5E7EB;
  z-index: 0;
}

.badge-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  flex: 1;
  position: relative;
  z-index: 1;
}

.badge-step__dot-wrap {
  position: relative;
}

.badge-step__dot {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #fff;
  border: 2px solid #D1D5DB;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: border-color 0.3s, box-shadow 0.3s;
}

.badge-step__dot--gs {
  border-color: #D1D5DB !important;
}

.badge-step__img {
  width: 26px;
  height: 26px;
  object-fit: contain;
}

.badge-step__img--active {
  filter: none;
}

.badge-step__img--locked {
  filter: grayscale(1);
  opacity: 0.4;
}

.badge-step__name {
  font-size: 0.6rem;
  font-weight: 600;
  color: #6B7280;
  text-align: center;
  line-height: 1.2;
  max-width: 56px;
}

.badge-step__name--active {
  color: #D4AF37;
  font-weight: 700;
}

.badge-step__name--locked {
  color: #9CA3AF;
}

/* ================================================
   TODAY STATS
   ================================================ */
.today-stats {
  display: flex;
  gap: 10px;
}

.today-stat {
  flex: 1;
  background: #fff;
  border-radius: 16px;
  padding: 14px 10px;
  text-align: center;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.today-stat__icon-wrap {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 6px;
}

.today-stat__icon-wrap--fluent {
  background: #D1FAE5;
  color: #059669;
}

.today-stat__icon-wrap--doubtful {
  background: #FEF3C7;
  color: #D97706;
}

.today-stat__icon-wrap--forgot {
  background: #FEE2E2;
  color: #DC2626;
}

.today-stat__value {
  display: block;
  font-size: 1.25rem;
  font-weight: 800;
  color: #1F2937;
  line-height: 1.1;
}

.today-stat__label {
  font-size: 0.65rem;
  color: #6B7280;
  font-weight: 600;
}

.empty-today {
  text-align: center;
  padding: 24px 16px;
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  display: flex;
  flex-direction: column;
  align-items: center;
}

.empty-today__icon {
  font-size: 2rem;
  margin-bottom: 8px;
}

.empty-today__title {
  font-size: 0.95rem;
  font-weight: 700;
  color: #1F2937;
  margin-bottom: 4px;
}

.empty-today__sub {
  font-size: 0.8rem;
  color: #6B7280;
  margin-bottom: 2px;
}

.empty-today__btn {
  margin-top: 14px;
  font-size: 0.85rem;
  min-height: 46px;
  padding: 10px 24px;
  width: 100%;
  max-width: 220px;
}

/* ================================================
   QUICK ACTIONS
   ================================================ */
.quick-actions {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.quick-action {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 18px;
  border-radius: 16px;
  font-size: 0.9rem;
  font-weight: 700;
  text-decoration: none;
  transition: transform 0.15s, box-shadow 0.2s;
}

.quick-action:active {
  transform: scale(0.98);
}

.quick-action--primary {
  background: linear-gradient(135deg, #064E3B, #0A5D44);
  color: #fff;
  box-shadow: 0 4px 14px rgba(6, 78, 59, 0.25);
}

.quick-action--outline {
  background: #fff;
  color: #1F2937;
  border: 1.5px solid #E5E7EB;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
}

.qa-icon-wrap {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.15);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.qa-icon-wrap--outline {
  background: #F3F4F6;
  color: #6B7280;
}

/* ================================================
   SURAH LIST
   ================================================ */
.surah-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.surah-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #fff;
  border-radius: 14px;
  padding: 12px 16px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
  cursor: pointer;
  transition: transform 0.15s, box-shadow 0.2s;
}

.surah-card:active {
  transform: scale(0.98);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
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
  background: #F3F4F6;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 700;
  color: #6B7280;
  flex-shrink: 0;
}

.surah-card__name {
  display: block;
  font-size: 0.9rem;
  font-weight: 700;
  color: #1F2937;
  line-height: 1.2;
}

.surah-card__arabic {
  display: block;
  font-size: 0.75rem;
  color: #6B7280;
  font-family: var(--font-arabic);
  direction: rtl;
}

.surah-card__right {
  text-align: right;
  flex-shrink: 0;
}

.surah-card__pct {
  font-size: 0.75rem;
  font-weight: 700;
  color: #6B7280;
  margin-bottom: 4px;
}

.mini-progress {
  width: 72px;
}

.mini-progress__bar {
  height: 4px;
  background: #E5E7EB;
  border-radius: 99px;
  display: flex;
  overflow: hidden;
}

.mini-progress__seg {
  height: 100%;
}

.mini-progress__seg--fluent {
  background: #10B981;
}

.mini-progress__seg--doubtful {
  background: #F59E0B;
}

.mini-progress__seg--forgot {
  background: #EF4444;
}

/* ================================================
   SUMMARY GRID
   ================================================ */
.summary-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

.summary-card {
  background: #fff;
  border-radius: 16px;
  padding: 16px;
  display: flex;
  align-items: center;
  gap: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.summary-card__icon {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.summary-card--surahs .summary-card__icon {
  background: #DBEAFE;
  color: #2563EB;
}

.summary-card--verses .summary-card__icon {
  background: #FEF3C7;
  color: #D97706;
}

.summary-card--fluent .summary-card__icon {
  background: #D1FAE5;
  color: #059669;
}

.summary-card--struggling .summary-card__icon {
  background: #FEE2E2;
  color: #DC2626;
}

.summary-card__stat {
  display: flex;
  flex-direction: column;
}

.summary-card__value {
  font-size: 1.25rem;
  font-weight: 800;
  color: #1F2937;
  line-height: 1.1;
}

.summary-card__value--fluent {
  color: #059669;
}

.summary-card__value--struggling {
  color: #DC2626;
}

.summary-card__label {
  font-size: 0.65rem;
  color: #6B7280;
  font-weight: 600;
}

/* ================================================
   DEV INFO
   ================================================ */
.dev-info__card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #fff;
  border-radius: 16px;
  padding: 14px 18px;
  text-decoration: none;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  transition: transform 0.15s;
}

.dev-info__card:active {
  transform: scale(0.98);
}

.dev-info__left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.dev-info__icon {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #F3F4F6;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #6B7280;
  flex-shrink: 0;
}

.dev-info__text strong {
  display: block;
  font-size: 0.85rem;
  color: #1F2937;
  margin-bottom: 1px;
}

.dev-info__text p {
  font-size: 0.75rem;
  color: #6B7280;
}

/* ================================================
   SKELETON
   ================================================ */
.skeleton {
  background: linear-gradient(90deg, #E5E7EB 25%, #F3F4F6 50%, #E5E7EB 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 8px;
}

.stat-skeleton {
  height: 80px;
  flex: 1;
}

@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

/* ================================================
   ANIMATIONS
   ================================================ */
.animate-fade-in {
  animation: fadeInUp 0.5s ease both;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(12px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* ================================================
   SECTION HEADERS
   ================================================ */
.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}

.section-title {
  font-size: 1rem;
  font-weight: 800;
  color: #1F2937;
}

.section-link {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 0.8rem;
  font-weight: 600;
  color: #064E3B;
  text-decoration: none;
}

.section {
  margin-bottom: 20px;
}

/* ================================================
   RESPONSIVE
   ================================================ */
@media (max-width: 374px) {
  .badge-hero-card {
    flex-direction: column;
    text-align: center;
  }

  .badge-hero-card__info {
    align-items: center;
  }

  .today-stats {
    flex-direction: column;
  }

  .summary-grid {
    grid-template-columns: 1fr;
  }
}

@media (min-width: 500px) {
  .summary-grid {
    grid-template-columns: repeat(4, 1fr);
  }
}

/* ================================================
   BADGE GUEST PREVIEW
   ================================================ */
.badge-roadmap--gs .badge-step__dot {
  border-color: #D1D5DB !important;
}

/* ================================================
   BADGE SECTION LINK (hilangkan underline NuxtLink)
   ================================================ */
.section--badge-link {
  text-decoration: none;
  display: block;
  color: inherit;
}

.badge-progress-card-link {
  text-decoration: none;
  display: block;
  color: inherit;
}

/* ================================================
   BADGE DETAIL DRAWER (Slide Up bottom sheet)
   ================================================ */
.badge-drawer-wrapper {
  position: fixed;
  inset: 0;
  z-index: 1000;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
}

.badge-drawer-wrapper .drawer-backdrop {
  position: absolute;
  inset: 0;
  background: rgba(4, 39, 25, 0.55);
  backdrop-filter: blur(10px);
  z-index: 1001;
}

.badge-drawer-wrapper .drawer-panel {
  position: relative;
  width: 100%;
  max-width: 500px;
  margin: 0 auto;
  background: #FFFDF8;
  border-radius: 28px 28px 0 0;
  z-index: 1002;
  box-shadow: 0 -16px 40px rgba(0, 0, 0, 0.22);
  display: flex;
  flex-direction: column;
  max-height: 90vh;
  border-top: 2px solid rgba(212, 175, 55, 0.3);
  overflow: hidden;
}

.badge-drawer-wrapper .drawer-handle-bar {
  display: flex;
  justify-content: center;
  padding: 12px 0 8px;
  cursor: grab;
  touch-action: none;
  user-select: none;
}

.badge-drawer-wrapper .drawer-handle-bar:active {
  cursor: grabbing;
}

.badge-drawer-wrapper .drawer-handle {
  width: 38px;
  height: 5px;
  border-radius: 99px;
  background: #E5E7EB;
}

.badge-drawer-wrapper .drawer-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 4px 20px 14px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.badge-drawer-wrapper .drawer-title-wrap {
  display: flex;
  align-items: center;
  gap: 12px;
}

.badge-drawer-wrapper .drawer-icon {
  font-size: 1.5rem;
}

.badge-drawer-wrapper .drawer-title {
  font-size: 1.15rem;
  font-weight: 800;
  color: #042719;
  margin: 0;
}

.badge-drawer-wrapper .drawer-subtitle {
  font-size: 0.76rem;
  color: #6B7280;
  margin: 2px 0 0;
}

.badge-drawer-wrapper .drawer-close {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background: #F3F4F6;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #4B5563;
  cursor: pointer;
  transition: all 0.2s;
}

.badge-drawer-wrapper .drawer-close:hover {
  background: #E5E7EB;
  color: #1F2937;
  transform: rotate(90deg);
}

.badge-drawer-wrapper .drawer-body {
  padding: 16px 20px 24px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

/* Selector Row */
.badge-selector-row {
  display: flex;
  gap: 14px;
  overflow-x: auto;
  padding: 4px 0 12px;
  scroll-behavior: smooth;
  -webkit-overflow-scrolling: touch;
}

.badge-selector-row::-webkit-scrollbar {
  display: none;
}

.badge-selector-row {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

.badge-selector-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  background: transparent;
  border: none;
  cursor: pointer;
  flex-shrink: 0;
  width: 68px;
  transition: transform 0.2s ease;
}

.badge-selector-item:active {
  transform: scale(0.92);
}

.badge-selector-item.active {
  transform: scale(1.05);
}

.selector-badge-img-wrap {
  position: relative;
  width: 54px;
  height: 54px;
  background: #FFF;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.04);
  border: 2px solid transparent;
  transition: all 0.2s ease;
}

.badge-selector-item.active .selector-badge-img-wrap {
  border-color: #D4AF37;
  box-shadow: 0 8px 18px rgba(212, 175, 55, 0.25);
  background: #FFFDF5;
}

.badge-selector-item.locked .selector-badge-img {
  filter: grayscale(1) opacity(0.5);
}

.selector-badge-img {
  width: 40px;
  height: 40px;
  object-fit: contain;
}

.current-indicator {
  position: absolute;
  bottom: -4px;
  right: -4px;
  background: #10B981;
  color: white;
  border-radius: 50%;
  width: 16px;
  height: 16px;
  font-size: 0.65rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1.5px solid white;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.lock-indicator {
  position: absolute;
  top: -4px;
  right: -4px;
  background: #9CA3AF;
  color: white;
  border-radius: 50%;
  width: 16px;
  height: 16px;
  font-size: 0.62rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1.5px solid white;
}

.selector-badge-lbl {
  font-size: 0.72rem;
  font-weight: 700;
  color: #4B5563;
  transition: color 0.2s;
}

.badge-selector-item.active .selector-badge-lbl {
  color: #042719;
}

/* Detail Card */
.badge-detail-card {
  position: relative;
  background: #FFF;
  border-radius: 24px;
  padding: 24px 20px;
  border: 1.5px solid rgba(0, 0, 0, 0.04);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.02);
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  overflow: hidden;
}

.badge-glow-bg {
  position: absolute;
  top: -40px;
  left: 50%;
  transform: translateX(-50%);
  width: 260px;
  height: 260px;
  pointer-events: none;
  border-radius: 50%;
  z-index: 0;
}

.badge-img-preview-wrap {
  position: relative;
  z-index: 1;
  width: 140px;
  height: 140px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 12px;
}

.detail-badge-img {
  width: 125px;
  height: 125px;
  object-fit: contain;
}

.animate-badge-float {
  animation: floatBadge 4s ease-in-out infinite;
}

@keyframes floatBadge {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-8px); }
}

.badge-info-block {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-bottom: 14px;
}

.badge-juz-tag {
  font-size: 0.72rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 4px 10px;
  border-radius: 99px;
  background: rgba(255, 255, 255, 0.8);
  border: 1px solid;
  margin-bottom: 10px;
  box-shadow: 0 2px 5px rgba(0,0,0,0.03);
}

.badge-name-large {
  font-size: 1.6rem;
  font-weight: 900;
  color: #1F2937;
  margin: 0;
  letter-spacing: -0.01em;
}

.badge-arabic-large {
  font-family: var(--font-arabic);
  font-size: 1.85rem;
  color: #042719;
  margin: 6px 0;
}

.badge-meaning-label {
  font-size: 0.78rem;
  color: #6B7280;
  font-weight: 550;
  margin: 0;
}

.badge-desc-text {
  position: relative;
  z-index: 1;
  font-size: 0.85rem;
  line-height: 1.5;
  color: #4B5563;
  margin: 0 0 22px;
  max-width: 320px;
  font-weight: 500;
}

/* Status Panel */
.badge-status-panel {
  position: relative;
  z-index: 1;
  display: flex;
  width: 100%;
  background: #F9FAF7;
  border-radius: 16px;
  border: 1px solid rgba(0, 0, 0, 0.03);
  overflow: hidden;
}

.status-metric {
  flex: 1;
  padding: 12px 14px;
  display: flex;
  flex-direction: column;
  gap: 4px;
  align-items: center;
}

.status-metric:first-child {
  border-right: 1px solid rgba(0,0,0,0.04);
}

.status-metric-lbl {
  font-size: 0.68rem;
  font-weight: 700;
  color: #9CA3AF;
  text-transform: uppercase;
  letter-spacing: 0.02em;
}

.status-metric-val {
  font-size: 0.88rem;
  font-weight: 800;
  color: #374151;
}

.status-tag {
  font-size: 0.76rem;
  font-weight: 800;
  padding: 3px 12px;
  border-radius: 99px;
  text-transform: uppercase;
}

.status-tag.active {
  background: rgba(16, 185, 129, 0.12);
  color: #059669;
}

.status-tag.unlocked {
  background: rgba(59, 130, 246, 0.1);
  color: #2563EB;
}

.status-tag.locked {
  background: rgba(156, 163, 175, 0.1);
  color: #6B7280;
}

/* Hover effects for main card */
.badge-hero-card {
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.badge-hero-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 12px 30px rgba(4, 37, 25, 0.3) !important;
}

.badge-hero-card:active {
  transform: translateY(-1px);
}

/* Transitions */
.drawer-fade-enter-active,
.drawer-fade-leave-active {
  transition: opacity 0.3s ease;
}

.drawer-fade-enter-from,
.drawer-fade-leave-to {
  opacity: 0;
}

.drawer-fade-enter-active .drawer-panel,
.drawer-fade-leave-active .drawer-panel {
  transition: transform 0.32s cubic-bezier(0.16, 1, 0.3, 1);
}

.drawer-fade-enter-from .drawer-panel,
.drawer-fade-leave-to .drawer-panel {
  transform: translateY(100%);
}

/* Streak and Due Murojaah Styles */
.home-header__right {
  display: flex;
  align-items: center;
  gap: 12px;
}

.streak-mini-pill {
  display: flex;
  align-items: center;
  gap: 4px;
  background: rgba(255, 255, 255, 0.14);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 20px;
  padding: 5px 12px;
  color: white;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  backdrop-filter: blur(8px);
}

.streak-mini-pill:hover {
  background: rgba(255, 255, 255, 0.22);
  transform: translateY(-1px);
}

.streak-mini-pill:active {
  transform: translateY(0);
}

.streak-mini-pill span {
  font-size: 0.95rem;
}

.streak-mini-pill strong {
  font-size: 0.85rem;
  font-weight: 800;
}

.section-badge {
  background: var(--color-primary-50);
  color: var(--color-primary);
  font-size: 0.68rem;
  font-weight: 800;
  padding: 3px 8px;
  border-radius: 99px;
  border: 1.5px solid var(--color-primary-100);
}

.due-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.due-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: white;
  border-radius: 16px;
  padding: 14px 16px;
  border: 1.5px solid rgba(0, 0, 0, 0.04);
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.02);
  cursor: pointer;
  transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

.due-card:hover {
  transform: translateY(-2px);
  border-color: rgba(212, 175, 55, 0.3);
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.05);
}

.due-card:active {
  transform: scale(0.98);
}

.due-card__left {
  display: flex;
  align-items: center;
  gap: 14px;
}

.due-card__number {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: #FFFDF5;
  border: 1.5px solid #FDE68A;
  color: #B45309;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.82rem;
  font-weight: 800;
}

.due-card__info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.due-card__name {
  font-weight: 750;
  color: #1F2937;
  font-size: 0.95rem;
}

.due-card__desc {
  font-size: 0.74rem;
  color: #D97706;
  font-weight: 600;
}

.due-card__right {
  display: flex;
  align-items: center;
  gap: 16px;
}

.due-card__arabic {
  font-family: var(--font-arabic);
  font-size: 1.15rem;
  color: #042719;
}

.due-card__btn {
  background: var(--color-primary);
  color: white;
  border: none;
  border-radius: 10px;
  padding: 8px 14px;
  font-size: 0.8rem;
  font-weight: 800;
  display: flex;
  align-items: center;
  gap: 4px;
  cursor: pointer;
  transition: background 0.2s;
  box-shadow: 0 4px 10px rgba(5, 150, 105, 0.15);
}

.due-card:hover .due-card__btn {
  background: #047857;
}

.empty-due {
  background: white;
  border-radius: 16px;
  border: 1.5px solid rgba(0, 0, 0, 0.03);
  padding: 24px 16px;
  text-align: center;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.01);
  display: flex;
  flex-direction: column;
  align-items: center;
}

.empty-due__icon {
  font-size: 2.2rem;
  margin-bottom: 8px;
}

.empty-due__title {
  font-size: 0.92rem;
  font-weight: 800;
  color: #1F2937;
  margin-bottom: 3px;
}

.empty-due__sub {
  font-size: 0.76rem;
  color: #6B7280;
  font-weight: 550;
  margin-bottom: 2px;
}

.empty-due__actions {
  display: flex;
  gap: 10px;
  width: 100%;
  justify-content: center;
  margin-top: 14px;
}

.empty-due__btn {
  font-size: 0.82rem;
  min-height: 44px;
  padding: 10px 16px;
  flex: 1;
  max-width: 180px;
}

@keyframes pulse-slow {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.05); }
}

.animate-pulse-slow {
  animation: pulse-slow 3s ease-in-out infinite;
}
</style>
