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
                <img v-if="user.avatar" :src="user.avatar" alt="Avatar" class="avatar-img" referrerpolicy="no-referrer" />
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

              <!-- Logout Button moved to bottom -->
              <div v-else style="display: none;"></div>
            </div>
          </template>
        </div>
      </div>

  <!-- Smooth premium wave -->
  <div class="profile-header__divider" aria-hidden="true">
    <svg
      class="profile-header__divider-svg"
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

      <!-- Konsistensi Murojaah Section (Streak & Heatmap) -->
      <div class="consistency-section" v-if="isLoggedIn && !statsLoading">
        <div class="section-header">
          <h2 class="section-title">Konsistensi Murojaah</h2>
        </div>

        <div class="consistency-card">
          <!-- Streak Sub-card -->
          <div class="streak-hero">
            <div class="streak-hero__icon">🔥</div>
            <div class="streak-hero__detail">
              <span class="streak-hero__value">{{ streak }} Hari</span>
              <span class="streak-hero__label">Murojaah Berturut-turut</span>
            </div>
          </div>

          <!-- Heatmap Sub-card -->
          <div class="heatmap-container">
            <div class="heatmap-header">
              <span class="heatmap-title">Aktivitas 22 Minggu Terakhir</span>
              <div class="heatmap-legend">
                <span>Kurang</span>
                <div class="legend-box legend-box--0"></div>
                <div class="legend-box legend-box--1"></div>
                <div class="legend-box legend-box--2"></div>
                <div class="legend-box legend-box--3"></div>
                <span>Banyak</span>
              </div>
            </div>
            
            <div class="heatmap-grid-wrap">
              <div class="heatmap-y-labels">
                <span style="grid-row: 1">S</span>
                <span style="grid-row: 3">R</span>
                <span style="grid-row: 5">J</span>
              </div>
               <div class="heatmap-grid">
                <div
                  v-for="(day, index) in heatmapDays"
                  :key="day.dateString"
                  class="heatmap-day"
                  :class="[
                    'heatmap-day--level-' + day.level,
                    { 'heatmap-day--future': day.isFuture }
                  ]"
                  :title="day.isFuture ? 'Mendatang' : `${day.dateString}: ${day.count} ayat murojaah`"
                ></div>
              </div>
            </div>
            <div class="heatmap-months">
              <span>22 Minggu Lalu</span>
              <span>Hari Ini</span>
            </div>
          </div>
        </div>
      </div>
      
      <!-- App Settings Section -->
      <div v-if="user && !user.is_guest" class="settings-section">
        <div class="section-header">
          <h2 class="section-title">Pengaturan Murojaah</h2>
        </div>
        <div class="settings-card">
          <!-- Toggle 1: Reveal Mode (Tebak Hafalan vs Tampilkan Ayat) -->
          <div class="settings-item">
            <div class="settings-item__info">
              <span class="settings-item__title">Tampilkan Ayat</span>
              <span class="settings-item__desc">Otomatis tampilkan teks ayat saat berpindah.</span>
            </div>
            <div class="settings-item__action">
              <label class="switch">
                <input type="checkbox" v-model="revealMode" true-value="revealed" false-value="hidden" @change="handleRevealModeChange" />
                <span class="slider round"></span>
              </label>
            </div>
          </div>

          <!-- Toggle 2: Autoplay Murottal (Otomatis Play) -->
          <div class="settings-item">
            <div class="settings-item__info">
              <span class="settings-item__title">Putar Otomatis</span>
              <span class="settings-item__desc">Putar audio Qori tiap berpindah ayat.</span>
            </div>
            <div class="settings-item__action">
              <label class="switch">
                <input type="checkbox" v-model="autoplayAudio" @change="handleAutoplayChange" />
                <span class="slider round"></span>
              </label>
            </div>
          </div>

          <!-- Setting 3: Default Qari -->
          <div class="settings-item">
            <div class="settings-item__info">
              <span class="settings-item__title">Pilihan Qori</span>
              <span class="settings-item__desc">Pilih suara Qori default untuk murottal.</span>
            </div>
            <div class="settings-item__action">
              <select v-model="selectedQari" class="settings-select" @change="handleQariChange">
                <option v-for="qari in qariList" :key="qari.id" :value="qari.id">
                  {{ qari.name }}
                </option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Infaq Section -->
      <div class="infaq-section">
        <div class="infaq-card">
          <div class="infaq-card__bg"></div>
          <div class="infaq-card__content">
            <div class="infaq-card__header">
              <div class="infaq-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                </svg>
              </div>
              <div>
                <h3 class="infaq-title">Dukung Aplikasi</h3>
                <p class="infaq-desc">Bantu patungan biaya server agar terus gratis & tanpa iklan.</p>
              </div>
            </div>
            <button class="btn infaq-btn" @click="handleInfaq">
              <span>Salurkan Infaq</span>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                <line x1="5" y1="12" x2="19" y2="12"></line>
                <polyline points="12 5 19 12 12 19"></polyline>
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Logout Button (Moved to bottom) -->
      <div v-if="user && !user.is_guest" style="margin-top: 24px;">
        <button class="btn btn-danger btn-block" @click="handleLogout" style="padding: 14px; font-size: 0.95rem; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 8px; border-radius: 12px;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
            <polyline points="16 17 21 12 16 7" />
            <line x1="21" y1="12" x2="9" y2="12" />
          </svg>
          Keluar Akun
        </button>
      </div>
      
      <!-- App Info -->
      <div class="app-info">
        <p>Murojaah App v1.2.0</p>
        <p class="app-info__desc">Didukung dengan Cloud Sync & Mode Remote Control Haptic</p>
      </div>
      <div style="height: 32px"></div>
    </div>

    <!-- Infaq Modal -->
    <div v-if="showInfaqModal" class="infaq-modal-overlay" @click="showInfaqModal = false">
      <div class="infaq-modal-content animate-slide-up" @click.stop>
        <div class="infaq-modal-header">
          <h3>Penyaluran Infaq</h3>
          <button class="infaq-modal-close" @click="showInfaqModal = false">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
          </button>
        </div>
        <div class="infaq-modal-body">
          <p class="infaq-modal-desc">Semoga infaq yang Anda berikan menjadi amal jariyah dan membantu kelangsungan aplikasi ini. Aamiin.</p>
          
          <div class="infaq-bank-card">
            <div class="infaq-bank-header">
              <span class="infaq-bank-name">Bank Syariah Indonesia (BSI)</span>
              <span class="infaq-bank-code">Kode: 451</span>
            </div>
            <div class="infaq-bank-body">
              <div class="infaq-bank-details">
                <span class="infaq-bank-acc">7265658738</span>
                <span class="infaq-bank-owner">a.n MUHAMAD RAMDANI</span>
              </div>
              <button class="infaq-btn-copy" @click="copyToClipboard('7265658738')">
                Salin
              </button>
            </div>
          </div>
          
          <!-- QRIS Card -->
          <div class="infaq-qris-card">
            <div class="infaq-qris-title">Atau Scan QRIS</div>
            <div class="infaq-qris-img-wrap">
              <img src="/images/qris.jpeg" alt="QRIS Murojaah" class="infaq-qris-img" />
            </div>
            <div class="infaq-qris-tip">
              Gak bisa scan? Screenshot layar ini lalu unggah ke GoPay/OVO/Dana/LinkAja/m-banking Anda
            </div>
          </div>
        </div>
      </div>
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
const { user, isLoggedIn, loginWithGoogle, logout, loading: authLoading } = useAuth()
const { apiFetch } = useApi()
const { currentThemeId, setTheme, themesList } = useTheme()

