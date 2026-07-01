<template>
  <Transition name="drawer-fade">
    <div class="murojaah-drawer-wrapper" v-if="isOpen">
      <!-- Backdrop overlay -->
      <div class="drawer-backdrop" @click="close"></div>

      <!-- Drawer Panel -->
      <div class="drawer-panel animate-slide-up">
        <!-- Drag handle indicator -->
        <div class="drawer-handle-bar">
          <div class="drawer-handle"></div>
        </div>

        <!-- Header -->
        <div class="drawer-header">
          <div class="drawer-title-wrap">
            <img src="/logo.png" alt="Murojaah Logo" class="drawer-logo" />
            <div>
              <h2 class="drawer-title">{{ drawerTitle }}</h2>
              <p class="drawer-subtitle">{{ drawerSubtitle }}</p>
            </div>
          </div>
          <button class="drawer-close" @click="close" aria-label="Tutup">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>

        <div class="drawer-mode-toggle">
          <button
            type="button"
            class="drawer-mode-toggle__btn"
            :class="{ 'drawer-mode-toggle__btn--active': mode === 'learning' }"
            @click="mode = 'learning'"
          >
            Mode Murojaah
          </button>
          <button
            type="button"
            class="drawer-mode-toggle__btn"
            :class="{ 'drawer-mode-toggle__btn--active': mode === 'listening' }"
            @click="mode = 'listening'"
          >
            Mode Mendengarkan
          </button>
        </div>

        <!-- Search Input -->
        <div class="drawer-search-row">
          <div class="search-wrapper">
            <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <circle cx="11" cy="11" r="8"/>
              <line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input
              type="text"
              class="search-input"
              placeholder="Cari surat..."
              v-model="searchQuery"
            />
          </div>
        </div>

        <!-- Content Area -->
        <div class="drawer-body">
          <!-- Loading state -->
          <div v-if="loading" class="drawer-loading">
            <div class="spinner"></div>
            <p>Memuat daftar surat...</p>
          </div>

          <div v-else class="drawer-scrollable-content">
            <!-- Active Surahs Section -->
            <div v-if="activeSurahs.length > 0 && !searchQuery" class="drawer-section">
              <h3 class="drawer-section-title">Surat Aktif</h3>
              <div class="surah-list">
                <div
                  v-for="surah in activeSurahs"
                  :key="surah.id"
                  class="drawer-surah-card drawer-surah-card--active"
                  @click="selectSurah(surah.id)"
                >
                  <div class="surah-badge-num">{{ surah.number }}</div>
                  <div class="surah-card-details">
                    <div class="surah-card-top">
                      <span class="surah-name">{{ surah.name_latin }}</span>
                      <span class="surah-arabic">{{ surah.name_arabic }}</span>
                    </div>
                    <div class="surah-progress-info">
                      <span class="progress-txt">Progres Murojaah: {{ activeAyahsCount(surah) }} / {{ surah.total_ayah }} Ayat</span>
                      <div class="mini-progress">
                        <div class="mini-bar mini-bar--fluent" :style="{ width: progressPercent(surah, 'fluent') }"></div>
                        <div class="mini-bar mini-bar--doubtful" :style="{ width: progressPercent(surah, 'doubtful') }"></div>
                        <div class="mini-bar mini-bar--forgot" :style="{ width: progressPercent(surah, 'forgot') }"></div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- All Surahs Section -->
            <div class="drawer-section">
              <h3 class="drawer-section-title" v-if="activeSurahs.length > 0 && !searchQuery">Semua Surat</h3>
              <div class="surah-list">
                <div
                  v-for="surah in filteredSurahs"
                  :key="surah.id"
                  class="drawer-surah-card"
                  @click="selectSurah(surah.id)"
                >
                  <div class="surah-badge-num">{{ surah.number }}</div>
                  <div class="surah-card-details">
                    <div class="surah-card-top">
                      <span class="surah-name">{{ surah.name_latin }}</span>
                      <span class="surah-arabic">{{ surah.name_arabic }}</span>
                    </div>
                    <div class="surah-meta">
                      {{ surah.name_translation }} · {{ surah.total_ayah }} Ayat
                    </div>
                  </div>
                </div>

                <!-- Empty state -->
                <div v-if="filteredSurahs.length === 0" class="drawer-empty">
                  <p>Tidak ada surat yang cocok</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'

const { isOpen, mode, close } = useMurojaahDrawer()
const { apiFetch } = useApi()

interface SurahItem {
  id: number
  number: number
  name_latin: string
  name_arabic: string
  name_translation: string
  total_ayah: number
  progress: {
    fluent: number
    doubtful: number
    forgot: number
  }
}

const surahs = ref<SurahItem[]>([])
const searchQuery = ref('')
const loading = ref(true)

