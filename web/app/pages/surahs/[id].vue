<template>
  <div class="page">
    <header class="page-header">
      <div class="page-header__arabesque" aria-hidden="true"></div>
      <div class="container" style="position: relative; z-index: 2;">
        <button
          v-if="surah"
          type="button"
          class="page-header__info page-header__surah-trigger"
          @click="openSurahPicker"
          aria-label="Pilih surat"
        >
          <span class="header-eyebrow" :class="'header-eyebrow--' + currentMode">
            {{ currentMode === 'listening' ? 'Mendengarkan' : 'Uji Hafalan' }}
          </span>
          <span class="page-header__title">
            {{ surah.name_latin }}
            <svg class="chevron-icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="6 9 12 15 18 9"/>
            </svg>
          </span>
          <span class="page-header__meta">
            Surah {{ surah.number }} &middot; {{ surah.total_ayah }} ayat &middot; {{ revelationLabel }}
          </span>

        </button>
      </div>
    </header>

    <div class="page-content container">


      <!-- Legend (hidden in listening mode) -->
      <div class="legend" v-if="currentMode !== 'listening'">
        <div class="legend__item"><span class="legend__dot legend__dot--fluent"></span> Lancar</div>
        <div class="legend__item"><span class="legend__dot legend__dot--doubtful"></span> Ragu</div>
        <div class="legend__item"><span class="legend__dot legend__dot--forgot"></span> Lupa</div>
        <div class="legend__item"><span class="legend__dot legend__dot--unreviewed"></span> Belum</div>
      </div>

      <!-- Ayah Grid -->
      <div class="ayah-grid" v-if="ayahs.length > 0">
        <NuxtLink
          v-for="ayah in ayahs"
          :key="ayah.id"
          :to="{ path: `/remote/${route.params.id}/${ayah.ayah_number}`, query: { mode: currentMode } }"
          class="ayah-cell"
          :class="currentMode === 'listening' ? 'ayah-cell--unreviewed' : `ayah-cell--${ayah.progress_status}`"
          @click="triggerHaptic"
        >
          {{ ayah.ayah_number }}
        </NuxtLink>
      </div>

      <!-- Loading -->
      <div class="ayah-grid" v-else-if="loading">
        <div v-for="i in 20" :key="i" class="skeleton ayah-cell" style="border: none"></div>
      </div>
    </div>

    <!-- iOS-style Surah Picker Sheet -->
    <Transition name="sheet">
      <div v-if="showSurahPicker" class="picker-overlay" @click="closeSurahPicker">
        <div class="picker-sheet animate-slide-up" @click.stop>
          <div class="picker-sheet__header">
            <div class="picker-sheet__indicator"></div>
            <div class="picker-sheet__title-row">
              <h3>Pilih Surat</h3>
              <button class="picker-sheet__close" @click="closeSurahPicker">Selesai</button>
            </div>
            <div class="picker-sheet__search">
              <input
                v-model="pickerSearch"
                type="text"
                placeholder="Cari nama atau nomor surat..."
                class="picker-sheet__search-input"
                id="picker-search"
                @click.stop
              />
            </div>
          </div>
          <div class="picker-sheet__content" @touchstart="isWheelDragging = false" @touchmove="isWheelDragging = true">
            <div class="picker-wheel">
              <div
                v-for="s in filteredPickerSurahs"
                :key="s.id"
                class="picker-wheel__item"
                :class="{ 'picker-wheel__item--active': s.id === Number(route.params.id) }"
                @click="handleSurahSelect(s.id)"
              >
                <span class="picker-wheel__number">{{ s.number }}</span>
                <div class="picker-wheel__names">
                  <span class="picker-wheel__latin">{{ s.name_latin }}</span>
                  <span class="picker-wheel__translation">{{ s.name_translation }}</span>
                </div>
                <span class="picker-wheel__arabic">{{ s.name_arabic }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
useScrollRestore('surahs-detail')
const route = useRoute()
const router = useRouter()
const { apiFetch } = useApi()

const triggerHaptic = () => {
  if (typeof navigator !== 'undefined' && navigator.vibrate) {
    navigator.vibrate(40)
  }
}

interface SurahDetail {
  id: number
  number: number
  name_latin: string
  name_arabic: string
  name_translation: string
  total_ayah: number
  revelation_place: string
}

interface AyahItem {
  id: number
  surah_id: number
  ayah_number: number
  progress_status: string
}

const surah = ref<SurahDetail | null>(null)
const ayahs = ref<AyahItem[]>([])
const surahList = ref<SurahDetail[]>([])
const loading = ref(true)
const currentMode = computed<'learning' | 'listening'>(() => route.query.mode === 'listening' ? 'listening' : 'learning')
const revelationLabel = computed(() => surah.value?.revelation_place === 'meccan' ? 'Makkiyah' : 'Madaniyah')

const showSurahPicker = ref(false)
const isWheelDragging = ref(false)
const pickerSearch = ref('')

const openSurahPicker = () => {
  triggerHaptic()
  pickerSearch.value = ''
  showSurahPicker.value = true
}

const closeSurahPicker = () => {
  triggerHaptic()
  showSurahPicker.value = false
}

const handleSurahSelect = (id: number) => {
  if (isWheelDragging.value) return
  triggerHaptic()
  showSurahPicker.value = false
  router.push({ path: `/surahs/${id}`, query: { mode: currentMode.value } })
}

const filteredPickerSurahs = computed(() => {
  if (!pickerSearch.value) return surahList.value
  const q = pickerSearch.value.toLowerCase()
  return surahList.value.filter(s =>
    s.name_latin.toLowerCase().includes(q) ||
    s.name_arabic.includes(q) ||
    s.number.toString() === q
  )
})

const loadData = async (id: number | string) => {
  loading.value = true
  try {
    const [surahRes, ayahsRes] = await Promise.all([
      apiFetch<{ data: SurahDetail }>(`/surahs/${id}`),
      apiFetch<{ data: AyahItem[] }>(`/surahs/${id}/ayahs`),
    ])
    surah.value = surahRes.data
    ayahs.value = ayahsRes.data
  } catch (e) {
    console.error('Failed to load ayahs:', e)
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  loadData(route.params.id as string)

  // Load surah list for the picker
  try {
    const listRes = await apiFetch<{ data: SurahDetail[] }>('/surahs')
    surahList.value = listRes.data
  } catch (e) {
    console.error('Failed to load surah list:', e)
  }
})

// Watch for route changes to load new surah details when selected via picker
watch(() => route.params.id, (newId) => {
  if (newId) {
    loadData(newId as string)
  }
})

useHead({
  title: computed(() => surah.value ? `${surah.value.name_latin} — Pilih Ayat` : 'Pilih Ayat'),
})
</script>

<style scoped>
.page-header {
  position: sticky;
  top: 0;
  z-index: 100;
  background: linear-gradient(160deg, #052e1c 0%, #064E3B 45%, #0a6349 100%) !important;
  color: white;
  padding: calc(var(--safe-top) + 16px) 0 16px;
  overflow: hidden;
  box-shadow: 0 4px 14px rgba(4, 37, 25, 0.15);
}

.page-header__arabesque {
  position: absolute;
  inset: 0;
  background-image:
    radial-gradient(ellipse at 10% 60%, rgba(212, 175, 55, 0.07) 0%, transparent 55%),
    radial-gradient(ellipse at 90% 20%, rgba(255, 255, 255, 0.04) 0%, transparent 45%),
    url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='60' height='60'%3E%3Cpath d='M30 0 L35 25 L60 30 L35 35 L30 60 L25 35 L0 30 L25 25 Z' fill='none' stroke='rgba(212,175,55,0.04)' stroke-width='1'/%3E%3C/svg%3E");
  background-size: auto, auto, 60px 60px;
  pointer-events: none;
  z-index: 1;
}

.page-header__info {
  width: 100%;
  max-width: 520px;
  margin: 0 auto;
  padding: 0 12px;
  display: flex;
  flex-direction: column;
  align-items: center;
  border: 0;
  background: transparent;
  color: white;
  text-align: center;
  cursor: pointer;
}

.page-header__title {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  font-size: 1.32rem;
  font-weight: 850;
  line-height: 1.25;
}

.page-header__meta {
  margin-top: 4px;
  color: rgba(255, 255, 255, 0.72);
  font-size: 0.74rem;
  font-weight: 600;
}

.header-eyebrow {
  display: block;
  font-size: 0.65rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  margin-bottom: 5px;
}

.header-eyebrow--learning {
  color: #34D399; /* Emerald */
}

.header-eyebrow--listening {
  color: #FBBF24; /* Amber Gold */
}

.mode-launcher {
  display: grid;
  gap: 12px;
  margin-bottom: 18px;
}

.mode-launcher__card {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 16px 18px;
  border-radius: 22px;
  border: none;
  cursor: pointer;
  text-align: left;
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
}

.mode-launcher__copy {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.mode-launcher__copy strong {
  font-size: 0.98rem;
}

.mode-launcher__copy span {
  font-size: 0.82rem;
  line-height: 1.4;
  color: var(--color-text-secondary);
}

.mode-launcher__cta {
  font-size: 0.82rem;
  font-weight: 800;
  flex-shrink: 0;
}

.mode-launcher__card--learning {
  background: linear-gradient(135deg, #ffffff, #f4fbf6);
  border: 1px solid rgba(6, 95, 70, 0.1);
}

.mode-launcher__card--learning .mode-launcher__cta {
  color: #065f46;
}

.mode-launcher__card--listening {
  background: linear-gradient(135deg, #f0fdf4, #dcfce7);
  border: 1px solid rgba(16, 185, 129, 0.14);
}

.mode-launcher__card--listening .mode-launcher__cta {
  color: #047857;
}
.legend {
  display: flex;
  gap: 16px;
  margin-bottom: 20px;
  flex-wrap: wrap;
}

.legend__item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.75rem;
  color: var(--color-text-secondary);
}

.legend__dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
}

.legend__dot--fluent { background: var(--color-fluent); }
.legend__dot--doubtful { background: var(--color-doubtful); }
.legend__dot--forgot { background: var(--color-forgot); }
.legend__dot--unreviewed { background: var(--color-unreviewed); }

.ayah-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 8px;
}

.ayah-cell {
  aspect-ratio: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: var(--radius-md);
  font-weight: 600;
  font-size: 0.9375rem;
  text-decoration: none;
  transition: all var(--transition-fast);
  border: 2px solid transparent;
}

.ayah-cell:active {
  transform: scale(0.92);
}

.ayah-cell--fluent {
  background: var(--color-fluent-bg);
  color: var(--color-fluent);
  border-color: var(--color-fluent-border);
}

.ayah-cell--doubtful {
  background: var(--color-doubtful-bg);
  color: #B45309;
  border-color: var(--color-doubtful-border);
}

.ayah-cell--forgot {
  background: var(--color-forgot-bg);
  color: var(--color-forgot);
  border-color: var(--color-forgot-border);
}

.ayah-cell--unreviewed {
  background: var(--color-bg-subtle);
  color: var(--color-text-secondary);
  border-color: transparent;
}

.page-header__surah-trigger {
  cursor: pointer;
  user-select: none;
  -webkit-tap-highlight-color: transparent;
}

.page-header__surah-trigger h1 {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin: 0;
  transition: opacity var(--transition-fast);
}

.page-header__surah-trigger:active h1 {
  opacity: 0.7;
}

.chevron-icon {
  margin-top: 2px;
  opacity: 0.8;
}

/* iOS-style Sheet Picker Overlay */
.picker-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 1000;
  display: flex;
  align-items: flex-end;
  justify-content: center;
  backdrop-filter: blur(4px);
}

.picker-sheet {
  width: 100%;
  max-width: 540px;
  background: var(--color-bg-card);
  border-radius: var(--radius-xl) var(--radius-xl) 0 0;
  display: flex;
  flex-direction: column;
  max-height: 75dvh;
  box-shadow: var(--shadow-lg);
  padding-bottom: calc(var(--safe-bottom) + 8px);
}

.picker-sheet__header {
  padding: 16px 20px 12px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.08);
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.picker-sheet__indicator {
  width: 40px;
  height: 5px;
  background: var(--color-text-muted);
  border-radius: var(--radius-full);
  margin: 0 auto 4px;
  opacity: 0.5;
}

.picker-sheet__title-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.picker-sheet__title-row h3 {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--color-text);
  margin: 0;
}