const revealMode = useCookie<string>('reveal_mode', {
  default: () => 'hidden',
  maxAge: 60 * 60 * 24 * 365,
  path: '/'
})

const autoplayAudio = useCookie<boolean>('autoplay_audio', {
  default: () => false,
  maxAge: 60 * 60 * 24 * 365,
  path: '/'
})

const selectedQari = useCookie<string>('selected_qari', {
  default: () => 'Maher_AlMuaiqly_64kbps',
  maxAge: 60 * 60 * 24 * 365,
  path: '/'
})

const showInfaqModal = ref(false)
const streak = ref(0)
const heatmap = ref<Record<string, number>>({})

const heatmapDays = ref<Array<{ dateString: string; count: number; level: number; isFuture: boolean }>>([])

function generateHeatmapDays(heatmapData: Record<string, number>) {
  const days: Array<{ dateString: string; count: number; level: number; isFuture: boolean }> = []
  const today = new Date()
  
  // Cari hari ke-x dari minggu ini (0: Minggu, 1: Senin, ..., 6: Sabtu)
  const currentDay = today.getDay()
  // Hitung selisih hari ke hari Senin minggu ini
  const diffToMonday = currentDay === 0 ? -6 : 1 - currentDay
  
  const mondayThisWeek = new Date(today)
  mondayThisWeek.setDate(today.getDate() + diffToMonday)
  
  // Tanggal mulai adalah Senin 21 minggu yang lalu (agar total pas 22 minggu)
  const startDate = new Date(mondayThisWeek)
  startDate.setDate(mondayThisWeek.getDate() - 21 * 7)
  
  // Generate 154 hari (22 minggu * 7 hari)
  for (let i = 0; i < 154; i++) {
    const d = new Date(startDate)
    d.setDate(startDate.getDate() + i)
    
    // Format YYYY-MM-DD local
    const year = d.getFullYear()
    const month = String(d.getMonth() + 1).padStart(2, '0')
    const date = String(d.getDate()).padStart(2, '0')
    const dateString = `${year}-${month}-${date}`
    
    const count = heatmapData[dateString] || 0
    if (count > 0) {
      console.log('Heatmap match:', dateString, 'Count:', count)
    }
    if (dateString === '2026-06-29') {
      console.log('Monday, June 29 details:', JSON.stringify({ index: i, dateString, count, level: count > 15 ? 3 : (count > 5 ? 2 : (count > 0 ? 1 : 0)), isFuture: false }))
    }
    
    // Bandingkan tanggal saja tanpa jam untuk mendeteksi hari mendatang
    const dZero = new Date(d.getFullYear(), d.getMonth(), d.getDate()).getTime()
    const todayZero = new Date(today.getFullYear(), today.getMonth(), today.getDate()).getTime()
    const isFuture = dZero > todayZero
    
    let level = 0
    if (!isFuture) {
      if (count > 0 && count <= 5) {
        level = 1
      } else if (count > 5 && count <= 15) {
        level = 2
      } else if (count > 15) {
        level = 3
      }
    }
    
    days.push({
      dateString,
      count,
      level,
      isFuture
    })
  }
  return days
}

