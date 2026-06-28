<template>
  <div class="remote-page" @touchstart.passive="onTouchStart" @touchend.passive="onTouchEnd">
    <!-- Header — 12% -->
    <header class="remote-header">
      <div class="remote-header__left">
        <button class="remote-header__back" @click="goBack" aria-label="Kembali">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="15 18 9 12 15 6"/>
          </svg>
        </button>
        <div class="remote-header__surah-trigger" @click="openSurahPicker">
          <h1 class="remote-header__title">
            {{ surahNumber }}. {{ surahName }}
            <svg class="chevron-icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="6 9 12 15 18 9"/>
            </svg>
          </h1>
          <p class="remote-header__subtitle">Ayat {{ currentAyahNumber }} dari {{ totalAyah }}</p>
        </div>
      </div>
      <div class="remote-header__right">
        <span class="remote-header__badge" @click="openAyahPicker" title="Pilih Ayat">{{ currentAyahNumber }}</span>
      </div>
    </header>

    <!-- Content — 52% -->
    <main class="remote-content" @click="toggleReveal">
      <Transition name="ayah" mode="out-in">
        <!-- Hidden State -->
        <div v-if="!isRevealed" class="remote-hidden" key="hidden">
          <div class="remote-hidden__icon">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
              <circle cx="12" cy="12" r="3"/>
              <line x1="1" y1="1" x2="23" y2="23"/>
            </svg>
          </div>
          <p class="remote-hidden__text">AYAT DISEMBUNYIKAN</p>
          <p class="remote-hidden__hint">Ketuk untuk tampilkan</p>
        </div>

        <!-- Revealed State -->
        <div v-else class="remote-revealed" key="revealed">
          <div class="remote-ayah-card">
            <!-- Prominent Ayah Number Badge -->
            <div class="remote-ayah-number-badge animate-scale-in">
              Ayat {{ currentAyahNumber }}
            </div>
            
            <p class="text-arabic text-arabic-xl remote-ayah-text" v-if="currentAyah">
              <span v-html="formatArabicText(currentAyah.text_arabic)"></span>
              <span class="ayah-circle">
                <span class="ayah-circle-num">{{ currentAyahNumber }}</span>
              </span>
            </p>
          </div>
          
          <p class="remote-translation" v-if="currentAyah?.translation_id">
            {{ currentAyah.translation_id }}
          </p>
          
          <button class="remote-hide-btn" @click.stop="hideAyah">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
              <line x1="1" y1="1" x2="23" y2="23"/>
            </svg>
            Sembunyikan
          </button>
        </div>
      </Transition>
    </main>

    <!-- Review Buttons — 16% -->
    <section class="remote-review">
      <button class="review-btn review-btn--forgot" @click="submitReview('forgot')" :disabled="submitting">
        <span class="review-btn__icon">✗</span>
        <span class="review-btn__label">Lupa</span>
      </button>
      <button class="review-btn review-btn--doubtful" @click="submitReview('doubtful')" :disabled="submitting">
        <span class="review-btn__icon">~</span>
        <span class="review-btn__label">Ragu</span>
      </button>
      <button class="review-btn review-btn--fluent" @click="submitReview('fluent')" :disabled="submitting">
        <span class="review-btn__icon">✓</span>
        <span class="review-btn__label">Lancar</span>
      </button>
    </section>

    <!-- Navigation — 20% -->
    <nav class="remote-nav">
      <button
        class="nav-btn nav-btn--prev"
        @click="prevAyah"
        :disabled="currentAyahNumber <= 1"
      >
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="15 18 9 12 15 6"/>
        </svg>
        Sebelumnya
      </button>
      <button class="nav-btn nav-btn--skip" @click="skipAyah" :disabled="currentAyahNumber >= totalAyah">
        Selanjutnya
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="9 18 15 12 9 6"/>
        </svg>
      </button>
    </nav>

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
                :class="{ 'picker-wheel__item--active': s.id === surahId }"
                @click="handleSurahSelect(s.id)"
              >
                <span class="picker-wheel__number">{{ s.number }}</span>
                <span class="picker-wheel__latin">{{ s.name_latin }}</span>
                <span class="picker-wheel__arabic">{{ s.name_arabic }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- iOS-style Ayah Picker Sheet -->
    <Transition name="sheet">
      <div v-if="showAyahPicker" class="picker-overlay" @click="closeAyahPicker">
        <div class="picker-sheet animate-slide-up" @click.stop>
          <div class="picker-sheet__header">
            <div class="picker-sheet__indicator"></div>
            <div class="picker-sheet__title-row">
              <h3>Pilih Ayat ({{ surahName }})</h3>
              <button class="picker-sheet__close" @click="closeAyahPicker">Selesai</button>
            </div>
          </div>
          <div class="picker-sheet__content picker-sheet__content--grid">
            <div class="ayah-picker-grid">
              <button
                v-for="n in totalAyah"
                :key="n"
                class="ayah-picker-cell"
                :class="{ 'ayah-picker-cell--active': n === currentAyahNumber }"
                @click="changeAyah(n)"
              >
                {{ n }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Flash Feedback Overlay -->
    <Transition name="flash">
      <div v-if="flashStatus" class="remote-flash" :class="`remote-flash--${flashStatus}`"></div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
const route = useRoute()
const router = useRouter()
const { apiFetch } = useApi()
const showToast = inject<(msg: string, type: string) => void>('showToast')

const surahId = computed(() => Number(route.params.surahId))
const currentAyahNumber = ref(Number(route.params.ayahNumber))

interface AyahData {
  id: number
  surah_id: number
  surah_name: string
  surah_name_arabic: string
  surah_number: number
  ayah_number: number
  total_ayah: number
  text_arabic: string
  translation_id: string | null
  progress_status: string
}

interface SurahItem {
  id: number
  number: number
  name_latin: string
  name_arabic: string
  name_translation: string
  total_ayah: number
  revelation_place: string
}

const currentAyah = ref<AyahData | null>(null)
const surahList = ref<SurahItem[]>([])
const isRevealed = ref(false)
const submitting = ref(false)
const flashStatus = ref<string | null>(null)
const showSurahPicker = ref(false)
const showAyahPicker = ref(false)
const isWheelDragging = ref(false)
const pickerSearch = ref('')

const surahName = computed(() => currentAyah.value?.surah_name || '...')
const surahNumber = computed(() => currentAyah.value?.surah_number || surahId.value)
const totalAyah = computed(() => currentAyah.value?.total_ayah || 0)

// Helper for haptic vibration
const triggerHaptic = (ms = 40) => {
  if (typeof navigator !== 'undefined' && navigator.vibrate) {
    navigator.vibrate(ms)
  }
}

// Helper logic for Surah navigation list
const currentSurahIndex = computed(() => {
  return surahList.value.findIndex(s => s.id === surahId.value)
})
const nextSurah = computed(() => {
  if (currentSurahIndex.value === -1 || currentSurahIndex.value >= surahList.value.length - 1) return null
  return surahList.value[currentSurahIndex.value + 1]
})
const prevSurah = computed(() => {
  if (currentSurahIndex.value <= 0) return null
  return surahList.value[currentSurahIndex.value - 1]
})

const filteredPickerSurahs = computed(() => {
  if (!pickerSearch.value) return surahList.value
  const q = pickerSearch.value.toLowerCase()
  return surahList.value.filter(s =>
    s.name_latin.toLowerCase().includes(q) ||
    s.name_arabic.includes(q) ||
    s.number.toString() === q
  )
})

// Track swipe gestures (horizontal for ayahs, vertical for surahs)
let touchStartX = 0
let touchStartY = 0
const onTouchStart = (e: TouchEvent) => {
  touchStartX = e.touches[0].clientX
  touchStartY = e.touches[0].clientY
}
const onTouchEnd = (e: TouchEvent) => {
  const diffX = e.changedTouches[0].clientX - touchStartX
  const diffY = e.changedTouches[0].clientY - touchStartY

  if (Math.abs(diffX) > Math.abs(diffY)) {
    // Horizontal swipe -> Prev/Next Ayah
    if (Math.abs(diffX) > 75) {
      if (diffX > 0 && currentAyahNumber.value > 1) prevAyah()
      else if (diffX < 0 && currentAyahNumber.value < totalAyah.value) skipAyah()
    }
  } else {
    // Vertical swipe -> Prev/Next Surah
    if (Math.abs(diffY) > 85) {
      if (diffY > 0 && prevSurah.value) {
        // Swipe down -> Prev Surah
        showToast?.(`Surat: ${prevSurah.value.name_latin}`, 'info')
        changeSurah(prevSurah.value.id)
      } else if (diffY < 0 && nextSurah.value) {
        // Swipe up -> Next Surah
        showToast?.(`Surat: ${nextSurah.value.name_latin}`, 'info')
        changeSurah(nextSurah.value.id)
      }
    }
  }
}

const fetchAyah = async (ayahNum: number) => {
  try {
    const res = await apiFetch<{ data: AyahData }>(`/surahs/${surahId.value}/ayahs/${ayahNum}`)
    currentAyah.value = res.data
    isRevealed.value = false
  } catch (e) {
    console.error('Failed to fetch ayah:', e)
  }
}

const fetchSurahList = async () => {
  try {
    const res = await apiFetch<{ data: SurahItem[] }>('/surahs')
    surahList.value = res.data
  } catch (e) {
    console.error('Failed to load surah list:', e)
  }
}

const toggleReveal = () => {
  if (!isRevealed.value) {
    triggerHaptic(30)
    isRevealed.value = true
  }
}

const hideAyah = () => {
  triggerHaptic(30)
  isRevealed.value = false
}

const openSurahPicker = () => {
  triggerHaptic(40)
  pickerSearch.value = ''
  showSurahPicker.value = true
}

const closeSurahPicker = () => {
  triggerHaptic(40)
  showSurahPicker.value = false
}

const openAyahPicker = () => {
  triggerHaptic(40)
  showAyahPicker.value = true
}

const closeAyahPicker = () => {
  triggerHaptic(40)
  showAyahPicker.value = false
}

const changeSurah = (targetSurahId: number) => {
  triggerHaptic(50)
  showSurahPicker.value = false
  router.push(`/remote/${targetSurahId}/1`)
}

const handleSurahSelect = (id: number) => {
  if (isWheelDragging.value) return
  changeSurah(id)
}

const changeAyah = (targetAyahNum: number) => {
  triggerHaptic(50)
  showAyahPicker.value = false
  currentAyahNumber.value = targetAyahNum
  fetchAyah(targetAyahNum)
  router.replace(`/remote/${surahId.value}/${targetAyahNum}`)
}

const submitReview = async (status: 'forgot' | 'doubtful' | 'fluent') => {
  if (!currentAyah.value || submitting.value) return
  submitting.value = true

  // Flash feedback
  flashStatus.value = status
  setTimeout(() => { flashStatus.value = null }, 350)

  // Haptic feedback
  triggerHaptic(60)

  const statusLabels = { forgot: 'Lupa', doubtful: 'Ragu', fluent: 'Lancar' }

  try {
    await apiFetch('/reviews', {
      method: 'POST',
      body: JSON.stringify({
        surah_id: currentAyah.value.surah_id,
        ayah_id: currentAyah.value.id,
        status,
        was_revealed: isRevealed.value,
      }),
    })

    showToast?.(`Ayat ${currentAyahNumber.value} — ${statusLabels[status]}`, status)

    // Auto advance to next ayah
    if (currentAyahNumber.value < totalAyah.value) {
      currentAyahNumber.value++
      await fetchAyah(currentAyahNumber.value)
      router.replace(`/remote/${surahId.value}/${currentAyahNumber.value}`)
    } else {
      showToast?.('Surat selesai! 🎉', 'fluent')
      setTimeout(() => router.push(`/progress/${surahId.value}`), 1200)
    }
  } catch (e) {
    console.error('Failed to submit review:', e)
  } finally {
    submitting.value = false
  }
}

const prevAyah = async () => {
  if (currentAyahNumber.value <= 1) return
  triggerHaptic(45)
  currentAyahNumber.value--
  await fetchAyah(currentAyahNumber.value)
  router.replace(`/remote/${surahId.value}/${currentAyahNumber.value}`)
}

const skipAyah = async () => {
  if (currentAyahNumber.value >= totalAyah.value) return
  triggerHaptic(45)
  currentAyahNumber.value++
  await fetchAyah(currentAyahNumber.value)
  router.replace(`/remote/${surahId.value}/${currentAyahNumber.value}`)
}

const goBack = () => {
  triggerHaptic(40)
  router.push(`/surahs/${surahId.value}`)
}

// Parse Quran Tajweed API markup into color-coded HTML spans
const formatArabicText = (text: string) => {
  if (!text) return ''
  
  // Clean BOM character if any
  let cleanText = text.replace(/^\uFEFF/, '')

  // Regex to match: [rule[text] or [rule:meta[text]
  const regex = /\[([a-z0-9:]+)\[([^\]]+)\]/g

  // WebKit (Safari dan semua browser di iOS seperti Chrome/Firefox) merusak sambungan huruf Arab
  // jika dibungkus dalam tag span. Kita deteksi WebKit untuk otomatis men-strip kode warna
  // agar huruf Arab tersambung rapi kembali di perangkat Apple.
  const isWebKit = typeof navigator !== 'undefined' && (
    /iPad|iPhone|iPod/.test(navigator.userAgent) || 
    (/^((?!chrome|android).)*safari/i.test(navigator.userAgent)) ||
    (navigator.maxTouchPoints && navigator.maxTouchPoints > 2 && /Macintosh/.test(navigator.userAgent))
  )

  if (isWebKit) {
    return cleanText.replace(regex, '$2')
  }

  return cleanText.replace(regex, (match, ruleInfo, char) => {
    const rule = ruleInfo.split(':')[0]
    return `<span class="tajweed-${rule}">${char}</span>`
  })
}

// Watch for route changes to load the correct ayah dynamically
watch(() => [route.params.surahId, route.params.ayahNumber], async ([newSurahId, newAyahNum]) => {
  if (newSurahId && newAyahNum) {
    currentAyahNumber.value = Number(newAyahNum)
    await fetchAyah(Number(newAyahNum))
  }
})

// Initial fetches
onMounted(() => {
  fetchAyah(currentAyahNumber.value)
  fetchSurahList()
})

useHead({
  title: computed(() => currentAyah.value
    ? `${currentAyah.value.surah_name} ${currentAyahNumber.value} — Murojaah`
    : 'Murojaah'
  ),
})
</script>

<style scoped>
.remote-page {
  height: 100dvh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  background: var(--color-bg);
  max-width: 540px;
  margin: 0 auto;
  position: relative;
  touch-action: pan-x;
}

/* Header — 12% */
.remote-header {
  flex: 0 0 12%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: calc(var(--safe-top) + 8px) 16px 8px;
  background: linear-gradient(135deg, #064E3B 0%, #047857 100%);
  color: white;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  z-index: 10;
}

.remote-header__left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.remote-header__back {
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
}

.remote-header__back:active {
  background: rgba(255, 255, 255, 0.25);
}

.remote-header__surah-trigger {
  cursor: pointer;
  padding: 2px 4px;
  border-radius: var(--radius-sm);
  transition: background var(--transition-fast);
}

.remote-header__surah-trigger:active {
  background: rgba(255, 255, 255, 0.1);
}

.remote-header__title {
  font-size: 1.45rem;
  font-weight: 700;
  letter-spacing: -0.01em;
  display: flex;
  align-items: center;
  gap: 6px;
}

.chevron-icon {
  opacity: 0.8;
  transition: transform var(--transition-fast);
}

.remote-header__surah-trigger:active .chevron-icon {
  transform: translateY(2px);
}

.remote-header__subtitle {
  font-size: 0.75rem;
  opacity: 0.8;
  margin-top: 1px;
}

.remote-header__badge {
  background: rgba(255, 255, 255, 0.18);
  min-width: 48px;
  height: 48px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0 10px;
  border-radius: var(--radius-full);
  font-weight: 800;
  font-size: 1.35rem;
  backdrop-filter: blur(4px);
  border: 1.5px solid rgba(255, 255, 255, 0.15);
  cursor: pointer;
  transition: transform var(--transition-fast), background var(--transition-fast);
  user-select: none;
  -webkit-tap-highlight-color: transparent;
}

.remote-header__badge:active {
  transform: scale(0.92);
  background: rgba(255, 255, 255, 0.3);
}

.picker-sheet__content--grid {
  padding: 16px 20px 24px;
}

.ayah-picker-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 10px;
  max-height: 45vh;
  overflow-y: auto;
}

.ayah-picker-cell {
  aspect-ratio: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: var(--radius-md);
  font-weight: 700;
  font-size: 1rem;
  background: var(--color-bg-subtle);
  color: var(--color-text-secondary);
  transition: all var(--transition-fast);
}

.ayah-picker-cell:active {
  transform: scale(0.92);
}

.ayah-picker-cell--active {
  background: var(--color-primary) !important;
  color: white !important;
  box-shadow: 0 2px 8px rgba(5, 150, 105, 0.3);
}

/* Content — 52% */
.remote-content {
  flex: 0 0 52%;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px 20px;
  cursor: pointer;
  -webkit-user-select: none;
  user-select: none;
  overflow: hidden;
}

.remote-hidden {
  text-align: center;
  animation: scaleIn 0.3s ease;
  touch-action: none;
}

.remote-hidden__icon {
  width: 88px;
  height: 88px;
  margin: 0 auto 16px;
  border-radius: 50%;
  background: var(--color-bg-subtle);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--color-text-muted);
  box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.03);
}

