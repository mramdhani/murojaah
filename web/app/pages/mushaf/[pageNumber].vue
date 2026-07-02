<template>
  <div class="mushaf-page">
    <header class="mushaf-header">
      <button type="button" class="mushaf-header__back" aria-label="Kembali" @click="router.back()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
      </button>

      <div class="mushaf-header__main">
        <button type="button" class="mushaf-header__mode" @click="openModeDrawer">
          <span>Mushaf Hafalan</span>
          <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 8 4 4 4-4"/></svg>
        </button>
        <button type="button" class="mushaf-header__title" @click="openNavigator">
          <strong>{{ currentSurahTitle }}</strong>
          <small>Hal {{ pageNumber }} <span>·</span> Juz {{ juzLabel }}</small>
        </button>
      </div>

      <button type="button" class="mushaf-header__browse" aria-label="Pilih surat, ayat, atau halaman" @click="openNavigator">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
          <path d="M4 6h16M4 12h16M4 18h10"/>
          <circle cx="18" cy="18" r="2.5"/>
        </svg>
      </button>
    </header>
    <main class="mushaf-content">
      <section
        ref="viewportRef"
        class="mushaf-viewport"
        :class="{ 'mushaf-viewport--dragging': swipeStartX !== null }"
        aria-label="Halaman mushaf interaktif. Geser kiri atau kanan untuk berpindah halaman."
        @pointerdown="handlePointerDown"
        @pointermove="handlePointerMove"
        @pointerup="handlePointerUp"
        @pointercancel="cancelSwipe"
      >
        <div class="mushaf-track" :class="{ 'mushaf-track--animating': swipeAnimating }" :style="trackStyle">
          <div v-for="(slidePage, index) in carouselPages" :key="slidePage + '-' + index" class="mushaf-slide">
            <img
              :src="localImageUrl(slidePage)"
              :alt="'Mushaf Al-Quran halaman ' + slidePage"
              class="mushaf-image"
              decoding="async"
              :fetchpriority="index === 1 ? 'high' : 'low'"
              @load="index === 1 && handleImageLoad()"
              @error="index === 1 && handleImageError()"
            >
          </div>
        </div>

        <div v-if="!hasRenderedPage && !imageError" class="image-loading" aria-hidden="true">
          <span class="mushaf-state__spinner"></span>
          <small>Memuat mushaf...</small>
        </div>

        <div v-if="imageError" class="image-error">
          <strong>Aset halaman belum tersedia</strong>
          <small>Jalankan pengunduh aset Mushaf lokal.</small>
        </div>

        <div v-if="hasRenderedPage && !imageError && !isSwipeActive" class="line-masks" aria-label="Mask hafalan per baris">
          <button
            v-for="line in lineCount"
            :key="line"
            type="button"
            class="line-mask"
            :class="{ 'line-mask--revealed': revealedLines[line - 1] }"
            :aria-label="(revealedLines[line - 1] ? 'Sembunyikan baris ' : 'Tampilkan baris ') + line"
            @click="toggleLine(line - 1)"
          ></button>
        </div>
      </section>
    </main>

    <section class="mushaf-player" aria-label="Pemutar murottal">
      <button type="button" class="mushaf-player__play" :aria-label="isPlaying ? 'Jeda murottal' : 'Putar murottal'" @click="togglePlayer">
        <svg v-if="!isPlaying" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="m8 5 11 7-11 7V5Z"/></svg>
        <svg v-else viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 5h4v14H7zM13 5h4v14h-4z"/></svg>
      </button>
      <button type="button" class="mushaf-player__qari-select" @click="openQariPicker">
        <img :src="activeQari.image" :alt="activeQariName">
        <span class="mushaf-player__info">
          <strong>{{ activeQariName }}</strong>
          <small>{{ playerAyahLabel }}</small>
          <span><i :style="{ width: playerProgress + '%' }"></i></span>
        </span>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg>
      </button>
    </section>

    <Transition name="sheet">
      <div v-if="showQariPicker" class="qari-overlay" @click="closeQariPicker">
        <section class="qari-sheet" role="dialog" aria-modal="true" aria-labelledby="qari-picker-title" @click.stop>
          <div class="qari-sheet__handle"></div>
          <header class="qari-sheet__header">
            <div>
              <span>Murottal</span>
              <h2 id="qari-picker-title">Pilih Qori</h2>
              <p>Pilih suara untuk menemani murojaah</p>
            </div>
            <button type="button" aria-label="Tutup pilihan qori" @click="closeQariPicker">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 6 12 12M18 6 6 18"/></svg>
            </button>
          </header>
          <div class="qari-list">
            <button v-for="qari in qariList" :key="qari.id" type="button" class="qari-item" :class="{ 'qari-item--active': qari.id === selectedQari }" @click="selectQari(qari.id)">
              <img :src="qari.image" :alt="qari.name" loading="lazy">
              <span><strong>{{ qari.name }}</strong><small>{{ qari.country }}</small></span>
              <svg v-if="qari.id === selectedQari" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>
            </button>
          </div>
        </section>
      </div>
    </Transition>

    <Transition name="sheet">
      <div v-if="navigatorOpen" class="navigator-overlay" @click="closeNavigator">
        <section class="navigator-sheet" role="dialog" aria-modal="true" aria-labelledby="navigator-title" @click.stop>
          <div class="navigator-sheet__handle"></div>

          <div class="navigator-sheet__content">
            <header class="navigator-sheet__header">
              <div>
                <span>Mushaf Hafalan</span>
                <h2 id="navigator-title">Pilih Bacaan</h2>
                <p>Surat, ayat, atau halaman yang ingin dibuka</p>
              </div>
              <button type="button" aria-label="Tutup navigasi" @click="closeNavigator">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="m6 6 12 12M18 6 6 18"/>
                </svg>
              </button>
            </header>

            <form class="navigator-dynamic" @submit.prevent="openNavigationTarget">
              <div class="navigator-field navigator-field--wide">
                <label for="navigation-type">Tampilan</label>
                <div class="navigator-select-wrap">
                  <select id="navigation-type" v-model="navigationType" @change="syncNavigationSelection">
                    <option value="surah">Surat</option>
                    <option value="juz">Juz</option>
                    <option value="hizb">Hizb</option>
                    <option value="manzil">Manzil</option>
                    <option value="page">Halaman</option>
                  </select>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m8 10 4 4 4-4"/></svg>
                </div>
              </div>

              <template v-if="navigationType === 'surah'">
                <div class="navigator-fields-row">
                  <div class="navigator-field navigator-field--surah">
                    <label>Surat</label>
                    <button type="button" class="navigator-selector" @click="showSurahPicker = true">
                      <span><strong v-if="selectedSurah">{{ selectedSurah.number }}. {{ selectedSurah.name_latin }}</strong><strong v-else>Memuat...</strong><small v-if="selectedSurah">{{ selectedSurah.total_ayah }} ayat</small></span>
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m8 10 4 4 4-4"/></svg>
                    </button>
                  </div>
                  <div class="navigator-field navigator-field--ayah">
                    <label>Ayat</label>
                    <button type="button" class="navigator-selector" @click="showAyahPicker = true">
                      <span><strong>{{ selectedAyah }}</strong></span>
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m8 10 4 4 4-4"/></svg>
                    </button>
                  </div>
                </div>
              </template>

              <div v-else class="navigator-field navigator-field--wide">
                <label>{{ navigationTypeLabel }} <span>{{ navigationAvailability }}</span></label>
                <button type="button" class="navigator-selector navigator-selector--section" @click="openSectionPicker">
                  <span><strong>{{ sectionDisplayValue }}</strong></span>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m8 10 4 4 4-4"/></svg>
                </button>
              </div>

              <button type="submit" class="navigator-primary" :disabled="navigatorLoading || (navigationType === 'surah' && !selectedSurah)">
                {{ navigatorLoading ? 'Menyiapkan...' : navigationButtonLabel }}
              </button>
            </form>

            <p v-if="navigatorError" class="navigator-error">{{ navigatorError }}</p>
          </div>

          <Transition name="picker">
            <div v-if="showSurahPicker" class="surah-picker">
              <header class="surah-picker__header">
                <button type="button" aria-label="Kembali ke navigasi" @click="showSurahPicker = false">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m15 18-6-6 6-6"/>
                  </svg>
                </button>
                <div>
                  <span>Daftar Surat</span>
                  <strong>Pilih surat</strong>
                </div>
              </header>

              <label class="surah-picker__search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                  <circle cx="11" cy="11" r="7"/>
                  <path d="m20 20-4-4"/>
                </svg>
                <input v-model="surahSearch" type="search" placeholder="Cari nama atau nomor surat">
              </label>

              <div class="surah-picker__list">
                <button
                  v-for="surah in filteredSurahOptions"
                  :key="surah.id"
                  type="button"
                  class="surah-picker__item"
                  :class="{ 'surah-picker__item--active': surah.id === selectedSurahId }"
                  @click="chooseSurah(surah)"
                >
                  <span class="surah-picker__number">{{ surah.number }}</span>
                  <span class="surah-picker__names">
                    <strong>{{ surah.name_latin }}</strong>
                    <small>{{ surah.total_ayah }} ayat</small>
                  </span>
                  <span class="surah-picker__arabic">{{ surah.name_arabic }}</span>
                  <svg v-if="surah.id === selectedSurahId" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                    <path d="m5 10 3 3 7-7"/>
                  </svg>
                </button>
              </div>
            </div>
          </Transition>
          <Transition name="picker">
            <div v-if="showAyahPicker" class="surah-picker ayah-picker">
              <header class="surah-picker__header">
                <button type="button" aria-label="Kembali ke navigasi" @click="showAyahPicker = false">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m15 18-6-6 6-6"/>
                  </svg>
                </button>
                <div>
                  <span>{{ selectedSurah?.name_latin || 'Surat' }}</span>
                  <strong>Pilih ayat</strong>
                </div>
              </header>

              <p class="ayah-picker__summary">
                Tersedia {{ selectedSurahTotalAyah }} ayat
              </p>

              <div class="ayah-picker__grid">
                <button
                  v-for="ayah in availableAyahs"
                  :key="ayah"
                  type="button"
                  :class="{ 'ayah-picker__number--active': ayah === selectedAyah }"
                  :aria-label="'Pilih ayat ' + ayah"
                  @click="chooseAyah(ayah)"
                >
                  {{ ayah }}
                </button>
              </div>
            </div>
          </Transition>
          <Transition name="picker">
            <div v-if="showSectionPicker" class="surah-picker section-picker">
              <header class="surah-picker__header">
                <button type="button" aria-label="Kembali ke navigasi" @click="showSectionPicker = false">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
                </button>
                <div>
                  <span>{{ navigationAvailability }}</span>
                  <strong>Pilih {{ navigationTypeLabel }}</strong>
                </div>
              </header>

              <p class="ayah-picker__summary">{{ sectionPickerHint }}</p>
              <div ref="sectionGridRef" class="section-picker__grid">
                <button
                  v-for="option in sectionOptions"
                  :key="option.value"
                  type="button"
                  :data-section-value="option.value"
                  :class="{ 'section-picker__number--active': option.value === selectedSection }"
                  :aria-label="'Pilih ' + navigationTypeLabel + ' ' + option.value"
                  @click="chooseSection(option.value)"
                >
                  {{ option.label }}
                </button>
              </div>
            </div>
          </Transition>
        </section>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