// Generate initial days (with empty heatmap)
heatmapDays.value = generateHeatmapDays({})

// Watch heatmap changes and regenerate days
watch(heatmap, (newHeatmap) => {
  console.log('Heatmap watch triggered:', JSON.stringify(newHeatmap))
  heatmapDays.value = generateHeatmapDays(newHeatmap || {})
}, { deep: true, immediate: false })

const qariList = [
  { id: 'Maher_AlMuaiqly_64kbps', name: 'Maher Al-Muaiqly' },
  { id: 'Alafasy_64kbps', name: 'Mishary Alafasy' },
  { id: 'Ghamadi_40kbps', name: 'Saad Al-Ghamdi' },
  { id: 'Husary_64kbps', name: 'Mahmoud Al-Husary' }
]

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

const handleRevealModeChange = () => {
  triggerHaptic()
  if (showToast) {
    showToast('Pengaturan Metode Belajar disimpan!', 'fluent')
  }
}

const handleAutoplayChange = () => {
  triggerHaptic()
  if (showToast) {
    showToast('Pengaturan Putar Otomatis disimpan!', 'fluent')
  }
}

const handleQariChange = () => {
  triggerHaptic()
  if (showToast) {
    showToast('Pilihan Qori berhasil disimpan!', 'fluent')
  }
}

const handleInfaq = () => {
  triggerHaptic()
  showInfaqModal.value = true
}

const copyToClipboard = (text: string) => {
  triggerHaptic()
  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(text).then(() => {
      if (showToast) showToast('Nomor rekening berhasil disalin!', 'fluent')
    }).catch(err => {
      console.error('Failed to copy', err)
      if (showToast) showToast('Gagal menyalin', 'forgot')
    })
  } else {
    // Fallback if clipboard API not available
    const textArea = document.createElement("textarea")
    textArea.value = text
    document.body.appendChild(textArea)
    textArea.select()
    try {
      document.execCommand('copy')
      if (showToast) showToast('Nomor rekening berhasil disalin!', 'fluent')
    } catch (err) {
      if (showToast) showToast('Gagal menyalin', 'forgot')
    }
    document.body.removeChild(textArea)
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
    streak.value = res.data.streak || 0
    heatmap.value = res.data.heatmap || {}
    console.log('Heatmap Loaded in Vue:', JSON.stringify(heatmap.value))
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
  border: none !important;
  border-bottom: none !important;
  box-shadow: none !important;
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
  z-index: 2;
  line-height: 0;
  margin-top: -8px;
  transform: translateY(1px);
  border: none !important;
  border-bottom: none !important;
}

.profile-header__divider-fill {
  height: 0;
}

.profile-header__divider-svg {
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

/* ================================================
   APP SETTINGS SECTION
   ================================================ */
.settings-section {
  margin-top: 24px;
}

.settings-card {
  background: white;
  border-radius: var(--radius-lg);
  border: 1.5px solid rgba(0, 0, 0, 0.04);
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.02);
  padding: 8px 16px;
}