.remote-hidden__text {
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--color-text-secondary);
  letter-spacing: 0.1em;
  margin-bottom: 8px;
}

.remote-hidden__hint {
  font-size: 0.875rem;
  color: var(--color-text-muted);
}

.remote-revealed {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 100%;
  max-height: 100%;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
  padding: 8px 0;
}

.remote-ayah-card {
  background: #FAF8F2;
  border: 2px solid #E6DFCE;
  border-radius: var(--radius-lg);
  padding: 24px 20px;
  margin-bottom: 16px;
  box-shadow: inset 0 1px 3px rgba(0,0,0,0.02);
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 100%;
}

.remote-ayah-number-badge {
  background: var(--color-primary-dark);
  color: white;
  padding: 4px 12px;
  border-radius: var(--radius-full);
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 16px;
  box-shadow: 0 2px 5px rgba(4, 120, 87, 0.2);
}

.remote-ayah-text {
  color: var(--color-text);
  padding: 0 4px;
  font-size: clamp(2rem, 7vw, 3.5rem);
  line-height: 2.2;
}

.ayah-circle {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border: 2px solid var(--color-primary-light);
  border-radius: 50%;
  font-family: var(--font-ui);
  font-size: 0.8125rem;
  font-weight: 700;
  color: var(--color-primary-dark);
  vertical-align: middle;
  margin-right: 12px;
  background: rgba(16, 185, 129, 0.05);
}

