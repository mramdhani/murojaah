<template>
  <div class="page">
    <header class="profile-header">
      <!-- Background arabesque overlay -->
      <div class="profile-header__arabesque"></div>

      <!-- Floating sparkles -->
      <span class="hdr-sparkle hdr-sparkle--1">✦</span>
      <span class="hdr-sparkle hdr-sparkle--2">◈</span>
      <span class="hdr-sparkle hdr-sparkle--3">✧</span>
      <span class="hdr-dot hdr-dot--1"></span>
      <span class="hdr-dot hdr-dot--2"></span>

      <div class="container" style="position: relative; z-index: 2; padding-bottom: 24px;">
        <div class="profile-header__text">
          <h1 class="profile-title">Profil Kakak</h1>
          <p class="profile-subtitle">
            <span class="hdr-diamond">◆</span>
            Kelola akun dan pantau statistik
          </p>
        </div>

        <!-- Profile Card inside Header -->
        <div class="profile-user-card">
          <!-- Loading State -->
          <div v-if="authLoading || !user" class="profile-loading">
            <div class="skeleton-avatar skeleton"></div>
            <div class="skeleton-info">
              <div class="skeleton skeleton-line" style="width: 140px; height: 18px; margin-bottom: 8px;"></div>
              <div class="skeleton skeleton-line" style="width: 180px; height: 12px; margin-bottom: 8px;"></div>
              <div class="skeleton skeleton-line" style="width: 80px; height: 16px;"></div>
            </div>
          </div>

          <!-- Logged In / Guest State -->
          <template v-else>
            <div class="profile-info">
              <div class="profile-avatar">
                <img v-if="user.avatar" :src="user.avatar" alt="Avatar" class="avatar-img" />
                <div v-else class="avatar-initials">
                  {{ user.name.charAt(0).toUpperCase() }}
                </div>
              </div>
              
              <div class="profile-details">
                <h2>{{ user.name }}</h2>
                <p class="profile-email">{{ user.is_guest ? 'Sesi Tamu (Perangkat Ini)' : user.email }}</p>
                <span class="status-badge" :class="user.is_guest ? 'status-badge--unreviewed' : 'status-badge--fluent'">
                  {{ user.is_guest ? 'Tamu' : 'Terhubung Google' }}
                </span>
              </div>
            </div>

            <div class="profile-actions">
              <!-- Login CTA -->
              <div v-if="user.is_guest" class="login-cta">
                <p class="cta-text">
                  Progress hafalan Kakak saat ini disimpan sementara. Hubungkan ke Google agar data <strong>aman selamanya</strong>!
                </p>
                <button class="btn btn-primary btn-block google-btn" @click="handleGoogleLogin" :disabled="authLoading">
                  <svg class="google-icon" viewBox="0 0 24 24" width="20" height="20">
                    <path fill="#EA4335" d="M12 5.04c1.67 0 3.19.57 4.37 1.7l3.26-3.26C17.65 1.58 14.98 1 12 1 7.24 1 3.2 3.73 1.25 7.72l3.87 3C6.03 7.74 8.79 5.04 12 5.04z" />
                    <path fill="#4285F4" d="M23.45 12.27c0-.82-.07-1.6-.2-2.36H12v4.51h6.42c-.28 1.48-1.12 2.73-2.38 3.58l3.7 2.87c2.16-1.99 3.41-4.93 3.41-8.6z" />
                    <path fill="#FBBC05" d="M5.12 14.72c-.24-.72-.37-1.48-.37-2.72s.13-2 .37-2.72l-3.87-3C.47 7.75 0 9.8 0 12s.47 4.25 1.25 5.72l3.87-3z" />
                    <path fill="#34A853" d="M12 23c3.24 0 5.96-1.08 7.95-2.92l-3.7-2.87c-1.08.72-2.47 1.15-4.25 1.15-3.21 0-5.97-2.7-6.97-5.68l-3.87 3C3.2 20.27 7.24 23 12 23z" />
                  </svg>
                  <span>Hubungkan ke Google</span>
                </button>
              </div>

              <!-- Logout Button -->
              <button v-else class="btn btn-ghost btn-block logout-btn" @click="handleLogout">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                  <polyline points="16 17 21 12 16 7" />
                  <line x1="21" y1="12" x2="9" y2="12" />
                </svg>
                Keluar Akun
              </button>
            </div>
          </template>
        </div>
      </div>

      <!-- Elegant curved divider -->
      <div class="profile-header__divider">
        <div class="profile-header__divider-fill"></div>
        <svg class="profile-header__divider-svg" viewBox="0 0 390 28" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <linearGradient id="divGold" x1="0" y1="0" x2="390" y2="0" gradientUnits="userSpaceOnUse">
              <stop offset="0%" stop-color="rgba(170,124,17,0)"/>
              <stop offset="20%" stop-color="#D4AF37"/>
              <stop offset="50%" stop-color="#FFF8D6"/>
              <stop offset="80%" stop-color="#D4AF37"/>
              <stop offset="100%" stop-color="rgba(170,124,17,0)"/>
            </linearGradient>
          </defs>
          <path d="M0,28 Q195,0 390,28 L390,28 L0,28 Z" fill="#FAFAF5"/>
          <path d="M0,28 Q195,0 390,28" fill="none" stroke="url(#divGold)" stroke-width="1.5"/>
        </svg>
        <div class="hdr-diamond-ornament">◆</div>
      </div>
    </header>

    <div class="page-content container animate-slide-up">
      <!-- Stats Section -->
      <div class="stats-section">
        <div class="section-header">
          <h2 class="section-title">Statistik Hafalan</h2>
        </div>
        
        <div v-if="statsLoading" class="summary-grid">
          <div v-for="i in 4" :key="i" class="skeleton stat-card" style="height: 90px; border-radius: 16px;"></div>
        </div>

        <div v-else-if="stats" class="summary-grid">
          <!-- Surahs Reviewed -->
          <div class="summary-card summary-card--surahs">
            <div class="summary-card__icon">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/></svg>
            </div>
            <div class="summary-card__stat">
              <span class="summary-card__value">{{ stats.surahs_reviewed }}</span>
              <span class="summary-card__label">Total Surat</span>
            </div>
          </div>
          <!-- Total Reviewed -->
          <div class="summary-card summary-card--verses">
            <div class="summary-card__icon">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
            </div>
            <div class="summary-card__stat">
              <span class="summary-card__value">{{ stats.total_reviewed }}</span>
              <span class="summary-card__label">Total Ayat</span>
            </div>
          </div>

          <!-- Fluent -->
          <div class="summary-card summary-card--fluent">
            <div class="summary-card__icon">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <div class="summary-card__stat">
              <span class="summary-card__value summary-card__value--fluent">{{ stats.fluent }}</span>
              <span class="summary-card__label">Lancar</span>
            </div>
          </div>

          <!-- Forgot / Doubtful -->
          <div class="summary-card summary-card--struggling">
            <div class="summary-card__icon">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            </div>
            <div class="summary-card__stat">
              <span class="summary-card__value summary-card__value--struggling">{{ stats.forgot + stats.doubtful }}</span>
              <span class="summary-card__label">Belum Lancar</span>
            </div>
          </div>
        </div>
      </div>
      
      <!-- App Version Info -->
      <div class="app-info">
        <p>Murojaah App v1.2.0</p>
        <p class="app-info__desc">Didukung dengan Cloud Sync & Mode Remote Control Haptic</p>
      </div>
      <div style="height: 32px"></div>
    </div>

    <!-- Custom Logout Confirmation Modal -->
    <div v-if="isLogoutModalOpen" class="modal-overlay animate-fade-in" @click="closeLogoutModal">
      <div class="modal-dialog animate-scale-in" @click.stop>
        <div class="modal-header">
          <div class="modal-icon-logout">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
              <polyline points="16 17 21 12 16 7" />
              <line x1="21" y1="12" x2="9" y2="12" />
            </svg>
          </div>
          <h3 class="modal-title">Keluar Akun</h3>
        </div>
        <div class="modal-body">
          <p>Apakah Kakak yakin ingin keluar dari akun Google? Progress murojaah Kakak tidak akan hilang, namun Kakak perlu login kembali untuk sinkronisasi cloud.</p>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="closeLogoutModal">Batal</button>
          <button class="btn btn-danger" @click="executeLogout">Ya, Keluar</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const { user, loginWithGoogle, logout, loading: authLoading } = useAuth()