.settings-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 0;
  border-bottom: 1.5px solid #F3F4F6;
  gap: 16px;
}

.settings-item:last-child {
  border-bottom: none;
}

.settings-item__info {
  display: flex;
  flex-direction: column;
  gap: 4px;
  flex: 1;
}

.settings-item__title {
  font-weight: 750;
  font-size: 0.9rem;
  color: #1F2937;
}

.settings-item__desc {
  font-size: 0.76rem;
  color: #6B7280;
  line-height: 1.4;
  font-weight: 550;
}

.settings-item__action {
  flex-shrink: 0;
}

.settings-select {
  background: #F3F4F6;
  border: 1px solid #E5E7EB;
  border-radius: var(--radius-md);
  padding: 8px 12px;
  font-size: 0.82rem;
  font-weight: 700;
  color: #374151;
  cursor: pointer;
  outline: none;
  transition: border-color 0.2s;
}

.settings-select:focus {
  border-color: var(--color-primary-light);
}

/* iOS-style toggle switch */
.switch {
  position: relative;
  display: inline-block;
  width: 44px;
  height: 24px;
}

.switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #D1D5DB;
  transition: .25s cubic-bezier(0.4, 0, 0.2, 1);
  border-radius: 34px;
}

.slider:before {
  position: absolute;
  content: "";
  height: 18px;
  width: 18px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  transition: .25s cubic-bezier(0.4, 0, 0.2, 1);
  border-radius: 50%;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

input:checked + .slider {
  background-color: #10B981;
}

input:checked + .slider:before {
  transform: translateX(20px);
}

/* ================================================
   INFAQ SECTION
   ================================================ */
.infaq-section {
  margin-top: 24px;
}

.infaq-card {
  position: relative;
  background: linear-gradient(135deg, #10B981 0%, #059669 100%);
  border-radius: var(--radius-lg);
  padding: 16px;
  overflow: hidden;
  box-shadow: 0 8px 20px rgba(16, 185, 129, 0.25);
}

.infaq-card__bg {
  position: absolute;
  top: -50px;
  right: -50px;
  width: 150px;
  height: 150px;
  background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0) 70%);
  border-radius: 50%;
  pointer-events: none;
}

.infaq-card__content {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.infaq-card__header {
  display: flex;
  align-items: center;
  gap: 12px;
}

.infaq-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  flex-shrink: 0;
}

.infaq-title {
  color: white;
  font-size: 1.05rem;
  font-weight: 800;
  margin-bottom: 2px;
}

.infaq-desc {
  color: rgba(255, 255, 255, 0.9);
  font-size: 0.75rem;
  line-height: 1.3;
  font-weight: 500;
}

.infaq-btn {
  background: white;
  color: #059669;
  border: none;
  border-radius: 10px;
  padding: 10px;
  font-weight: 800;
  font-size: 0.85rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  cursor: pointer;
  transition: transform 0.2s;
  width: 100%;
}

.infaq-btn:active {
  transform: scale(0.96);
}

/* ================================================
   INFAQ MODAL
   ================================================ */
.infaq-modal-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0,0,0,0.4);
  z-index: 1000;
  display: flex;
  align-items: flex-end;
  justify-content: center;
  backdrop-filter: blur(4px);
}

.infaq-modal-content {
  background: white;
  width: 100%;
  max-width: 500px;
  border-radius: 20px 20px 0 0;
  padding: 24px;
  box-shadow: 0 -4px 20px rgba(0,0,0,0.1);
  padding-bottom: env(safe-area-inset-bottom, 24px);
}

.infaq-modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.infaq-modal-header h3 {
  font-size: 1.15rem;
  font-weight: 800;
  color: #111827;
}

.infaq-modal-close {
  background: #F3F4F6;
  border: none;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #6B7280;
  cursor: pointer;
}

.infaq-modal-desc {
  font-size: 0.9rem;
  color: #4B5563;
  line-height: 1.5;
  margin-bottom: 24px;
}

.infaq-bank-card {
  background: #F8FAFC;
  border: 1px solid #E2E8F0;
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 16px;
}

.infaq-bank-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
  font-size: 0.85rem;
  color: #64748B;
  font-weight: 600;
}