interface MushafSurah {
  id: number
  number: number
  name_latin: string
  name_arabic: string
}

interface MushafPageData {
  page_number: number
  juz: number[]
  surahs: MushafSurah[]
  ayahs: Array<{
    id: number
    surah_id: number
    verse_key: string
    ayah_number: number
    page: number
    progress_status: string
  }>
}

interface SurahOption extends MushafSurah {
  total_ayah: number
  page_start: number | null
}

interface AyahLookup {
  page: number
}

const route = useRoute()
const router = useRouter()
const { apiFetch } = useApi()
const { open: openMurojaahDrawer } = useMurojaahDrawer()

const pageData = ref<MushafPageData | null>(null)
const surahOptions = ref<SurahOption[]>([])
const imageLoaded = ref(false)
const hasRenderedPage = ref(false)
const imageError = ref(false)
const lineCount = 15
const revealedLines = ref<boolean[]>(Array(lineCount).fill(true))
const viewportRef = ref<HTMLElement | null>(null)
const swipeStartX = ref<number | null>(null)
const swipeStartTime = ref(0)
const swipeOffset = ref(0)
const swipeAnimating = ref(false)
const suppressNextLineTap = ref(false)

const navigatorOpen = ref(false)
const navigatorLoading = ref(false)
const navigatorError = ref('')
const selectedSurahId = ref(1)
const selectedAyah = ref(1)
const directPage = ref(1)
const showSurahPicker = ref(false)
const showAyahPicker = ref(false)
const showSectionPicker = ref(false)
const sectionGridRef = ref<HTMLElement | null>(null)
const surahSearch = ref('')
const availablePages = Array.from({ length: 604 }, (_, index) => index + 1)
type NavigationType = 'surah' | 'juz' | 'hizb' | 'manzil' | 'page'
const navigationType = ref<NavigationType>('surah')
const selectedSection = ref(1)