.ayah-circle-num {
  line-height: 1;
}

.remote-translation {
  font-size: 0.9375rem;
  color: var(--color-text-secondary);
  line-height: 1.6;
  margin-bottom: 20px;
  text-align: center;
  padding: 0 8px;
}

.remote-hide-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 24px;
  border-radius: var(--radius-full);
  background: var(--color-bg-subtle);
  color: var(--color-text-secondary);
  font-size: 0.875rem;
  font-weight: 600;
  border: 1px solid rgba(0, 0, 0, 0.08);
  transition: all var(--transition-fast);
  box-shadow: var(--shadow-sm);
}

.remote-hide-btn:active {
  background: var(--color-primary-50);
  color: var(--color-primary);
  border-color: var(--color-primary-light);
}

/* Review Buttons — 16% */
.remote-review {
  flex: 0 0 16%;
  display: flex;
  gap: 12px;
  padding: 8px 16px;
  align-items: center;
}

.review-btn {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 6px;
  border-radius: var(--radius-lg);
  min-height: 72px;
  font-weight: 700;
  font-size: 0.9375rem;
  transition: all var(--transition-fast);
  position: relative;
  overflow: hidden;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
}

.review-btn:active {
  transform: scale(0.94);
}

.review-btn:disabled {
  opacity: 0.5;
}