const { apiFetch } = useApi()
const { currentThemeId, setTheme, themesList } = useTheme()

const showToast = inject<(message: string, type?: string) => void>('showToast')

const stats = ref<any>(null)
const statsLoading = ref(false)

const handleThemeChange = (themeId: any) => {
  if (user.value?.is_guest) {
    if (showToast) {
      showToast('Hubungkan ke Google untuk membuka fitur ganti tema!', 'info')
    }
    return
  }
  setTheme(themeId)
  if (showToast) {
    showToast('Tema berhasil diubah!', 'fluent')
  }
}

const triggerHaptic = () => {
  if (typeof navigator !== 'undefined' && navigator.vibrate) {
    navigator.vibrate(30)
  }
}

const handleGoogleLogin = () => {
  triggerHaptic()
  loginWithGoogle()
}

const isLogoutModalOpen = ref(false)

const handleLogout = () => {
  triggerHaptic()
  isLogoutModalOpen.value = true
}

const closeLogoutModal = () => {
  isLogoutModalOpen.value = false
}

const executeLogout = () => {
  logout()
  closeLogoutModal()
}

const loadStats = async () => {
  statsLoading.value = true
  try {
    const res = await apiFetch<any>('/dashboard')
    stats.value = res.data.overall
  } catch (e) {
    console.error('Failed to load profile stats:', e)
  } finally {
    statsLoading.value = false
  }
}