const juzStartPages = [1, 22, 42, 62, 82, 102, 121, 142, 162, 182, 201, 222, 242, 262, 282, 302, 322, 342, 362, 382, 402, 422, 442, 462, 482, 502, 522, 542, 562, 582]
const hizbStartPages = [1, 11, 22, 32, 42, 51, 62, 72, 82, 92, 102, 112, 121, 132, 142, 151, 162, 173, 182, 192, 201, 212, 222, 231, 242, 252, 262, 272, 282, 292, 302, 312, 322, 332, 342, 352, 362, 371, 382, 392, 402, 413, 422, 431, 442, 451, 462, 472, 482, 491, 502, 513, 522, 531, 542, 553, 562, 572, 582, 591]
const manzilStartPages = [1, 106, 208, 282, 367, 440, 518]

const qariList = [
  { id: 'Maher_AlMuaiqly_64kbps', name: 'Maher Al-Muaiqly', country: 'Arab Saudi', image: '/images/qori/syekh-maher-al-muaiqly.png' },
  { id: 'Alafasy_64kbps', name: 'Mishary Alafasy', country: 'Kuwait', image: '/images/qori/syekh-mishary-al-fasy.png' },
  { id: 'Ghamadi_40kbps', name: 'Saad Al-Ghamdi', country: 'Arab Saudi', image: '/images/qori/syekh-saad-al-ghamdi.png' },
  { id: 'Husary_64kbps', name: 'Mahmoud Al-Husary', country: 'Mesir', image: '/images/qori/syekh-mahmoud-al-husary.png' },
]
const selectedQari = useCookie<string>('selected_qari', { default: () => 'Maher_AlMuaiqly_64kbps', maxAge: 60 * 60 * 24 * 365, path: '/' })
const showQariPicker = ref(false)
const isPlaying = ref(false)
const playerAyahIndex = ref(0)
const playerCurrentTime = ref(0)
const playerDuration = ref(0)
let playerAudio: HTMLAudioElement | null = null

const pageNumber = computed(() => {
  const value = Number(route.params.pageNumber)
  return Number.isFinite(value) ? Math.min(604, Math.max(1, Math.trunc(value))) : 1
})

const carouselPages = computed(() => [
  Math.max(1, pageNumber.value - 1),
  pageNumber.value,
  Math.min(604, pageNumber.value + 1),
])
const trackStyle = computed(() => ({ transform: `translate3d(calc(-33.333333% + ${swipeOffset.value}px), 0, 0)` }))
const isSwipeActive = computed(() => swipeAnimating.value || Math.abs(swipeOffset.value) > 1)
const primarySurah = computed(() => pageData.value?.surahs[0] || null)
const currentSurahTitle = computed(() =>
  primarySurah.value
    ? primarySurah.value.number + '. ' + primarySurah.value.name_latin
    : 'Al-Quran'
)
const juzLabel = computed(() => pageData.value?.juz.join('-') || '-')
const selectedSurah = computed(() =>
  surahOptions.value.find(surah => surah.id === selectedSurahId.value)
)
const selectedSurahTotalAyah = computed(() => selectedSurah.value?.total_ayah || 1)
const availableAyahs = computed(() =>
  Array.from({ length: selectedSurahTotalAyah.value }, (_, index) => index + 1)
)
const filteredSurahOptions = computed(() => {
  const query = surahSearch.value.trim().toLowerCase()
  if (!query) return surahOptions.value

  return surahOptions.value.filter(surah =>
    surah.name_latin.toLowerCase().includes(query) ||
    surah.name_arabic.includes(query) ||
    String(surah.number) === query
  )
})

const navigationTypeLabel = computed(() => ({ surah: 'Surat', juz: 'Juz', hizb: 'Hizb', manzil: 'Manzil', page: 'Halaman' }[navigationType.value]))
const navigationAvailability = computed(() => ({ surah: '114 surat', juz: '1–30 tersedia', hizb: '1–60 tersedia', manzil: '1–7 tersedia', page: '1–604 tersedia' }[navigationType.value]))
const navigationButtonLabel = computed(() => navigationType.value === 'surah' ? 'Buka ayat' : `Buka ${navigationTypeLabel.value}`)
const sectionOptions = computed(() => {
  const count = { juz: 30, hizb: 60, manzil: 7, page: 604 }[navigationType.value]
  if (!count) return []
  return Array.from({ length: count }, (_, index) => ({ value: index + 1, label: String(index + 1) }))
})
const sectionDisplayValue = computed(() => navigationType.value === 'page' ? String(selectedSection.value) : `${navigationTypeLabel.value} ${selectedSection.value}`)
const sectionPickerHint = computed(() => navigationType.value === 'page' ? 'Pilih nomor tujuan' : `Pilih ${navigationTypeLabel.value.toLowerCase()} tujuan`)
const activeQari = computed(() => qariList.find(qari => qari.id === selectedQari.value) || qariList[0])
const activeQariName = computed(() => activeQari.value.name)
const playerAyahs = computed(() => pageData.value?.ayahs || [])
const playerAyah = computed(() => playerAyahs.value[playerAyahIndex.value] || playerAyahs.value[0])
const playerAyahLabel = computed(() => playerAyah.value ? `Ayat ${playerAyah.value.verse_key} · Halaman ${pageNumber.value}` : `Halaman ${pageNumber.value}`)
const playerProgress = computed(() => playerDuration.value > 0 ? Math.min(100, (playerCurrentTime.value / playerDuration.value) * 100) : 0)

const findSectionForPage = (starts: number[]) => {
  let value = 1
  starts.forEach((start, index) => { if (start <= pageNumber.value) value = index + 1 })
  return value
}
const localImageUrl = (page: number) =>
  '/mushaf/madinah-classic/page-' + String(page).padStart(3, '0') + '.jpg'

const mushafImageUrl = computed(() => localImageUrl(pageNumber.value))

