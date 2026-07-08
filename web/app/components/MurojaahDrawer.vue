<template>
  <Transition name="drawer-fade">
    <div class="murojaah-drawer-wrapper" v-if="isOpen">
      <!-- Backdrop overlay -->
      <div class="drawer-backdrop" @click="close"></div>

      <!-- Drawer Panel -->
      <div class="drawer-panel" :style="murojaahSheet.sheetStyle.value">
        <!-- Drag handle indicator -->
        <div class="drawer-handle-bar" v-bind="murojaahSheet.bindHandle">
          <div class="drawer-handle"></div>
        </div>

        <!-- Header -->
        <div class="drawer-header">
          <div class="drawer-title-wrap">
            <img src="/logo.png" alt="Murojaah Logo" class="drawer-logo" />
            <div>
              <h2 class="drawer-title">Mulai Murojaah</h2>
              <p class="drawer-subtitle">{{ isSessionPage ? 'Pilih cara berlatih untuk surat ini' : 'Pilih cara berlatih, lalu pilih surat' }}</p>
            </div>
          </div>
          <button class="drawer-close" @click="close" aria-label="Tutup">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>

        <div class="journey-cards" role="tablist" aria-label="Pilih cara murojaah">
          <!-- Uji Ayat -->
          <button
            type="button"
            class="journey-card journey-card--quiz"
            :class="{ 'journey-card--active': selectedJourney === 'quiz' }"
            role="tab"
            :aria-selected="selectedJourney === 'quiz'"
            @click="selectJourney('quiz')"
          >
            <span class="journey-card__name">Uji Ayat</span>
          </button>

          <!-- Mushaf -->
          <button
            type="button"
            class="journey-card journey-card--mushaf"
            :class="{ 'journey-card--active': selectedJourney === 'mushaf' }"
            role="tab"
            :aria-selected="selectedJourney === 'mushaf'"
            @click="selectJourney('mushaf')"
          >
            <span class="journey-card__name">Mushaf</span>
          </button>

          <!-- Dengar -->
          <button
            type="button"
            class="journey-card journey-card--listening"
            :class="{ 'journey-card--active': selectedJourney === 'listening' }"
            role="tab"
            :aria-selected="selectedJourney === 'listening'"
            @click="selectJourney('listening')"
          >
            <span class="journey-card__name">Quran per Ayat</span>
          </button>
        </div>

        <!-- Search Input -->
        <div v-if="!isSessionPage" class="drawer-search-row">
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
        <div v-if="!isSessionPage" class="drawer-body">
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
                    <div class="surah-meta">{{ surah.total_ayah }} ayat &middot; {{ revelationLabel(surah) }}</div>
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
                      {{ surah.name_translation }} &middot; {{ surah.total_ayah }} ayat
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

        <div v-else class="drawer-safe-bottom"></div>
      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'

const { isOpen, mode, format, close } = useMurojaahDrawer()
const { apiFetch } = useApi()

const murojaahSheet = useBottomSheet({
  mode: 'dismiss',
  closeThreshold: 90,
  onClose: close,
})

watch(isOpen, (val) => { if (val) murojaahSheet.reset() })

interface SurahItem {
  id: number
  number: number
  name_latin: string
  name_arabic: string
  name_translation: string
  total_ayah: number
  revelation_place: string
  page_start: number | null
  progress: {
    fluent: number
    doubtful: number
    forgot: number
  }
}

const surahs = ref<SurahItem[]>([])
const searchQuery = ref('')
const loading = ref(true)

type Journey = 'quiz' | 'mushaf' | 'listening'

const selectedJourney = computed<Journey>(() => {
  if (mode.value === 'listening') return 'listening'
  return format.value
})

const selectJourney = (journey: Journey) => {
  if (journey === 'listening') {
    mode.value = 'listening'
  } else {
    mode.value = 'learning'
    format.value = journey
  }

  // Jika sedang di halaman membaca (session aktif), langsung pindah mode untuk surat saat ini
  if (isSessionPage.value && currentSurahIdFromRoute.value !== null) {
    const activeSurahId = currentSurahIdFromRoute.value
    close()

    // Memberikan delay agar animasi slide-down drawer selesai sebelum halaman berpindah
    window.setTimeout(() => {
      if (mode.value === 'learning' && format.value === 'mushaf') {
        const selectedSurah = surahs.value.find(s => s.id === activeSurahId)
        router.push({
          path: `/mushaf/${selectedSurah?.page_start || 1}`,
          query: { surah: activeSurahId },
        })
      } else {
        router.push({
          path: `/remote/${activeSurahId}/1`,
          query: { mode: mode.value },
        })
      }
    }, 280)
  }
}