const drawerTitle = computed(() => mode.value === 'listening' ? 'Mulai Mendengarkan' : 'Mulai Murojaah')
const drawerSubtitle = computed(() =>
  mode.value === 'listening'
    ? 'Pilih surat untuk sesi audio tanpa mencatat progress'
    : 'Pilih surat untuk remote hafalan'
)

const fetchSurahs = async () => {
  try {
    loading.value = true
    const res = await apiFetch<{ data: SurahItem[] }>('/surahs')
    surahs.value = res.data
  } catch (e) {
    console.error('Failed to load surahs for drawer:', e)
  } finally {
    loading.value = false
  }
}

watch(isOpen, (newVal) => {
  if (newVal && surahs.value.length === 0) {
    fetchSurahs()
  }
})

onMounted(() => {
  if (isOpen.value) {
    fetchSurahs()
  }
})

const activeSurahs = computed(() => {
  return surahs.value.filter(s => {
    const total = s.progress.fluent + s.progress.doubtful + s.progress.forgot
    return total > 0
  })
})

const filteredSurahs = computed(() => {
  if (!searchQuery.value) return surahs.value
  const q = searchQuery.value.toLowerCase()
  return surahs.value.filter(s =>
    s.name_latin.toLowerCase().includes(q) ||
    s.name_translation.toLowerCase().includes(q) ||
    s.name_arabic.includes(q) ||
    s.number.toString() === q
  )
})

const activeAyahsCount = (surah: SurahItem) => {
  return surah.progress.fluent + surah.progress.doubtful + surah.progress.forgot
}

const progressPercent = (surah: SurahItem, status: 'fluent' | 'doubtful' | 'forgot') => {
  const total = surah.total_ayah
  const count = surah.progress[status] || 0
  return `${(count / total) * 100}%`
}

const router = useRouter()
const selectSurah = (surahId: number) => {
  const targetMode = mode.value
  close()
  router.push({ path: `/remote/${surahId}/1`, query: { mode: targetMode } })
}
</script>

<style scoped>
.murojaah-drawer-wrapper {
  position: fixed;
  inset: 0;
  z-index: 1000;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
}

.drawer-backdrop {
  position: absolute;
  inset: 0;
  background: rgba(4, 39, 25, 0.45);
  backdrop-filter: blur(8px);
  z-index: 1001;
}

.drawer-panel {
  position: relative;
  width: 100%;
  max-width: 500px;
  margin: 0 auto;
  background: #FFFDF7;
  border-radius: 28px 28px 0 0;
  z-index: 1002;
  box-shadow: 0 -12px 36px rgba(0, 0, 0, 0.2);
  display: flex;
  flex-direction: column;
  max-height: 86dvh;
  animation: slideUp 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
  border-top: 1.5px solid rgba(212, 175, 55, 0.25);
  overflow: hidden;
}

@keyframes slideUp {
  from { transform: translateY(100%); }
  to { transform: translateY(0); }
}

.drawer-handle-bar {
  display: flex;
  justify-content: center;
  padding: 10px 0;
  cursor: grab;
}

.drawer-handle {
  width: 48px;
  height: 5px;
  border-radius: 99px;
  background: rgba(0, 0, 0, 0.12);
}

.drawer-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 6px 20px 14px;
}

.drawer-title-wrap {
  display: flex;
  align-items: center;
  gap: 12px;
}

.drawer-logo {
  width: 40px;
  height: 40px;
  object-fit: contain;
  filter: drop-shadow(0 4px 8px rgba(0,0,0,0.12));
}

.drawer-title {
  font-size: 1.25rem;
  font-weight: 800;
  color: #042719;
  letter-spacing: -0.02em;
  margin: 0;
}

.drawer-subtitle {
  font-size: 0.78rem;
  color: #6B7280;
  margin: 2px 0 0;
}