const toggleLine = (index: number) => {
  if (suppressNextLineTap.value) {
    suppressNextLineTap.value = false
    return
  }

  revealedLines.value[index] = !revealedLines.value[index]
  revealedLines.value = [...revealedLines.value]
  if (typeof navigator !== 'undefined' && navigator.vibrate) navigator.vibrate(18)
}

const handleImageLoad = () => {
  imageLoaded.value = true
  hasRenderedPage.value = true
  imageError.value = false
}

const handleImageError = () => {
  imageLoaded.value = false
  imageError.value = true
}

const prefetchPages = () => {
  if (typeof Image === 'undefined') return
  for (const page of [
    pageNumber.value - 2,
    pageNumber.value - 1,
    pageNumber.value + 1,
    pageNumber.value + 2,
  ]) {
    if (page < 1 || page > 604) continue
    const image = new Image()
    image.src = localImageUrl(page)
  }
}

const loadPageMetadata = async () => {
  try {
    const response = await apiFetch<{ data: MushafPageData }>('/mushaf/pages/' + pageNumber.value)
    pageData.value = response.data
  } catch (error) {
    console.error('Failed to load Mushaf page metadata:', error)
  }
}

const loadSurahOptions = async () => {
  if (surahOptions.value.length > 0) return
  try {
    const response = await apiFetch<{ data: SurahOption[] }>('/surahs')
    surahOptions.value = response.data
  } catch (error) {
    console.error('Failed to load Surah options:', error)
  }
}

const handlePageChange = () => {
  resetPlayer()
  imageLoaded.value = false
  imageError.value = false
  revealedLines.value = Array(lineCount).fill(true)
  directPage.value = pageNumber.value
  loadPageMetadata()
  nextTick(prefetchPages)
}

const goToPage = (page: number) => {
  if (page < 1 || page > 604 || page === pageNumber.value) return
  return router.push({ path: '/mushaf/' + page, query: route.query })
}

const settleSwipe = () => {
  swipeAnimating.value = true
  swipeOffset.value = 0
  window.setTimeout(() => { swipeAnimating.value = false }, 240)
}

const handlePointerDown = (event: PointerEvent) => {
  if (swipeAnimating.value) return
  swipeStartX.value = event.clientX
  swipeStartTime.value = performance.now()
  swipeOffset.value = 0
  suppressNextLineTap.value = false
  ;(event.currentTarget as HTMLElement)?.setPointerCapture?.(event.pointerId)
}

const handlePointerMove = (event: PointerEvent) => {
  if (swipeStartX.value === null || swipeAnimating.value) return
  let distance = event.clientX - swipeStartX.value
  const pullingPastFirst = pageNumber.value === 1 && distance > 0
  const pullingPastLast = pageNumber.value === 604 && distance < 0
  if (pullingPastFirst || pullingPastLast) distance *= .24
  swipeOffset.value = distance
  if (Math.abs(distance) > 8) suppressNextLineTap.value = true
}

const handlePointerUp = async (event: PointerEvent) => {
  if (swipeStartX.value === null || swipeAnimating.value) return
  const distance = event.clientX - swipeStartX.value
  const elapsed = Math.max(1, performance.now() - swipeStartTime.value)
  const velocity = distance / elapsed
  const viewportWidth = viewportRef.value?.clientWidth || window.innerWidth
  const direction = distance < 0 ? 1 : -1
  const targetPage = pageNumber.value + direction
  const canMove = targetPage >= 1 && targetPage <= 604
  const shouldMove = canMove && (Math.abs(distance) > viewportWidth * .18 || Math.abs(velocity) > .42)
  swipeStartX.value = null

  if (!shouldMove) {
    settleSwipe()
    return
  }

  swipeAnimating.value = true
  swipeOffset.value = direction > 0 ? -viewportWidth : viewportWidth
  if (typeof navigator !== 'undefined' && navigator.vibrate) navigator.vibrate(12)

  window.setTimeout(async () => {
    await goToPage(targetPage)
    swipeAnimating.value = false
    swipeOffset.value = 0
  }, 235)
}

const cancelSwipe = () => {
  if (swipeStartX.value === null) return
  swipeStartX.value = null
  settleSwipe()
}

const openModeDrawer = () => openMurojaahDrawer('learning', 'mushaf')
const openQariPicker = () => { showQariPicker.value = true }
const closeQariPicker = () => { showQariPicker.value = false }
const selectQari = (qariId: string) => {
  selectedQari.value = qariId
  resetPlayer()
  closeQariPicker()
}
const openAyahMode = (mode: 'learning' | 'listening') => {
  const ayah = pageData.value?.ayahs[0]
  if (!ayah) return
  router.push({ path: `/remote/${ayah.surah_id}/${ayah.ayah_number}`, query: { mode } })
}

const openSectionPicker = async () => {
  showSectionPicker.value = true
  await nextTick()
  const active = sectionGridRef.value?.querySelector<HTMLElement>(`[data-section-value="${selectedSection.value}"]`)
  active?.scrollIntoView({ block: 'center', behavior: 'auto' })
}

const chooseSection = (value: number) => {
  selectedSection.value = value
  showSectionPicker.value = false
}
const syncNavigationSelection = () => {
  showSurahPicker.value = false
  showAyahPicker.value = false
  if (navigationType.value === 'page') selectedSection.value = pageNumber.value
  if (navigationType.value === 'juz') selectedSection.value = pageData.value?.juz[0] || findSectionForPage(juzStartPages)
  if (navigationType.value === 'hizb') selectedSection.value = findSectionForPage(hizbStartPages)
  if (navigationType.value === 'manzil') selectedSection.value = findSectionForPage(manzilStartPages)
}

const openNavigationTarget = async () => {
  if (navigationType.value === 'surah') return openSelectedAyah()
  const starts = navigationType.value === 'juz' ? juzStartPages : navigationType.value === 'hizb' ? hizbStartPages : navigationType.value === 'manzil' ? manzilStartPages : availablePages
  const targetPage = starts[Math.max(0, selectedSection.value - 1)] || 1
  closeNavigator()
  goToPage(targetPage)
}

const playerAudioUrl = () => {
  const ayah = playerAyah.value
  if (!ayah) return ''
  const [surah, number] = ayah.verse_key.split(':').map(Number)
  return `https://everyayah.com/data/${selectedQari.value}/${String(surah).padStart(3, '0')}${String(number).padStart(3, '0')}.mp3`
}

const stopPlayer = () => {
  if (playerAudio) {
    playerAudio.pause()
    playerAudio.removeAttribute('src')
    playerAudio.load()
  }
  isPlaying.value = false
  playerCurrentTime.value = 0
  playerDuration.value = 0
}