.picker-sheet__close {
  color: var(--color-primary);
  font-weight: 700;
  font-size: 1rem;
  padding: 4px 8px;
  border: none;
  background: none;
  cursor: pointer;
}

.picker-sheet__search-input {
  width: 100%;
  padding: 10px 14px;
  border: 1.5px solid rgba(0, 0, 0, 0.08);
  border-radius: var(--radius-md);
  font-size: 0.9375rem;
  outline: none;
  background: var(--color-bg-subtle);
  transition: all var(--transition-fast);
}

.picker-sheet__search-input:focus {
  border-color: var(--color-primary);
  background: white;
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
}

.picker-sheet__content {
  overflow-y: auto;
  flex: 1;
  -webkit-overflow-scrolling: touch;
  padding: 8px 16px 20px;
}

.picker-wheel {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.picker-wheel__item {
  display: flex;
  align-items: center;
  padding: 14px 16px;
  border-radius: var(--radius-md);
  transition: all var(--transition-fast);
  cursor: pointer;
  user-select: none;
  -webkit-tap-highlight-color: transparent;
  color: var(--color-text);
}

.picker-wheel__item:active {
  background: var(--color-bg-subtle);
}

.picker-wheel__item--active {
  background: var(--color-primary-50) !important;
  color: var(--color-primary-dark) !important;
  font-weight: 700;
}

.picker-wheel__number {
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--color-text-secondary);
  background: rgba(0, 0, 0, 0.04);
  width: 26px;
  height: 26px;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin-right: 12px;
  flex-shrink: 0;
}