.drawer-close {
  width: 36px;
  height: 36px;
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

.drawer-close:hover {
  background: #E5E7EB;
  color: #1F2937;
  transform: rotate(90deg);
}

/* Search Row */
.drawer-mode-toggle {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
  padding: 0 20px 18px;
}

.drawer-mode-toggle__btn {
  min-height: 46px;
  border-radius: 16px;
  border: 1.5px solid rgba(6, 78, 59, 0.12);
  background: #F7F8F6;
  color: var(--color-text-secondary);
  font-size: 0.86rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.18s ease;
}

.drawer-mode-toggle__btn--active {
  background: linear-gradient(135deg, rgba(6, 78, 59, 0.12), rgba(16, 185, 129, 0.18));
  color: #065F46;
  border-color: rgba(6, 95, 70, 0.22);
  box-shadow: 0 8px 18px rgba(6, 95, 70, 0.08);
}
.drawer-mode-toggle {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
  padding: 0 20px 18px;
}

.drawer-mode-toggle__btn {
  min-height: 46px;
  border-radius: 16px;
  border: 1.5px solid rgba(6, 78, 59, 0.12);
  background: #F7F8F6;
  color: var(--color-text-secondary);
  font-size: 0.86rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.18s ease;
}

.drawer-mode-toggle__btn--active {
  background: linear-gradient(135deg, rgba(6, 78, 59, 0.12), rgba(16, 185, 129, 0.18));
  color: #065F46;
  border-color: rgba(6, 95, 70, 0.22);
  box-shadow: 0 8px 18px rgba(6, 95, 70, 0.08);
}
.drawer-search-row {
  padding: 0 20px 14px;
}

.search-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  background: #F4F3ED;
  border: 1.5px solid rgba(0, 0, 0, 0.04);
  border-radius: 14px;
  padding: 0 14px;
  height: 44px;
  transition: all 0.2s ease;
}

.search-wrapper:focus-within {
  background: #fff;
  border-color: #D4AF37;
  box-shadow: 0 4px 12px rgba(212, 175, 55, 0.1);
}

.search-icon {
  color: #9CA3AF;
  margin-right: 10px;
  flex-shrink: 0;
}

.search-input {
  width: 100%;
  border: none;
  background: transparent;
  font-size: 0.95rem;
  color: #1F2937;
  outline: none;
  padding-left:10px
}

.search-input::placeholder {
  color: #9CA3AF;
}

/* Body (Scrollable List) */
.drawer-body {
  flex: 1;
  overflow-y: auto;
  padding: 0 20px 30px;
  scrollbar-width: thin;
}

.drawer-body::-webkit-scrollbar {
  width: 6px;
}
.drawer-body::-webkit-scrollbar-thumb {
  background-color: rgba(0, 0, 0, 0.1);
  border-radius: 99px;
}

.drawer-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 0;
  color: #6B7280;
  gap: 12px;
}

.spinner {
  width: 32px;
  height: 32px;
  border: 3.5px solid rgba(4, 39, 25, 0.1);
  border-left-color: #D4AF37;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Sections & Cards */
.drawer-section {
  margin-bottom: 24px;
}

.drawer-section-title {
  font-size: 0.78rem;
  font-weight: 800;
  color: #8C8262;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  margin-bottom: 12px;
}

.surah-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.drawer-surah-card {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 12px 14px;
  background: #fff;
  border-radius: 16px;
  border: 1.5px solid rgba(0, 0, 0, 0.04);
  cursor: pointer;
  transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

.drawer-surah-card:hover {
  transform: translateY(-2px);
  border-color: rgba(212, 175, 55, 0.35);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.04);
  background: #FFFDF9;
}

.drawer-surah-card:active {
  transform: scale(0.98);
}

.drawer-surah-card--active {
  border-color: rgba(4, 39, 25, 0.1);
  background: linear-gradient(145deg, #FFFDF7 0%, #FAF8F0 100%);
}

.drawer-surah-card--active:hover {
  border-color: #D4AF37;
  background: #FFFDF5;
}

.surah-badge-num {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: #F4F3ED;
  color: #6B7280;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  font-weight: 800;
  flex-shrink: 0;
  border: 1px solid rgba(0,0,0,0.03);
}

.drawer-surah-card--active .surah-badge-num {
  background: #FEF3C7;
  color: #B45309;
  border-color: #FDE68A;
}

.surah-card-details {
  flex: 1;
  min-width: 0;
}

.surah-card-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 2px;
}

.surah-name {
  font-weight: 700;
  color: #1F2937;
  font-size: 0.95rem;
}

.surah-arabic {
  font-family: var(--font-arabic);
  font-size: 1.25rem;
  color: var(--color-primary-dark, #042719);
  direction: rtl;
  font-weight: normal;
}

.surah-meta {
  font-size: 0.78rem;
  color: #6B7280;
}

.surah-progress-info {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-top: 6px;
}

.progress-txt {
  font-size: 0.74rem;
  font-weight: 600;
  color: #6B7280;
}

.mini-progress {
  height: 5px;
  border-radius: 99px;
  background: #E5E7EB;
  display: flex;
  overflow: hidden;
}

.mini-bar {
  height: 100%;
}

.mini-bar--fluent { background: var(--color-fluent, #059669); }
.mini-bar--doubtful { background: var(--color-doubtful, #D97706); }
.mini-bar--forgot { background: var(--color-forgot, #DC2626); }

.drawer-empty {
  text-align: center;
  padding: 30px 0;
  color: #6B7280;
  font-size: 0.9rem;
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
</style>