const playPlayerAyah = () => {
  const src = playerAudioUrl()
  if (!src) return
  if (!playerAudio) playerAudio = new Audio()
  playerAudio.src = src
  playerAudio.ontimeupdate = () => { playerCurrentTime.value = playerAudio?.currentTime || 0 }
  playerAudio.onloadedmetadata = () => { playerDuration.value = Number.isFinite(playerAudio?.duration) ? (playerAudio?.duration || 0) : 0 }
  playerAudio.onplay = () => { isPlaying.value = true }
  playerAudio.onpause = () => { isPlaying.value = false }
  playerAudio.onerror = () => { isPlaying.value = false }
  playerAudio.onended = () => {
    if (playerAyahIndex.value < playerAyahs.value.length - 1) {
      playerAyahIndex.value += 1
      playPlayerAyah()
    } else {
      isPlaying.value = false
      playerCurrentTime.value = playerDuration.value
    }
  }
  playerAudio.play().catch(() => { isPlaying.value = false })
}

const togglePlayer = () => {
  if (isPlaying.value && playerAudio) {
    playerAudio.pause()
    return
  }
  if (playerAudio?.src && playerAudio.currentTime > 0 && playerAudio.currentTime < playerAudio.duration) {
    playerAudio.play().catch(() => { isPlaying.value = false })
    return
  }
  playPlayerAyah()
}

const resetPlayer = () => {
  stopPlayer()
  playerAyahIndex.value = 0
}
const openNavigator = () => {
  navigatorError.value = ''
  showSurahPicker.value = false
  showAyahPicker.value = false
  surahSearch.value = ''
  directPage.value = pageNumber.value
  syncNavigationSelection()
  if (primarySurah.value) {
    selectedSurahId.value = primarySurah.value.id
    const currentAyah = pageData.value?.ayahs[0]?.ayah_number
    selectedAyah.value = currentAyah || 1
  }
  navigatorOpen.value = true
  loadSurahOptions()
}

const closeNavigator = () => {
  navigatorOpen.value = false
  showSurahPicker.value = false
  showAyahPicker.value = false
}

const chooseSurah = (surah: SurahOption) => {
  selectedSurahId.value = surah.id
  selectedAyah.value = 1
  showSurahPicker.value = false
  showAyahPicker.value = false
  surahSearch.value = ''
}

const chooseAyah = (ayah: number) => {
  selectedAyah.value = ayah
  showAyahPicker.value = false
}

const syncAyahLimit = () => {
  selectedAyah.value = Math.min(Math.max(1, selectedAyah.value || 1), selectedSurahTotalAyah.value)
}

const openSelectedAyah = async () => {
  syncAyahLimit()
  navigatorLoading.value = true
  navigatorError.value = ''

  try {
    const response = await apiFetch<{ data: AyahLookup }>(
      '/surahs/' + selectedSurahId.value + '/ayahs/' + selectedAyah.value
    )
    if (!response.data.page) throw new Error('Page mapping missing')
    closeNavigator()
    goToPage(response.data.page)
  } catch (error) {
    console.error('Failed to find ayah page:', error)
    navigatorError.value = 'Ayat belum dapat ditemukan.'
  } finally {
    navigatorLoading.value = false
  }
}

const openDirectPage = () => {
  const page = Math.min(604, Math.max(1, Number(directPage.value) || 1))
  closeNavigator()
  goToPage(page)
}

watch(() => route.params.pageNumber, handlePageChange)

onMounted(() => {
  handlePageChange()
  loadSurahOptions()
})

onBeforeUnmount(stopPlayer)

useHead({ title: computed(() => 'Mushaf Hafalan - Halaman ' + pageNumber.value) })
</script>

<style scoped>
.mushaf-page {
  min-height: 100dvh;
  padding-bottom: calc(72px + var(--safe-bottom));
  overflow-x: hidden;
  background: #fffefa;
}

.mushaf-header {
  position: sticky;
  top: 0;
  z-index: 20;
  min-height: 76px;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: calc(var(--safe-top) + 9px) 14px 9px;
  border-bottom: 1px solid rgba(31, 54, 45, .08);
  background: rgba(255, 255, 253, .97);
  backdrop-filter: blur(18px);
}

.mushaf-header__main {
  min-width: 0;
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  border: 0;
  padding: 0;
  color: #1d2b26;
  background: transparent;
  text-align: left;
}

.mushaf-header__edition {
  color: #087d59;
  font-size: .61rem;
  font-weight: 800;
  letter-spacing: .08em;
  text-transform: uppercase;
}