.review-btn__icon {
  font-size: 1.35rem;
  font-weight: 800;
}

.review-btn__label {
  font-size: 0.8125rem;
  text-transform: uppercase;
  letter-spacing: 0.02em;
}

.review-btn--forgot {
  background: var(--color-forgot-bg);
  color: var(--color-forgot);
  border: 2px solid var(--color-forgot);
}

.review-btn--forgot:active {
  background: var(--color-forgot);
  color: white;
  box-shadow: 0 0 14px rgba(244, 63, 94, 0.4);
}

.review-btn--doubtful {
  background: var(--color-doubtful-bg);
  color: #B45309;
  border: 2px solid var(--color-doubtful);
}

.review-btn--doubtful:active {
  background: var(--color-doubtful);
  color: white;
  box-shadow: 0 0 14px rgba(245, 158, 11, 0.4);
}

.review-btn--fluent {
  background: var(--color-fluent-bg);
  color: var(--color-fluent);
  border: 2px solid var(--color-fluent);
}

.review-btn--fluent:active {
  background: var(--color-fluent);
  color: white;
  box-shadow: 0 0 14px rgba(16, 185, 129, 0.4);
}

/* Navigation — 20% */
.remote-nav {
  flex: 0 0 20%;
  display: flex;
  gap: 10px;
  padding: 8px 16px;
  padding-bottom: calc(var(--safe-bottom) + 16px);
  align-items: stretch;
}