.infaq-bank-body {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.infaq-bank-details {
  display: flex;
  flex-direction: column;
}

.infaq-bank-acc {
  font-size: 1.25rem;
  font-weight: 800;
  color: #0F172A;
  letter-spacing: 1px;
}

.infaq-bank-owner {
  font-size: 0.8rem;
  color: #64748B;
  text-transform: uppercase;
  font-weight: 700;
  margin-top: 2px;
}

.infaq-btn-copy {
  background: #E2E8F0;
  color: #334155;
  border: none;
  padding: 8px 16px;
  border-radius: 8px;
  font-weight: 700;
  font-size: 0.85rem;
  cursor: pointer;
  transition: background 0.2s;
}

.infaq-btn-copy:active {
  background: #CBD5E1;
}

.infaq-qris-card {
  background: #F8FAFC;
  border: 1px dashed #CBD5E1;
  border-radius: 12px;
  padding: 16px;
  text-align: center;
  margin-bottom: 16px;
}

.infaq-qris-title {
  font-size: 0.9rem;
  font-weight: 700;
  color: #334155;
  margin-bottom: 12px;
}

.infaq-qris-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  color: #94A3B8;
  padding: 16px 0;
}

.infaq-qris-placeholder span {
  font-size: 0.8rem;
  font-weight: 600;
}

.infaq-qris-img-wrap {
  display: flex;
  justify-content: center;
  margin: 12px 0;
  background: white;
  padding: 12px;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.infaq-qris-img {
  max-width: 100%;
  height: auto;
  max-height: 280px;
  object-fit: contain;
}

.infaq-qris-tip {
  font-size: 0.72rem;
  color: #64748B;
  font-weight: 550;
  line-height: 1.4;
  margin-top: 8px;
  text-align: center;
  background: #F1F5F9;
  padding: 8px 12px;
  border-radius: 8px;
}

/* Consistency Section (Streak & Heatmap) CSS */
.consistency-section {
  margin-top: 24px;
}

.consistency-card {
  background: white;
  border-radius: var(--radius-lg);
  border: 1.5px solid rgba(0, 0, 0, 0.04);
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.02);
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.streak-hero {
  display: flex;
  align-items: center;
  gap: 16px;
  background: linear-gradient(135deg, #FFFDF5 0%, #FFF9E6 100%);
  border: 1.5px solid #FDE68A;
  border-radius: 16px;
  padding: 16px;
}

.streak-hero__icon {
  font-size: 2.2rem;
  animation: sparkFloat 3s ease-in-out infinite;
}

.streak-hero__detail {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.streak-hero__value {
  font-size: 1.5rem;
  font-weight: 900;
  color: #B45309;
  line-height: 1.1;
}

.streak-hero__label {
  font-size: 0.76rem;
  color: #8C8262;
  font-weight: 700;
}

.heatmap-container {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.heatmap-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
}

.heatmap-title {
  font-size: 0.8rem;
  font-weight: 750;
  color: #374151;
}

.heatmap-legend {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 0.65rem;
  color: #9CA3AF;
  font-weight: 600;
}

.legend-box {
  width: 10px;
  height: 10px;
  border-radius: 2px;
}

.legend-box--0 { background: #E2E8F0; }
.legend-box--1 { background: #D1FAE5; }
.legend-box--2 { background: #34D399; }
.legend-box--3 { background: #047857; }

.heatmap-grid-wrap {
  display: flex;
  gap: 8px;
  align-items: flex-start;
  overflow-x: auto;
  scrollbar-width: none; /* Hide scrollbar for clean grid */
  margin: 0 -4px;
  padding: 4px;
}

.heatmap-grid-wrap::-webkit-scrollbar {
  display: none;
}

.heatmap-y-labels {
  display: grid;
  grid-template-rows: repeat(7, 11px);
  gap: 4px;
  font-size: 0.62rem;
  color: #9CA3AF;
  font-weight: 750;
  width: 12px;
  text-align: center;
  line-height: 11px;
}

.heatmap-grid {
  display: grid;
  grid-template-rows: repeat(7, 11px);
  grid-auto-flow: column;
  grid-auto-columns: 11px;
  gap: 4px;
  width: max-content;
}

.heatmap-day {
  width: 11px;
  height: 11px;
  border-radius: 2px;
  transition: transform 0.1s;
}

.heatmap-day:hover {
  transform: scale(1.3);
  z-index: 1;
}

.heatmap-day--level-0 { background: #E2E8F0; border: 1px solid rgba(0,0,0,0.02); }
.heatmap-day--level-1 { background: #D1FAE5; }
.heatmap-day--level-2 { background: #34D399; }
.heatmap-day--level-3 { background: #047857; }

.heatmap-day--future {
  background: #F8FAFC !important;
  border: 1px dashed #CBD5E1 !important;
  cursor: not-allowed;
}

.heatmap-months {
  display: flex;
  justify-content: space-between;
  font-size: 0.68rem;
  color: #9CA3AF;
  font-weight: 600;
  margin-top: 2px;
}
</style>