const revelationLabel = (surah: SurahItem) =>
  surah.revelation_place === 'meccan' ? 'Makkiyah' : 'Madaniyah'
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


const router = useRouter()
const route = useRoute()

const currentSurahIdFromRoute = computed(() => {
  if (route.params.surahId) {
    return Number(route.params.surahId)
  }
  if (route.query.surah) {
    return Number(route.query.surah)
  }
  return null
})

const isSessionPage = computed(() => currentSurahIdFromRoute.value !== null)

const selectSurah = (surahId: number) => {
  const targetMode = mode.value
  const selectedSurah = surahs.value.find(surah => surah.id === surahId)
  close()

  // Delay the navigation slightly so the slide-down animation of the drawer finishes cleanly
  window.setTimeout(() => {
    if (targetMode === 'learning' && format.value === 'mushaf') {
      router.push({
        path: `/mushaf/${selectedSurah?.page_start || 1}`,
        query: { surah: surahId },
      })
      return
    }

    router.push({ path: `/remote/${surahId}/1`, query: { mode: targetMode } })
  }, 280)
}
</script>

<style scoped>
.murojaah-drawer-wrapper {
  position: fixed;
  inset: 0;
  z-index: 2000;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
}

.drawer-backdrop {
  position: absolute;
  inset: 0;
  background: rgba(4, 39, 25, 0.45);
  backdrop-filter: blur(8px);
  z-index: 2001;
}

.drawer-panel {
  position: relative;
  width: 100%;
  max-width: 500px;
  margin: 0 auto;
  background: #FFFDF7;
  border-radius: 28px 28px 0 0;
  z-index: 2002;
  box-shadow: 0 -12px 36px rgba(0, 0, 0, 0.2);
  display: flex;
  flex-direction: column;
  max-height: 86dvh;
  border-top: 1.5px solid rgba(212, 175, 55, 0.25);
  overflow: hidden;
  user-select: none;
  -webkit-user-select: none;
}

.drawer-handle-bar {
  display: flex;
  justify-content: center;
  padding: 10px 0;
  cursor: grab;
  touch-action: none;
  user-select: none;
}
.drawer-handle-bar:active { cursor: grabbing; }

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

/* Journey Mode Cards */
.journey-cards {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
  margin: 0 20px 18px;
}

.journey-card {
  position: relative;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 12px 6px;
  border-radius: 14px;
  border: 1.5px solid rgba(0, 0, 0, 0.05);
  background: #f4f3ee;
  cursor: pointer;
  transition: transform 0.22s cubic-bezier(0.34, 1.56, 0.64, 1),
              box-shadow 0.22s ease,
              background 0.22s ease,
              border-color 0.22s ease,
              color 0.18s ease;
  text-align: center;
  color: #6b7280;
  overflow: hidden;
  -webkit-tap-highlight-color: transparent;
}

.journey-card:active {
  transform: scale(0.95) !important;
  transition: transform 0.1s ease !important;
}

.journey-card__name {
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: -0.01em;
  line-height: 1.2;
}


/* ─── Uji Ayat — Emerald ─── */
.journey-card--quiz.journey-card--active {
  background: linear-gradient(145deg, #054f38 0%, #0a9e72 100%);
  border-color: transparent;
  color: #fff;
  box-shadow: 0 4px 12px rgba(8, 125, 89, 0.24);
}

/* ─── Mushaf — Warm Amber ─── */
.journey-card--mushaf.journey-card--active {
  background: linear-gradient(145deg, #6b2d07 0%, #c2610a 100%);
  border-color: transparent;
  color: #fff;
  box-shadow: 0 4px 12px rgba(194, 97, 10, 0.24);
}

/* ─── Dengar — Deep Indigo ─── */
.journey-card--listening.journey-card--active {
  background: linear-gradient(145deg, #1a2e5c 0%, #2b5fd4 100%);
  border-color: transparent;
  color: #fff;
  box-shadow: 0 4px 12px rgba(43, 95, 212, 0.24);
}

.drawer-safe-bottom {
  height: calc(20px + var(--safe-bottom, 0px));
}

/* Search Row */.drawer-search-row {
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

.drawer-fade-enter-active .drawer-panel,
.drawer-fade-leave-active .drawer-panel {
  transition: transform 0.32s cubic-bezier(0.16, 1, 0.3, 1);
}

.drawer-fade-enter-from .drawer-panel,
.drawer-fade-leave-to .drawer-panel {
  transform: translateY(100%);
}

</style>