.nav-btn {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  border-radius: var(--radius-lg);
  font-weight: 700;
  font-size: 1.15rem;
  min-height: 60px;
  transition: all var(--transition-fast);
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}

.nav-btn:active {
  transform: scale(0.97);
}

.nav-btn:disabled {
  opacity: 0.3;
  pointer-events: none;
}

.nav-btn--prev {
  background: #FFFFFF;
  color: var(--color-text-secondary);
  border: 2px solid rgba(0, 0, 0, 0.08);
}

.nav-btn--prev:active {
  background: #F3F4F6;
}

.nav-btn--skip {
  background: var(--color-primary);
  color: white;
  border: 2px solid var(--color-primary-dark);
}

.nav-btn--skip:active {
  background: var(--color-primary-dark);
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
}

.picker-sheet__close {
  color: var(--color-primary);
  font-weight: 700;
  font-size: 1rem;
  padding: 4px 8px;
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
}

.picker-wheel__item:active {
  background: var(--color-bg-subtle);
}

.picker-wheel__item--active {
  background: var(--color-primary-50) !important;
  color: var(--color-primary-dark);
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
}

.picker-wheel__item--active .picker-wheel__number {
  background: rgba(5, 150, 105, 0.12);
  color: var(--color-primary);
}

.picker-wheel__latin {
  font-size: 1rem;
  font-weight: 600;
}

