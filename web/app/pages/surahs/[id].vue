<template>
  <div class="page">
    <header class="page-header">
      <div class="container">
        <!-- Top Row: Back button & Title Info -->
        <div class="page-header__top-row">
          <NuxtLink to="/surahs" class="page-header__back" aria-label="Kembali" @click="triggerHaptic">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="15 18 9 12 15 6"/>
            </svg>
          </NuxtLink>
          <div class="page-header__info page-header__surah-trigger" @click="openSurahPicker" v-if="surah">
            <h1>
              {{ surah.name_latin }}
              <svg class="chevron-icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9"/>
              </svg>
            </h1>
            <p>Pilih ayat awal — {{ surah.total_ayah }} ayat · {{ surah.name_arabic }}</p>
          </div>
        </div>

        <!-- Surah Pagination Navigation Bar inside Header -->
        <div class="surah-pagination" v-if="surah">
          <button
            class="surah-pagelink surah-pagelink--prev"
            :disabled="surah.number === 1"
            @click="handleSurahSelect(surah.number - 1)"
          >
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="15 18 9 12 15 6"/>
            </svg>
            <span class="surah-pagelink__name">
              <span class="surah-pagelink__label">Sebelumnya</span>
              <span class="surah-pagelink__title">{{ prevSurah ? prevSurah.name_latin : '-' }}</span>
            </span>
          </button>

          <button
            class="surah-pagelink surah-pagelink--next"
            :disabled="surah.number === 114"
            @click="handleSurahSelect(surah.number + 1)"
          >
            <span class="surah-pagelink__name">
              <span class="surah-pagelink__label">Selanjutnya</span>
              <span class="surah-pagelink__title">{{ nextSurah ? nextSurah.name_latin : '-' }}</span>
            </span>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="9 18 15 12 9 6"/>
            </svg>
          </button>
        </div>
      </div>
    </header>

    <div class="page-content container">


      <!-- Legend -->
      <div class="legend">
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
          :to="`/remote/${route.params.id}/${ayah.ayah_number}`"
          class="ayah-cell"
          :class="`ayah-cell--${ayah.progress_status}`"
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
  router.push(`/surahs/${id}`)
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

const prevSurah = computed(() => {
  if (!surah.value || !surahList.value.length) return null
  const currentNum = surah.value.number
  return surahList.value.find(s => s.number === currentNum - 1) || null
})

const nextSurah = computed(() => {
  if (!surah.value || !surahList.value.length) return null
  const currentNum = surah.value.number
  return surahList.value.find(s => s.number === currentNum + 1) || null
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
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.page-header__top-row {
  display: flex;
  align-items: center;
  gap: 16px;
}

.page-header__back {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.12);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  flex-shrink: 0;
  transition: background var(--transition-fast);
  text-decoration: none;
}

.page-header__back:active {
  background: rgba(255, 255, 255, 0.25);
}

.page-header__info {
  flex: 1;
  min-width: 0;
}

/* ========== SURAH PAGINATION IN HEADER ========== */
.surah-pagination {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  margin-top: 14px;
  margin-bottom: 4px;
}

.surah-pagelink {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 10px;
  background: rgba(255, 255, 255, 0.12);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: var(--radius-lg);
  padding: 10px 14px;
  cursor: pointer;
  box-shadow: var(--shadow-sm);
  transition: transform var(--transition-fast), background var(--transition-fast);
  color: white;
  text-align: left;
  min-width: 0;
}

.surah-pagelink:active:not(:disabled) {
  transform: scale(0.98);
  background: rgba(255, 255, 255, 0.22);
}

.surah-pagelink--next {
  justify-content: flex-end;
  text-align: right;
}

.surah-pagelink__name {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.surah-pagelink__label {
  font-size: 0.65rem;
  color: rgba(255, 255, 255, 0.65);
  text-transform: uppercase;
  font-weight: 300; /* Light label weight */
  letter-spacing: 0.06em;
}

.surah-pagelink__title {
  font-size: 0.9375rem; /* Larger title */
  font-weight: 800; /* Extra bold */
  color: #FFFFFF;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.surah-pagelink:disabled {
  opacity: 0.25;
  cursor: not-allowed;
  background: rgba(255, 255, 255, 0.04);
  border-color: transparent;
  box-shadow: none;
}

.surah-pagelink:disabled .surah-pagelink__title {
  color: rgba(255, 255, 255, 0.4);
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