onMounted(() => {
  loadStats()
})

// Reload stats when user session changes
watch(user, () => {
  loadStats()
})

useHead({
  title: 'Profil Murojaah',
})
</script>

<style scoped>
/* ================================================
   HEADER (Mirrors homepage & progress)
   ================================================ */
.profile-header {
  padding: calc(var(--safe-top) + 36px) 0 0 !important;
  background: linear-gradient(160deg, #052e1c 0%, #064E3B 45%, #0a6349 100%) !important;
  color: white;
  position: relative;
  overflow: hidden;
}

/* Subtle arabesque/geometric overlay */
.profile-header__arabesque {
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

.profile-header .container {
  padding: 0 20px;
}

.profile-title {
  font-size: 1.8rem;
  font-weight: 800;
  letter-spacing: -0.03em;
  line-height: 1.1;
  margin-bottom: 8px;
  color: #fff;
}

.profile-subtitle {
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

/* Glassmorphism Profile Card */
.profile-user-card {
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(12px);
  border-radius: 16px;
  padding: 20px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.profile-loading {
  display: flex;
  align-items: center;
  gap: 18px;
}

.skeleton-avatar {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  flex-shrink: 0;
  background: rgba(255, 255, 255, 0.2);
}

.skeleton-info {
  flex: 1;
}
.skeleton-line {
  background: rgba(255, 255, 255, 0.2);
}

.profile-info {
  display: flex;
  align-items: center;
  gap: 18px;
}

.profile-avatar {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  overflow: hidden;
  flex-shrink: 0;
  border: 2.5px solid rgba(212, 175, 55, 0.6);
  box-shadow: 0 0 0 4px rgba(212, 175, 55, 0.12), 0 4px 12px rgba(0,0,0,0.2);
  background: rgba(255, 255, 255, 0.08);
}

.avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-initials {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  font-weight: 800;
  color: white;
  background: linear-gradient(135deg, rgba(212, 175, 55, 0.4) 0%, rgba(255, 255, 255, 0.15) 100%);
}

.profile-details {
  flex: 1;
  min-width: 0;
}

.profile-details h2 {
  font-size: 1.25rem;
  font-weight: 800;
  color: #fff;
  margin-bottom: 2px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  letter-spacing: -0.01em;
}

.profile-email {
  font-size: 0.85rem;
  color: rgba(255,255,255,0.7);
  margin-bottom: 8px;
  word-break: break-all;
}

.status-badge {
  font-size: 0.65rem;
  font-weight: 800;
  padding: 4px 10px;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  border-radius: 99px;
  display: inline-block;
  background: rgba(0,0,0,0.2);
}

.status-badge--fluent {
  color: #34D399; /* emerald-400 */
  border: 1px solid rgba(52, 211, 153, 0.3);
}

.status-badge--unreviewed {
  color: #FCD34D; /* amber-300 */
  border: 1px solid rgba(252, 211, 77, 0.3);
}

/* Actions in profile card */
.profile-actions {
  display: flex;
  flex-direction: column;
}

.login-cta {
  background: rgba(0,0,0,0.15);
  padding: 16px;
  border-radius: 12px;
  border: 1px dashed rgba(255,255,255,0.2);
}

.cta-text {
  font-size: 0.8125rem;
  color: rgba(255,255,255,0.8);
  line-height: 1.5;
  margin-bottom: 14px;
}

.cta-text strong {
  color: #D4AF37;
}

.google-btn {
  background: white !important;
  color: #111827 !important;
  border: none !important;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
  font-weight: 700 !important;
  font-size: 0.9375rem !important;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 14px !important;
}

.google-btn:active {
  transform: translateY(1px);
  background: #fdfdfd !important;
}

.google-icon {
  margin-right: 10px;
}

.logout-btn {
  color: #FCA5A5 !important;
  background: rgba(239, 68, 68, 0.15) !important;
  border: 1px solid rgba(239, 68, 68, 0.3) !important;
  padding: 14px !important;
  font-weight: 700 !important;
}

.logout-btn:active {
  background: rgba(239, 68, 68, 0.25) !important;
}

/* Elegant curved divider */
.profile-header__divider {
  position: relative;
  z-index: 1;
  line-height: 0;
  margin-top: 4px;
}

.profile-header__divider-fill {
  height: 0;
}

.profile-header__divider-svg {
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
   CONTENT
   ================================================ */
.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  padding-top: 10px;
}

.section-title {
  font-size: 0.8rem;
  font-weight: 800;
  color: var(--color-text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.07em;
}

/* SUMMARY GRID (Matches Homepage) */
.summary-grid { 
  display: grid; 
  grid-template-columns: 1fr 1fr; 
  gap: 12px; 
}

.summary-card {
  background: var(--color-bg-card);
  border-radius: 16px;
  padding: 18px 16px;
  display: flex;
  align-items: center;
  gap: 14px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.03);
  border: 1px solid rgba(0,0,0,0.04);
}

.summary-card__icon {
  width: 48px;
  height: 48px;
  border-radius: 14px;
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
.summary-card__value { font-size: 1.5rem; font-weight: 900; line-height: 1; color: var(--color-text); letter-spacing: -0.02em; }
.summary-card__value--fluent { color: var(--color-fluent); }
.summary-card__value--struggling { color: var(--color-forgot); }
.summary-card__label { font-size: 0.7rem; font-weight: 700; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: 0.04em; margin-top: 4px; }

/* App Info */
.app-info {
  text-align: center;
  margin-top: 32px;
  padding: 20px 0;
  border-top: 1px dashed rgba(0,0,0,0.06);
}

.app-info p {
  font-size: 0.8rem;
  font-weight: 800;
  color: var(--color-text-secondary);
}

.app-info__desc {
  font-weight: 600 !important;
  font-size: 0.72rem !important;
  margin-top: 4px;
  color: var(--color-text-muted) !important;
}

/* Modal Styles (Matches progress modal layout) */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(4px);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.modal-dialog {
  background: var(--color-bg);
  border-radius: 20px;
  width: 100%;
  max-width: 400px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  overflow: hidden;
}

.modal-header {
  padding: 24px 24px 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.modal-icon-logout {
  width: 52px;
  height: 52px;
  border-radius: 50%;
  background: rgba(239, 68, 68, 0.1);
  color: #DC2626;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16px;
}

.modal-title {
  font-size: 1.25rem;
  font-weight: 800;
  color: var(--color-text);
  margin: 0;
}

.modal-body {
  padding: 16px 24px;
  text-align: center;
  color: var(--color-text-secondary);
  font-size: 0.9375rem;
  line-height: 1.5;
}

.modal-footer {
  padding: 16px 24px 24px;
  display: flex;
  gap: 12px;
}

.modal-footer .btn {
  flex: 1;
}

.btn-secondary {
  background: var(--color-surface);
  color: var(--color-text);
  border: 1.5px solid var(--border-color);
  border-radius: 99px;
  padding: 12px;
  font-weight: 700;
  cursor: pointer;
}

.btn-danger {
  background: #DC2626;
  color: white;
  border: none;
  border-radius: 99px;
  padding: 12px;
  font-weight: 700;
  cursor: pointer;
}

.btn-danger:hover {
  background: #B91C1C;
}
</style>