.picker-wheel__item--active .picker-wheel__number {
  background: rgba(5, 150, 105, 0.12);
  color: var(--color-primary);
}

.picker-wheel__names {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 2px;
}

.picker-wheel__latin {
  font-size: 1rem;
  font-weight: 600;
  line-height: 1.2;
}

.picker-wheel__translation {
  font-size: 0.75rem;
  color: var(--color-text-muted);
  font-weight: 500;
  line-height: 1.2;
}

.picker-wheel__item--active .picker-wheel__translation {
  color: var(--color-primary-dark);
  opacity: 0.8;
}

.picker-wheel__arabic {
  margin-left: auto;
  font-family: var(--font-arabic);
  font-size: 1.35rem;
  color: var(--color-primary-dark);
  direction: rtl;
}

/* Sheet slide-up transition */
.sheet-enter-active,
.sheet-leave-active {
  transition: opacity 0.3s ease;
}

.sheet-enter-from,
.sheet-leave-to {
  opacity: 0;
}

.sheet-enter-active .picker-sheet,
.sheet-leave-active .picker-sheet {
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.sheet-enter-from .picker-sheet,
.sheet-leave-to .picker-sheet {
  transform: translateY(100%);
}

@media (min-width: 480px) {
  .ayah-grid {
    grid-template-columns: repeat(7, 1fr);
  }
}

@media (min-width: 768px) {
  .ayah-grid {
    grid-template-columns: repeat(10, 1fr);
  }
}
</style>