.picker-wheel__arabic {
  margin-left: auto;
  font-family: var(--font-arabic);
  font-size: 1.35rem;
  color: var(--color-primary-dark);
}

/* Flash Feedback Overlay */
.remote-flash {
  position: absolute;
  inset: 0;
  pointer-events: none;
  z-index: 50;
  animation: flashPulse 0.4s ease-out forwards;
}

.remote-flash--fluent { background: rgba(16, 185, 129, 0.15); }
.remote-flash--doubtful { background: rgba(245, 158, 11, 0.15); }
.remote-flash--forgot { background: rgba(244, 63, 94, 0.15); }

@keyframes flashPulse {
  0% { opacity: 0; }
  30% { opacity: 1; }
  100% { opacity: 0; }
}

/* Transitions */
.ayah-enter-active,
.ayah-leave-active {
  transition: all 0.25s ease;
}

.ayah-enter-from {
  opacity: 0;
  transform: scale(0.95);
}

.ayah-leave-to {
  opacity: 0;
  transform: scale(0.95);
}

.flash-enter-active { animation: flashPulse 0.4s ease-out; }
.flash-leave-active { transition: opacity 0.1s; }
.flash-leave-to { opacity: 0; }

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

/* Desktop: center as card */
@media (min-width: 768px) {
  .remote-page {
    border-left: 1px solid rgba(0, 0, 0, 0.08);
    border-right: 1px solid rgba(0, 0, 0, 0.08);
    box-shadow: var(--shadow-lg);
  }
}

/* Tajweed Color Coding Rules */
:deep(.tajweed-h),
:deep(.tajweed-s),
:deep(.tajweed-l) {
  color: #AAAAAA !important; /* Silent/Wasl - Grey */
}

:deep(.tajweed-n) {
  color: #537FFF !important; /* Madda Normal - Light Blue */
}

:deep(.tajweed-p) {
  color: #4050FF !important; /* Madda Permissible - Blue */
}

:deep(.tajweed-m) {
  color: #000EBC !important; /* Madda Necessary - Dark Blue */
}

:deep(.tajweed-o) {
  color: #2144C1 !important; /* Madda Obligatory - Royal Blue */
}

:deep(.tajweed-q) {
  color: #DD0008 !important; /* Qalaqah - Red */
  font-weight: 700;
}

:deep(.tajweed-f),
:deep(.tajweed-c) {
  color: #9400A8 !important; /* Ikhfa - Purple */
}

:deep(.tajweed-i) {
  color: #26BFFD !important; /* Iqlab - Cyan */
}

:deep(.tajweed-g) {
  color: #169777 !important; /* Ghunnah/Idgham with Ghunnah - Teal/Green */
  font-weight: 700;
}

:deep(.tajweed-u) {
  color: #169200 !important; /* Idgham without Ghunnah - Dark Green */
}
</style>