.mushaf-header__main strong {
  max-width: 100%;
  overflow: hidden;
  margin-top: 2px;
  font-size: 1rem;
  font-weight: 800;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.mushaf-header__main small {
  margin-top: 1px;
  color: #737f79;
  font-size: .68rem;
  font-weight: 600;
}

.mushaf-header__main small span {
  margin: 0 3px;
  color: #b0b6b2;
}

.mushaf-header__browse {
  width: 42px;
  height: 42px;
  display: grid;
  place-items: center;
  flex: 0 0 auto;
  border: 0;
  border-radius: 50%;
  color: #087d59;
  background: #eef7f3;
}

.mushaf-header__browse svg {
  width: 21px;
  height: 21px;
}

.mushaf-content {
  width: 100%;
  max-width: 620px;
  margin: 0 auto;
}

.mushaf-viewport {
  position: relative;
  width: 100%;
  overflow: hidden;
  aspect-ratio: 750 / 1075;
  background: #fffefa;
  touch-action: pan-y;
  user-select: none;
}

.mushaf-track {
  width: 300%;
  height: 100%;
  display: flex;
  will-change: transform;
}

.mushaf-track--animating {
  transition: transform 235ms cubic-bezier(.22, .72, .28, 1);
}

.mushaf-slide {
  width: 33.333333%;
  height: 100%;
  flex: 0 0 33.333333%;
  overflow: hidden;
  background: #fffefa;
}

.mushaf-viewport--dragging { cursor: grabbing; }

.mushaf-image {
  width: 100%;
  height: 100%;
  display: block;
  object-fit: contain;
  pointer-events: none;
  -webkit-user-drag: none;
  transform: scale(1.1);
  transform-origin: center;
}

.image-loading,
.image-error {
  position: absolute;
  inset: 0;
  display: grid;
  place-content: center;
  justify-items: center;
  gap: 10px;
  padding: 24px;
  color: #738079;
  background: #f6f4ee;
  text-align: center;
}

.image-error strong {
  color: #34463e;
  font-size: .85rem;
}

.image-loading small,
.image-error small {
  font-size: .68rem;
  font-weight: 650;
}

.mushaf-state__spinner {
  width: 28px;
  height: 28px;
  border: 3px solid #dce6e1;
  border-top-color: #087d59;
  border-radius: 50%;
  animation: spin .8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.line-masks {
  position: absolute;
  inset: 3.8% 2.8% 4%;
  display: grid;
  grid-template-rows: repeat(15, 1fr);
}

.line-mask {
  min-height: 0;
  border: 0;
  border-bottom: 1px solid rgba(130, 115, 78, .05);
  background: rgba(251, 249, 242, .99);
  cursor: pointer;
  transition: opacity .16s ease;
}

.line-mask:nth-child(odd) {
  background: rgba(247, 248, 240, .99);
}

.line-mask--revealed {
  opacity: 0;
}

.navigator-overlay {
  position: fixed;
  inset: 0;
  z-index: 1100;
  display: flex;
  align-items: flex-end;
  justify-content: center;
  background: rgba(13, 29, 23, .42);
  backdrop-filter: blur(5px);
}

.navigator-sheet {
  position: relative;
  width: 100%;
  max-width: 620px;
  min-height: 500px;
  max-height: 90dvh;
  overflow: hidden;
  padding: 10px 20px calc(24px + var(--safe-bottom));
  border-radius: 26px 26px 0 0;
  background: #fffdfa;
  box-shadow: 0 -18px 50px rgba(17, 35, 28, .2);
}

.navigator-sheet__handle {
  width: 42px;
  height: 4px;
  margin: 0 auto 15px;
  border-radius: 999px;
  background: #d5d9d6;
}

.navigator-sheet__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 18px;
}

.navigator-sheet__header span {
  color: #087d59;
  font-size: .65rem;
  font-weight: 800;
  letter-spacing: .08em;
  text-transform: uppercase;
}

.navigator-sheet__header h2 {
  margin: 2px 0 0;
  color: #1f2e28;
  font-size: 1.2rem;
}

.navigator-sheet__header p {
  margin: 3px 0 0;
  color: #7c8781;
  font-size: .68rem;
}
.navigator-sheet__header button {
  width: 38px;
  height: 38px;
  display: grid;
  place-items: center;
  border: 0;
  border-radius: 50%;
  color: #5d6863;
  background: #f0f2f0;
}

.navigator-sheet__header svg {
  width: 18px;
  height: 18px;
}

.navigator-form {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 88px;
  gap: 10px;
}

.navigator-field {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.navigator-field label {
  color: #66736d;
  font-size: .68rem;
  font-weight: 700;
}

.navigator-field select,
.navigator-field input {
  width: 100%;
  height: 46px;
  border: 1px solid #dfe4e0;
  border-radius: 12px;
  padding: 0 12px;
  outline: none;
  color: #25332d;
  background: #f8f8f5;
  font-size: .82rem;
  font-weight: 650;
}

.navigator-field select:focus,
.navigator-field input:focus {
  border-color: #42a486;
  background: #fff;
  box-shadow: 0 0 0 3px rgba(8, 125, 89, .08);
}

.navigator-field label > span {
  margin-left: 4px;
  color: #9aa29e;
  font-weight: 600;
}

.navigator-field--surah {
  min-width: 0;
}

.navigator-selector {
  width: 100%;
  height: 46px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  border: 1px solid #dfe4e0;
  border-radius: 12px;
  padding: 0 11px 0 13px;
  color: #25332d;
  background: #f8f8f5;
  text-align: left;
}

.navigator-selector > span {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 1px;
}

.navigator-selector strong {
  overflow: hidden;
  font-size: .8rem;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.navigator-selector small {
  color: #8a948f;
  font-size: .6rem;
}

.navigator-selector svg {
  width: 17px;
  height: 17px;
  flex: 0 0 auto;
  color: #78837d;
}
.navigator-primary {
  grid-column: 1 / -1;
  height: 46px;
  border: 0;
  border-radius: 13px;
  color: #fff;
  background: #087d59;
  font-size: .8rem;
  font-weight: 800;
}

.navigator-primary:disabled {
  opacity: .55;
}

.navigator-divider {
  display: flex;
  align-items: center;
  gap: 10px;
  margin: 18px 0;
  color: #98a19c;
  font-size: .65rem;
}

.navigator-divider::before,
.navigator-divider::after {
  content: '';
  height: 1px;
  flex: 1;
  background: #e7e9e7;
}

.navigator-page-form {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 46px;
  align-items: end;
  gap: 10px;
}

.navigator-page-form > button {
  width: 46px;
  height: 46px;
  display: grid;
  place-items: center;
  border: 0;
  border-radius: 12px;
  color: #087d59;
  background: #e8f5ef;
}

.navigator-page-form svg {
  width: 19px;
  height: 19px;
}

.navigator-error {
  margin: 12px 0 0;
  color: #c2414d;
  font-size: .72rem;
  text-align: center;
}

.surah-picker {
  position: absolute;
  inset: 0;
  z-index: 3;
  display: flex;
  flex-direction: column;
  min-height: 0;
  padding: 18px 18px calc(20px + var(--safe-bottom));
  background: #fffdfa;
}

.surah-picker__header {
  display: flex;
  align-items: center;
  gap: 11px;
  margin-bottom: 14px;
}

.surah-picker__header > button {
  width: 38px;
  height: 38px;
  display: grid;
  place-items: center;
  flex: 0 0 auto;
  border: 0;
  border-radius: 50%;
  color: #087d59;
  background: #edf6f2;
}

.surah-picker__header svg {
  width: 19px;
  height: 19px;
}

.surah-picker__header > div {
  display: flex;
  flex-direction: column;
  gap: 1px;
}

.surah-picker__header span {
  color: #087d59;
  font-size: .61rem;
  font-weight: 800;
  letter-spacing: .08em;
  text-transform: uppercase;
}

.surah-picker__header strong {
  color: #23332c;
  font-size: 1.05rem;
}

.surah-picker__search {
  height: 44px;
  display: flex;
  align-items: center;
  gap: 10px;
  flex: 0 0 auto;
  margin-bottom: 12px;
  border: 1px solid #e0e4e1;
  border-radius: 13px;
  padding: 0 13px;
  background: #f6f7f4;
}

.surah-picker__search svg {
  width: 18px;
  height: 18px;
  flex: 0 0 auto;
  color: #8a9590;
}

.surah-picker__search input {
  width: 100%;
  border: 0;
  outline: 0;
  color: #28362f;
  background: transparent;
  font-size: .8rem;
}

.surah-picker__list {
  min-height: 0;
  display: flex;
  flex-direction: column;
  gap: 7px;
  overflow-y: auto;
  overscroll-behavior: contain;
}

.surah-picker__item {
  min-height: 58px;
  display: grid;
  grid-template-columns: 34px minmax(0, 1fr) auto 20px;
  align-items: center;
  gap: 10px;
  flex: 0 0 auto;
  border: 1px solid #e8eae7;
  border-radius: 14px;
  padding: 8px 10px;
  color: #2b3933;
  background: #fff;
  text-align: left;
}

.surah-picker__item--active {
  border-color: #5aac91;
  background: #eff8f4;
}

.surah-picker__number {
  width: 32px;
  height: 32px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  color: #64716b;
  background: #f1f3f1;
  font-size: .7rem;
  font-weight: 800;
}

.surah-picker__item--active .surah-picker__number {
  color: #087d59;
  background: #dcefe7;
}

.surah-picker__names {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.surah-picker__names strong {
  overflow: hidden;
  font-size: .78rem;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.surah-picker__names small {
  color: #8a948f;
  font-size: .61rem;
}

.surah-picker__arabic {
  font-family: var(--font-arabic);
  color: #087d59;
  font-size: 1.02rem;
  direction: rtl;
}

.surah-picker__item > svg {
  width: 17px;
  height: 17px;
  color: #087d59;
}

.ayah-picker__summary {
  margin: 0 0 13px;
  color: #7e8983;
  font-size: .68rem;
}

.ayah-picker__grid {
  min-height: 0;
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  align-content: start;
  gap: 9px;
  overflow-y: auto;
  overscroll-behavior: contain;
  padding: 2px 1px 8px;
}

.ayah-picker__grid button {
  aspect-ratio: 1;
  display: grid;
  place-items: center;
  border: 1px solid #e2e6e3;
  border-radius: 13px;
  color: #44524c;
  background: #fff;
  font-size: .78rem;
  font-weight: 750;
}

.ayah-picker__grid button:active {
  transform: scale(.96);
}

.ayah-picker__grid .ayah-picker__number--active {
  border-color: #087d59;
  color: #fff;
  background: #087d59;
  box-shadow: 0 5px 13px rgba(8, 125, 89, .2);
}
.picker-enter-active,
.picker-leave-active {
  transition: transform .24s ease, opacity .2s ease;
}

.picker-enter-from,
.picker-leave-to {
  transform: translateY(100%);
  opacity: 0;
}
.sheet-enter-active,
.sheet-leave-active {
  transition: opacity .2s ease;
}

.sheet-enter-active .navigator-sheet,
.sheet-leave-active .navigator-sheet {
  transition: transform .24s ease;
}

.sheet-enter-from,
.sheet-leave-to {
  opacity: 0;
}

.sheet-enter-from .navigator-sheet,
.sheet-leave-to .navigator-sheet {
  transform: translateY(100%);
}

@media (min-width: 620px) {
  .mushaf-page {
    background: #f1eee7;
  }

  .mushaf-viewport {
    box-shadow: 0 12px 36px rgba(45, 37, 24, .09);
  }
}

/* Reader controls */
.mushaf-header__back {
  width: 40px;
  height: 40px;
  display: grid;
  place-items: center;
  flex: 0 0 auto;
  border: 0;
  border-radius: 50%;
  color: #167b62;
  background: transparent;
}

.mushaf-header__back:active { background: #eef5f2; }
.mushaf-header__back svg { width: 23px; height: 23px; }

.mushaf-modes {
  position: sticky;
  top: calc(var(--safe-top) + 76px);
  z-index: 19;
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 4px;
  padding: 7px 12px 8px;
  border-bottom: 1px solid rgba(31, 54, 45, .07);
  background: rgba(255, 255, 253, .96);
  backdrop-filter: blur(18px);
}

.mushaf-modes button {
  min-height: 34px;
  border: 0;
  border-radius: 10px;
  color: #748078;
  background: transparent;
  font-size: .7rem;
  font-weight: 750;
}

.mushaf-modes .mushaf-modes__active {
  color: #087d59;
  background: #e9f6f0;
  box-shadow: inset 0 0 0 1px rgba(8, 125, 89, .11);
}

.mushaf-player {
  position: fixed;
  right: 0;
  bottom: 0;
  left: 0;
  z-index: 90;
  min-height: calc(70px + var(--safe-bottom));
  display: flex;
  align-items: center;
  gap: 11px;
  max-width: 620px;
  margin: 0 auto;
  padding: 9px 16px calc(9px + var(--safe-bottom));
  border-top: 1px solid rgba(31, 54, 45, .1);
  background: rgba(255, 255, 253, .97);
  box-shadow: 0 -8px 26px rgba(28, 46, 39, .08);
  backdrop-filter: blur(18px);
}

.mushaf-player__play {
  width: 42px;
  height: 42px;
  display: grid;
  place-items: center;
  flex: 0 0 auto;
  border: 1.5px solid #159174;
  border-radius: 50%;
  color: #0c8a69;
  background: #fff;
}

.mushaf-player__play svg { width: 21px; height: 21px; }
.mushaf-player__info { min-width: 0; flex: 1; display: flex; flex-direction: column; }
.mushaf-player__info strong { overflow: hidden; color: #26362f; font-size: .78rem; text-overflow: ellipsis; white-space: nowrap; }
.mushaf-player__info small { margin-top: 1px; color: #7b8680; font-size: .62rem; font-weight: 600; }
.mushaf-player__info > span { height: 3px; overflow: hidden; margin-top: 7px; border-radius: 99px; background: #e4ebe7; }
.mushaf-player__info > span i { height: 100%; display: block; border-radius: inherit; background: #159174; transition: width .2s linear; }
.mushaf-player__qari { position: relative; width: 38px; height: 40px; flex: 0 0 38px; color: #168d70; }
.mushaf-player__qari select { position: absolute; inset: 0; width: 100%; appearance: none; border: 0; opacity: 0; cursor: pointer; }
.mushaf-player__qari svg { position: absolute; top: 50%; left: 50%; width: 19px; height: 19px; pointer-events: none; transform: translate(-50%, -50%); }
.mushaf-player__qari select option { color: #26362f; font-size: .8rem; }

.navigator-dynamic { display: flex; flex-direction: column; gap: 14px; }
.navigator-fields-row { display: grid; grid-template-columns: minmax(0, 1fr) 92px; gap: 10px; }
.navigator-field--wide { width: 100%; }
.navigator-field--ayah { min-width: 0; }
.navigator-field label span { margin-left: 4px; color: #9aa29e; font-size: .62rem; font-weight: 600; }
.navigator-select-wrap { position: relative; }
.navigator-select-wrap select {
  width: 100%;
  height: 48px;
  appearance: none;
  border: 1px solid #dce3df;
  border-radius: 13px;
  outline: none;
  padding: 0 42px 0 14px;
  color: #314039;
  background: #fbfcfa;
  font-size: .8rem;
  font-weight: 750;
}
.navigator-select-wrap select:focus { border-color: #159174; box-shadow: 0 0 0 3px rgba(21, 145, 116, .09); }
.navigator-select-wrap svg { position: absolute; top: 50%; right: 14px; width: 17px; height: 17px; color: #168d70; pointer-events: none; transform: translateY(-50%); }
.navigator-dynamic .navigator-primary { width: 100%; margin-top: 2px; }

@media (min-width: 621px) {
  .mushaf-header, .mushaf-modes { max-width: 620px; margin-right: auto; margin-left: auto; }
}
/* Compact reader header */
.mushaf-header {
  gap: 5px;
  padding-left: 4px;
}

.mushaf-header__main {
  min-width: 0;
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

.mushaf-header__mode,
.mushaf-header__title {
  max-width: 100%;
  border: 0;
  padding: 0;
  color: inherit;
  background: transparent;
  text-align: left;
}

.mushaf-header__mode {
  display: inline-flex;
  align-items: center;
  gap: 3px;
  color: #087d59;
  font-size: .61rem;
  font-weight: 800;
  letter-spacing: .08em;
  text-transform: uppercase;
}

.mushaf-header__mode svg { width: 13px; height: 13px; }
.mushaf-header__title { display: flex; flex-direction: column; }
.mushaf-header__title strong { max-width: 100%; overflow: hidden; margin-top: 2px; color: #1d2b26; font-size: 1rem; font-weight: 800; text-overflow: ellipsis; white-space: nowrap; }
.mushaf-header__title small { margin-top: 1px; color: #737f79; font-size: .68rem; font-weight: 600; }
.mushaf-header__title small span { margin: 0 3px; color: #b0b6b2; }

/* Qori selector */
.mushaf-player__qari-select {
  min-width: 0;
  flex: 1;
  display: flex;
  align-items: center;
  gap: 9px;
  border: 0;
  padding: 0;
  background: transparent;
  text-align: left;
}

.mushaf-player__qari-select > img {
  width: 36px;
  height: 36px;
  flex: 0 0 36px;
  border: 1px solid rgba(180, 146, 77, .2);
  border-radius: 50%;
  object-fit: cover;
  background: #f3efe5;
}

.mushaf-player__qari-select > svg { width: 18px; height: 18px; flex: 0 0 18px; color: #168d70; }
.mushaf-player__qari-select .mushaf-player__info { display: flex; }

.qari-overlay {
  position: fixed;
  inset: 0;
  z-index: 1200;
  display: flex;
  align-items: flex-end;
  justify-content: center;
  background: rgba(13, 29, 23, .42);
  backdrop-filter: blur(5px);
}

.qari-sheet {
  width: 100%;
  max-width: 620px;
  padding: 10px 20px calc(24px + var(--safe-bottom));
  border-radius: 26px 26px 0 0;
  background: #fffdfa;
  box-shadow: 0 -18px 50px rgba(17, 35, 28, .2);
}

.qari-sheet__handle { width: 42px; height: 4px; margin: 0 auto 15px; border-radius: 999px; background: #d5d9d6; }
.qari-sheet__header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
.qari-sheet__header span { color: #087d59; font-size: .65rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
.qari-sheet__header h2 { margin: 2px 0 0; color: #1f2e28; font-size: 1.2rem; }
.qari-sheet__header p { margin: 3px 0 0; color: #7b8580; font-size: .68rem; }
.qari-sheet__header > button { width: 40px; height: 40px; display: grid; place-items: center; border: 0; border-radius: 50%; color: #65716b; background: #f0f3f1; }
.qari-sheet__header > button svg { width: 19px; height: 19px; }
.qari-list { display: flex; flex-direction: column; gap: 9px; }
.qari-item { width: 100%; display: flex; align-items: center; gap: 12px; border: 1px solid #e4e8e5; border-radius: 15px; padding: 10px 12px; background: #fbfcfa; text-align: left; }
.qari-item--active { border-color: rgba(8, 125, 89, .32); background: #edf8f3; }
.qari-item img { width: 45px; height: 45px; flex: 0 0 45px; border: 1px solid rgba(180, 146, 77, .22); border-radius: 50%; object-fit: cover; background: #f3efe5; }
.qari-item > span { min-width: 0; flex: 1; display: flex; flex-direction: column; gap: 2px; }
.qari-item strong { color: #25332d; font-size: .82rem; }
.qari-item small { color: #7b8580; font-size: .69rem; }
.qari-item > svg { width: 20px; height: 20px; color: #087d59; }
.navigator-selector--section {
  width: 100%;
  height: 50px;
  padding: 0 14px;
  border-color: #dce3df;
  background: #fbfcfa;
}

.navigator-selector--section strong {
  color: #314039;
  font-size: .82rem;
  font-weight: 780;
}

.section-picker__grid {
  min-height: 0;
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  align-content: start;
  gap: 9px;
  overflow-y: auto;
  overscroll-behavior: contain;
  padding: 2px 1px 18px;
  scroll-behavior: auto;
}

.section-picker__grid button {
  min-height: 49px;
  display: grid;
  place-items: center;
  border: 1px solid #e2e6e3;
  border-radius: 13px;
  color: #44524c;
  background: #fff;
  font-size: .78rem;
  font-weight: 750;
  transition: transform .12s ease, border-color .16s ease, background .16s ease;
}

.section-picker__grid button:active { transform: scale(.95); }
.section-picker__grid .section-picker__number--active {
  border-color: #087d59;
  color: #fff;
  background: #087d59;
  box-shadow: 0 5px 13px rgba(8, 125, 89, .2);
}</style>