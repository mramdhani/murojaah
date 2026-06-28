<template>
  <div class="page">
    <header class="page-header">
      <div class="container page-header__content">
        <h1>Profil Murojaah</h1>
        <p>Kelola akun dan pantau statistik hafalan Kakak</p>
      </div>
    </header>

    <div class="page-content container animate-slide-up">
      <!-- Profile Card -->
      <div class="profile-card card">
        <!-- Sesi Loading / Menghubungkan -->
        <div v-if="authLoading || !user" class="profile-loading">
          <div class="skeleton-avatar skeleton"></div>
          <div class="skeleton-info">
            <div class="skeleton skeleton-line" style="width: 140px; height: 18px; margin-bottom: 8px;"></div>
            <div class="skeleton skeleton-line" style="width: 180px; height: 12px; margin-bottom: 8px;"></div>
            <div class="skeleton skeleton-line" style="width: 80px; height: 16px;"></div>
          </div>
        </div>

        <!-- Sesi Terkoneksi (Guest atau User Google) -->
        <template v-else>
          <div class="profile-info">
            <div class="profile-avatar">
              <img v-if="user.avatar" :src="user.avatar" alt="Avatar" class="avatar-img" />
              <div v-else class="avatar-placeholder">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v-2"/>
                  <circle cx="12" cy="7" r="4"/>
                </svg>
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
            <!-- Login with Google (If Guest) -->
            <div v-if="user.is_guest" class="login-cta">
              <p class="cta-text">
                Progress hafalan Kakak saat ini disimpan di database sebagai Tamu. Hubungkan ke Google agar data Kakak <strong>aman selamanya</strong> dan bisa dibuka dari HP atau laptop lain!
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

            <!-- Logout Button (If Logged In) -->
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

      <!-- Stats Section -->
      <div class="stats-section animate-fade-in" style="animation-delay: 0.15s">
        <h3>Statistik Hafalan Kakak</h3>
        
        <div v-if="statsLoading" class="stats-grid">
          <div v-for="i in 4" :key="i" class="skeleton stat-card" style="height: 90px; border: none"></div>
        </div>

        <div v-else-if="stats" class="stats-grid">
          <!-- Total Reviewed -->
          <div class="stat-card card">
            <span class="stat-num">{{ stats.total_reviewed }}</span>
            <span class="stat-label">Total Ayat Murojaah</span>
          </div>

          <!-- Surahs Reviewed -->
          <div class="stat-card card">
            <span class="stat-num">{{ stats.surahs_reviewed }}</span>
            <span class="stat-label">Surat Pernah Dilihat</span>
          </div>

          <!-- Fluent -->
          <div class="stat-card card stat-card--fluent">
            <span class="stat-num text-fluent">{{ stats.fluent }}</span>
            <span class="stat-label">Ayat Lancar (Hijau)</span>
          </div>

          <!-- Forgot / Doubtful -->
          <div class="stat-card card stat-card--forgot">
            <span class="stat-num text-forgot">{{ stats.forgot + stats.doubtful }}</span>
            <span class="stat-label">Belum Lancar (Merah/Kuning)</span>
          </div>
        </div>
      </div>
      
      <!-- App Version Info -->
      <div class="app-info">
        <p>Murojaah App v1.2.0</p>
        <p class="app-info__desc">Didukung dengan Cloud Sync & Mode Remote Control Haptic</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const { user, loginWithGoogle, logout, loading: authLoading } = useAuth()
const { apiFetch } = useApi()

const stats = ref<any>(null)
const statsLoading = ref(false)

const triggerHaptic = () => {
  if (typeof navigator !== 'undefined' && navigator.vibrate) {
    navigator.vibrate(30)
  }
}

const handleGoogleLogin = () => {
  triggerHaptic()
  loginWithGoogle()
}

const handleLogout = () => {
  triggerHaptic()
  if (confirm('Apakah Kakak yakin ingin keluar dari akun Google?')) {
    logout()
  }
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
.profile-card {
  padding: 24px;
  margin-bottom: 24px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.profile-info {
  display: flex;
  align-items: center;
  gap: 18px;
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
}

.skeleton-info {
  flex: 1;
}

.profile-avatar {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  overflow: hidden;
  flex-shrink: 0;
  border: 2px solid var(--color-primary-light);
  box-shadow: var(--shadow-sm);
}

.avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-placeholder {
  width: 100%;
  height: 100%;
  background: var(--color-primary-50);
  color: var(--color-primary);
  display: flex;
  align-items: center;
  justify-content: center;
}

.profile-details {
  flex: 1;
  min-width: 0;
}

.profile-details h2 {
  font-size: 1.18rem;
  font-weight: 700;
  color: var(--color-text);
  margin-bottom: 3px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.profile-email {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
  margin-bottom: 6px;
  word-break: break-all;
}

.status-badge {
  font-size: 0.6875rem;
  padding: 2px 8px;
}

.login-cta {
  background: var(--color-bg-subtle);
  padding: 16px;
  border-radius: var(--radius-md);
  border: 1px dashed rgba(5, 150, 105, 0.15);
}

.cta-text {
  font-size: 0.8125rem;
  color: var(--color-text-secondary);
  line-height: 1.5;
  margin-bottom: 14px;
}

.cta-text strong {
  color: var(--color-primary-dark);
}

.google-btn {
  background: white !important;
  color: var(--color-text) !important;
  border: 1.5px solid rgba(0, 0, 0, 0.08) !important;
  box-shadow: var(--shadow-sm) !important;
  font-weight: 600 !important;
}

.google-btn:active {
  transform: translateY(1px);
  background: #fdfdfd !important;
}

.google-icon {
  margin-right: 8px;
}

.logout-btn {
  color: #DC2626 !important;
  background: #FEF2F2 !important;
  border: 1px solid #FEE2E2 !important;
}

.logout-btn:active {
  background: #FEE2E2 !important;
}

.stats-section h3 {
  font-size: 1rem;
  font-weight: 700;
  color: var(--color-text);
  margin-bottom: 12px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
  margin-bottom: 24px;
}

.stat-card {
  padding: 16px;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  border: 1.5px solid rgba(0, 0, 0, 0.02);
}

.stat-num {
  font-size: 1.375rem;
  font-weight: 800;
  color: var(--color-text);
  margin-bottom: 4px;
}

.stat-label {
  font-size: 0.75rem;
  color: var(--color-text-secondary);
}

.stat-card--fluent {
  background: var(--color-fluent-bg);
  border-color: var(--color-fluent-border);
}

.text-fluent {
  color: var(--color-fluent) !important;
}

.stat-card--forgot {
  background: var(--color-forgot-bg);
  border-color: var(--color-forgot-border);
}

.text-forgot {
  color: var(--color-forgot) !important;
}

.app-info {
  text-align: center;
  margin-top: 12px;
  padding: 16px 0;
}

.app-info p {
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--color-text-muted);
}

.app-info__desc {
  font-weight: 500 !important;
  font-size: 0.6875rem !important;
  margin-top: 2px;
}
</style>
