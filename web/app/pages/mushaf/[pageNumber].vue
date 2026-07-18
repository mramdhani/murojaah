<template>
  <div
    class="mushaf-page"
    :class="{
      'mushaf-page--nabawiyyah': mushafTheme === 'nabawiyyah',
      'mushaf-page--classic': mushafTheme === 'classic',
      'mushaf-page--dark': mushafTheme === 'dark',
      'mushaf-page--fullscreen': isFullscreenMode,
      'mushaf-page--klasik': mushafMode === 'klasik'
    }"
    @click.self="toggleFullscreen"
  >
    <header class="mushaf-header" :class="{ 'mushaf-header--hidden': isFullscreenMode }">
      <button type="button" class="mushaf-header__back" aria-label="Kembali" @click="router.push('/')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
      </button>

      <div class="mushaf-header__main">
        <button type="button" class="mushaf-header__mode" @click="openModeDrawer">
          <span>Mushaf Hafalan</span>
          <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 8 4 4 4-4"/></svg>
        </button>
        <button type="button" class="mushaf-header__title" @click="openNavigator">
          <strong>{{ currentSurahTitle }}</strong>
          <small>Hal {{ pageNumber }} <span>&middot;</span> Juz {{ juzLabel }}</small>
        </button>
      </div>

      <!-- Translate & Display Settings Button -->
      <button type="button" class="mushaf-header__translate" aria-label="Pengaturan Tampilan & Terjemahan" @click="showTranslationDrawer = true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
          <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/>
          <path d="M6 6h10M6 10h10M6 14h6"/>
        </svg>
      </button>

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
        :class="{
          'mushaf-viewport--dragging': swipeStartX !== null,
          'mushaf-viewport--monochrome': !showTajweedColors
        }"
        aria-label="Halaman mushaf interaktif. Geser kiri atau kanan untuk berpindah halaman."
        @pointerdown="handlePointerDown"
        @pointermove="handlePointerMove"
        @pointerup="handlePointerUp"
        @pointercancel="cancelSwipe"
      >
        <div class="mushaf-track" :class="{ 'mushaf-track--animating': swipeAnimating }" :style="trackStyle">
          <div v-for="(slidePage, index) in carouselPages" :key="slidePage + '-' + index" class="mushaf-slide">
            <!-- QCF V2 Font-Based Mushaf Page -->
            <div
              class="mushaf-page-box"
              :class="[
                'mushaf-theme--' + mushafTheme,
                {
                  'mushaf-page-box--loading': index === 1 && qcfLoading,
                  'mushaf-page-box--opening': slidePage && slidePage <= 2,
                  'mushaf-page-box--fatihah': slidePage === 1,
                  'mushaf-page-box--baqarah': slidePage === 2,
                  'mushaf-page-box--closing': slidePage === 604,
                  'mushaf-page-box--multi-surah': slidePage && getPageSurahCount(slidePage) > 1,
                  'mushaf-page-box--bottom-surah-banner': slidePage && hasBottomSurahBanner(slidePage),
                  'mushaf-page-box-klasik': mushafMode === 'klasik',
                  'mushaf-page-box--with-frame': SHOW_DIGITAL_FRAME && mushafMode === 'digital',
                  'mushaf-page-box--left-page': slidePage && slidePage % 2 === 0,
                  'mushaf-page-box--right-page': slidePage && slidePage % 2 !== 0
                }
              ]"
            >
              <template v-if="slidePage">
                <!-- Mode Klasik (Gambar Scan HD) -->
                <div v-if="mushafMode === 'klasik'" class="mushaf-classic-container">
                  <div 
                    class="mushaf-classic-wrapper" 
                    :data-page="slidePage"
                    :style="getKlassikLayout(slidePage).wrapperStyle"
                  >
                    <!-- Show beautiful loading spinner overlay ONLY on the active center slide when not loaded -->
                    <div v-if="index === 1 && !classicImgLoaded" class="mushaf-classic-loading">
                      <div class="mushaf-spinner"></div>
                      <span>Memuat Halaman...</span>
                    </div>

                    <img
                      :ref="el => { if (index === 1 && el) klassikImgRef = el as HTMLImageElement }"
                      :src="localImageUrl(slidePage)"
                      alt="Mushaf Cetak Madinah"
                      class="mushaf-classic-img"
                      :class="{ 
                        'mushaf-classic-img--dark': mushafTheme === 'dark',
                        'mushaf-classic-img--sepia': mushafTheme === 'nabawiyyah',
                        'mushaf-classic-img--loaded': index !== 1 || classicImgLoaded
                      }"
                      :style="getKlassikLayout(slidePage).imgStyle"
                      @load="index === 1 && onClassicImgLoad()"
                    />
                    <!-- SVG Overlay for ayah selection (only active for center slide) -->
                    <svg
                      v-if="index === 1 && klassikPageCoords"
                      class="mushaf-classic-overlay"
                      :viewBox="getKlassikLayout(slidePage).viewBox"
                      preserveAspectRatio="none"
                      @pointerdown.stop="handleClassicPointerDown"
                      @pointerup.stop="handleClassicPointerUp"
                      @pointermove.stop="handleClassicPointerMove"
                      @pointercancel.stop="handleClassicPointerCancel"
                      @mousemove="handleClassicHover"
                      @mouseleave="klassikHoverAyah = null"
                    >
                      <!-- Hover highlight: full-height merged block -->
                      <template v-if="klassikHoverAyah">
                        <rect
                          v-for="(line, li) in klassikHoverAyah.l"
                          :key="'h' + li"
                          :x="line[0]"
                          :y="line[1]"
                          :width="line[2] - line[0]"
                          :height="line[3] - line[1]"
                          rx="6"
                          ry="6"
                          class="klassik-hover-rect"
                        />
                      </template>
                      <!-- Selected (tapped) highlight: full-height merged block -->
                      <template v-if="klassikActiveHighlight">
                        <rect
                          v-for="(line, li) in klassikActiveHighlight.l"
                          :key="'s' + li"
                          :x="line[0]"
                          :y="line[1]"
                          :width="line[2] - line[0]"
                          :height="line[3] - line[1]"
                          rx="6"
                          ry="6"
                          class="klassik-selected-rect"
                        />
                      </template>
                    </svg>
                  </div>
                </div>

                <!-- Mode Digital (Teks QCF V2) -->
                <template v-else>
                  <!-- Ornamental Frame -->
                <div class="mushaf-frame" aria-hidden="true">
                  <!-- Corner ornaments -->
                  <div class="mushaf-frame__corner mushaf-frame__corner--tl"></div>
                  <div class="mushaf-frame__corner mushaf-frame__corner--tr"></div>
                  <div class="mushaf-frame__corner mushaf-frame__corner--bl"></div>
                  <div class="mushaf-frame__corner mushaf-frame__corner--br"></div>
                  <!-- Border lines -->
                  <div class="mushaf-frame__border"></div>
                </div>

                <!-- Page Meta Header -->
                <div class="mushaf-meta">
                  <span class="mushaf-meta__juz">{{ getPageJuz(slidePage) }}</span>
                  <span class="mushaf-meta__page">{{ slidePage }}</span>
                  <span class="mushaf-meta__surah">{{ getPageSurah(slidePage) }}</span>
                </div>

                <!-- QCF V2 Text Content -->
                <div
                  class="mushaf-qcf-content"
                  :class="{ 'mushaf-qcf-content--short': getPageLines(slidePage).length < 15 }"
                  :style="{ fontFamily: qcfFontFamily(slidePage) }"
                >
                  <!-- Loading skeleton -->
                  <div v-if="index === 1 && qcfLoading" class="qcf-loading">
                    <div v-for="l in 15" :key="l" class="qcf-skeleton-line"></div>
                  </div>

                  <!-- Rendered lines -->
                  <template v-else>
                    <div v-if="slidePage === 1" class="mushaf-opening-title mushaf-opening-title--fatihah">{{ surahNameGlyph(1) }}</div>
                    <template v-for="line in getPageLines(slidePage)" :key="line.line_number">
                      <!-- Render banner/Bismillah as a direct line substitute if mapped to this line number -->
                      <div
                        v-if="getSurahBannerAtLine(line.line_number, slidePage) || getBismillahAtLine(line.line_number, slidePage)"
                        class="mushaf-line mushaf-line--qcf"
                        :class="{
                          'mushaf-line--banner-row': getSurahBannerAtLine(line.line_number, slidePage),
                          'mushaf-line--bismillah-row': getBismillahAtLine(line.line_number, slidePage),
                          'mushaf-line--no-border': true
                        }"
                        :data-line="line.line_number"
                      >
                        <!-- Surah Banner Row -->
                        <div
                          v-if="getSurahBannerAtLine(line.line_number, slidePage)"
                          class="mushaf-surah-banner"
                        >
                          <div class="mushaf-surah-banner__inner">
                            <span class="mushaf-surah-banner__sub mushaf-surah-banner__sub--left">
                              {{ '\u0622\u064a\u064e\u0627\u062a\u064f\u0647\u064e\u0627' }} {{ formatArabicNumber(getSurahBannerAtLine(line.line_number, slidePage)?.total_ayah || 0) }}
                            </span>
                            <span
                              class="mushaf-surah-banner__name"
                              :aria-label="getSurahBannerAtLine(line.line_number, slidePage)?.name_arabic"
                            >{{ surahNameGlyph(getSurahBannerAtLine(line.line_number, slidePage)?.number) }}</span>
                            <span class="mushaf-surah-banner__sub mushaf-surah-banner__sub--right">
                              {{ getSurahBannerAtLine(line.line_number, slidePage)?.revelation_place === 'meccan' ? '\u0645\u064e\u0643\u0650\u0651\u064a\u064e\u0651\u0629\u064c' : '\u0645\u064e\u062f\u064e\u0646\u0650\u064a\u064e\u0651\u0629\u064c' }}
                            </span>
                          </div>
                        </div>

                        <!-- Bismillah Calligraphy Row -->
                        <div
                          v-else-if="getBismillahAtLine(line.line_number, slidePage)"
                          class="mushaf-bismillah-calligraphy"
                          aria-label="Bismillahirrahmanirrahim"
                        >{{ '\ufdfd' }}</div>
                      </div>

                      <!-- Normal text line (Unicode Fallback) -->
                      <div
                        v-else-if="line.unicode_fallback?.length"
                        class="mushaf-line mushaf-line--unicode"
                        :class="{ 'mushaf-line--no-border': isLastLineOfSurah(line.line_number, slidePage) }"
                        :data-line="line.line_number"
                      >
                        <span
                          v-for="verse in line.unicode_fallback"
                          :key="verse.verse_key"
                          class="mushaf-unicode-verse"
                          :data-verse="verse.verse_key"
                        >
                          <span>{{ verse.text }}</span>
                          <span
                            class="mushaf-ayah-ornament mushaf-ayah-ornament--unicode"
                            :aria-label="`Ayat ${verse.ayah_number}`"
                          >
                            <svg viewBox="0 0 100 100" aria-hidden="true">
                              <rect x="22" y="22" width="56" height="56" rx="8" fill="none" stroke="#bd8c30" stroke-width="5" transform="rotate(45 50 50)"/>
                              <rect x="22" y="22" width="56" height="56" rx="8" fill="none" stroke="#bd8c30" stroke-width="5"/>
                              <circle cx="50" cy="50" r="26" fill="#fffcf5" stroke="#166d70" stroke-width="2.5"/>
                              <circle cx="50" cy="50" r="21.5" fill="none" stroke="#d34f3b" stroke-width="1.5" stroke-dasharray="3 2.5"/>
                            </svg>
                            <span>{{ formatArabicNumber(verse.ayah_number) }}</span>
                          </span>
                        </span>
                      </div>

                      <!-- Normal text line (QCF V2) -->
                      <div
                        v-else
                        class="mushaf-line mushaf-line--qcf"
                        :class="{ 'mushaf-line--no-border': isLastLineOfSurah(line.line_number, slidePage) }"
                        :data-line="line.line_number"
                      >
                        <span class="mushaf-line__qcf-content">
                          <template v-for="word in getGroupedWords(line.words)" :key="word.word_id">
                            <span
                              class="mushaf-word-group"
                              :class="{ 'mushaf-word-group--with-ornament': word.attachedOrnament }"
                            >
                              <!-- Normal word -->
                              <span
                                :data-verse="word.verse_key"
                                :class="[
                                  'mushaf-word',
                                  ...tajweedClassesForWord(word.text_tajweed),
                                  index === 1 && isActiveVerse(word.verse_key) ? 'mushaf-word--active' : '',
                                  index === 1 && isLastReadVerse(word.verse_key) ? 'mushaf-word--last-read' : '',
                                  index === 1 && isBookmarkedVerse(word.verse_key) ? 'mushaf-word--bookmarked' : '',
                                  index === 1 ? getVerseProgressClass(word.verse_key) : ''
                                ]"
                              >
                                <span :style="{ fontFamily: qcfFontFamily(word.page_number || slidePage) }">{{ word.code_v2 }}</span>
                              </span>
                              <!-- Attached ornament -->
                              <span
                                v-if="word.attachedOrnament"
                                :data-verse="word.attachedOrnament.verse_key"
                                :class="[
                                  'mushaf-word',
                                  'mushaf-word--end',
                                  index === 1 && isActiveVerse(word.attachedOrnament.verse_key) ? 'mushaf-word--active' : '',
                                  index === 1 && isLastReadVerse(word.attachedOrnament.verse_key) ? 'mushaf-word--last-read' : '',
                                  index === 1 && isBookmarkedVerse(word.attachedOrnament.verse_key) ? 'mushaf-word--bookmarked' : '',
                                  index === 1 ? getVerseProgressClass(word.attachedOrnament.verse_key) : ''
                                ]"
                              >
                                <span
                                  class="mushaf-ayah-ornament"
                                  :aria-label="`Ayat ${ayahNumberFromVerseKey(word.attachedOrnament.verse_key)}`"
                                >
                                  <svg viewBox="0 0 100 100" aria-hidden="true">
                                    <rect x="22" y="22" width="56" height="56" rx="8" fill="none" stroke="#bd8c30" stroke-width="5" transform="rotate(45 50 50)"/>
                                    <rect x="22" y="22" width="56" height="56" rx="8" fill="none" stroke="#bd8c30" stroke-width="5"/>
                                    <circle cx="50" cy="50" r="26" fill="#fffcf5" stroke="#166d70" stroke-width="2.5"/>
                                    <circle cx="50" cy="50" r="21.5" fill="none" stroke="#d34f3b" stroke-width="1.5" stroke-dasharray="3 2.5"/>
                                  </svg>
                                  <span>{{ formatArabicNumber(ayahNumberFromVerseKey(word.attachedOrnament.verse_key)) }}</span>
                                </span>
                              </span>
                            </span>
                          </template>
                        </span>
                      </div>
                    </template>
                  </template>
                </div>

                <!-- Opening Page Bottom Banner -->
                <div v-if="slidePage === 1 || slidePage === 2" class="mushaf-bottom-banner">
                  <div class="mushaf-bottom-banner__inner">
                    <span class="mushaf-bottom-banner__text">
                      {{ slidePage === 1 ? '\u0648\u064e\u0622\u064a\u064e\u0627\u062a\u064f\u0647\u064e\u0627 \u0633\u064e\u0628\u0652\u0639\u064c' : '\u0648\u064e\u0622\u064a\u064e\u0627\u062a\u064f\u0647\u064e\u0627 \u0645\u0650\u0627\u0626\u064e\u062a\u064e\u0627\u0646\u0650 \u0648\u064e\u0633\u0650\u062a\u064c\u0651 \u0648\u064e\u062b\u064e\u0645\u064e\u0627\u0646\u064f\u0648\u0646\u064e' }}
                    </span>
                  </div>
                </div>

                  <!-- Page number footer -->
                  <div class="mushaf-page-footer">
                    <span>{{ slidePage }}</span>
                  </div>
                </template>
              </template>
            </div>
          </div>
        </div>

        <!-- Line mask overlay for memorization (works on QCF too) -->
        <div v-if="mushafMode === 'digital' && !qcfLoading && !isSwipeActive" class="line-masks" aria-label="Mask hafalan per baris">
          <button
            v-for="line in lineCount"
            :key="line"
            type="button"
            class="line-mask"
            :class="{ 'line-mask--revealed': revealedLines[line - 1] }"
            :aria-label="(revealedLines[line - 1] ? 'Sembunyikan baris ' : 'Tampilkan baris ') + line"
            @dblclick.stop="toggleLine(line - 1)"
          ></button>
        </div>
      </section>
    </main>

    <Teleport to="body">
      <section class="mushaf-player" :class="{ 'mushaf-player--hidden': isFullscreenMode || showTranslationDrawer, 'mushaf-player--dark': mushafTheme === 'dark' }" aria-label="Pemutar murottal">
        <div class="mushaf-player__actions">
          <!-- Play/Pause -->
          <button type="button" class="mushaf-player__play" :aria-label="isPlaying ? 'Jeda murottal' : 'Putar murottal'" @click="togglePlayer">
            <svg v-if="!isPlaying" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="m8 5 11 7-11 7V5Z"/></svg>
            <svg v-else viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 5h4v14H7zM13 5h4v14h-4z"/></svg>
          </button>

          <!-- Repeat Button -->
          <button type="button" class="mushaf-player__btn" :class="{ 'mushaf-player__btn--active': isCustomRangeActive || localRepeatCount > 1 }" @click="toggleRepeatMode" aria-label="Pengulangan Murotal">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="17 1 21 5 17 9"/>
              <path d="M3 11V9a4 4 0 0 1 4-4h14"/>
              <polyline points="7 23 3 19 7 15"/>
              <path d="M21 13v2a4 4 0 0 1-4 4H3"/>
            </svg>
            <span v-if="isCustomRangeActive" class="mushaf-player__repeat-badge" style="font-size: 0.52rem;">Rentang</span>
            <span v-else-if="localRepeatCount > 1" class="mushaf-player__repeat-badge" style="font-size: 0.52rem;">
              {{ localRepeatCount === 99999 ? '\u221E' : localRepeatCount }}
            </span>
          </button>

          <!-- Settings Button -->
          <button type="button" class="mushaf-player__btn" @click="openAudioSettings" aria-label="Pengaturan Murotal">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="4" y1="21" x2="4" y2="14"/>
              <line x1="4" y1="10" x2="4" y2="3"/>
              <line x1="12" y1="21" x2="12" y2="12"/>
              <line x1="12" y1="8" x2="12" y2="3"/>
              <line x1="20" y1="21" x2="20" y2="16"/>
              <line x1="20" y1="12" x2="20" y2="3"/>
              <line x1="1" y1="14" x2="7" y2="14"/>
              <line x1="9" y1="8" x2="15" y2="8"/>
              <line x1="17" y1="16" x2="23" y2="16"/>
            </svg>
          </button>
        </div>

        <button type="button" class="mushaf-player__qari-select" @click="openQariPicker">
          <img :src="activeQari.image" :alt="activeQariName">
          <span class="mushaf-player__info">
            <strong>{{ activeQariName }}</strong>
            <small>{{ playerAyahLabel }}</small>
          </span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg>
        </button>
      </section>
    </Teleport>

    <!-- Settings Bottom Sheet -->
    <Transition name="sheet">
      <div v-if="showAudioSettings" class="qari-overlay" @click="closeAudioSettings">
        <section class="qari-sheet" :class="{ 'qari-sheet--dark': mushafTheme === 'dark' }" role="dialog" aria-modal="true" :style="audioSettingsSheet.sheetStyle.value" @click.stop>
          <div class="qari-sheet__handle" v-bind="audioSettingsSheet.bindHandle"></div>

          <template v-if="activePickerType === 'none'">
            <header class="qari-sheet__header">
              <h3 id="audio-settings-title" class="qari-sheet__title">Pengaturan Murotal</h3>
            </header>

            <div class="audio-settings-body">
              <div class="audio-settings-row">
                <div class="audio-settings-col">
                  <label>Dari Surat</label>
                  <button type="button" class="custom-select-trigger" @click="activePickerType = 'startSurah'">
                    <span>{{ startSurahName }}</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m8 10 4 4 4-4"/></svg>
                  </button>
                </div>
                <div class="audio-settings-col">
                  <label>Ayat</label>
                  <button type="button" class="custom-select-trigger" @click="activePickerType = 'startAyah'">
                    <span>{{ settingsStartAyah }}</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m8 10 4 4 4-4"/></svg>
                  </button>
                </div>
              </div>

              <div class="audio-settings-row">
                <div class="audio-settings-col">
                  <label>Hingga Surat</label>
                  <button type="button" class="custom-select-trigger" @click="activePickerType = 'endSurah'">
                    <span>{{ endSurahName }}</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m8 10 4 4 4-4"/></svg>
                  </button>
                </div>
                <div class="audio-settings-col">
                  <label>Ayat</label>
                  <button type="button" class="custom-select-trigger" @click="activePickerType = 'endAyah'">
                    <span>{{ settingsEndAyah }}</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m8 10 4 4 4-4"/></svg>
                  </button>
                </div>
              </div>

              <div class="audio-settings-field">
                <label>Pengulangan Ayat</label>
                <button type="button" class="custom-select-trigger" @click="activePickerType = 'ayahRepeat'">
                  <span>{{ ayahRepeatLabel }}</span>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m8 10 4 4 4-4"/></svg>
                </button>
              </div>

              <div class="audio-settings-field">
                <label>Pengulangan Keseluruhan</label>
                <button type="button" class="custom-select-trigger" @click="activePickerType = 'rangeRepeat'">
                  <span>{{ rangeRepeatLabel }}</span>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m8 10 4 4 4-4"/></svg>
                </button>
              </div>

              <div class="audio-settings-actions">
                <button class="settings-action-btn settings-action-btn--cancel" @click="closeAudioSettings">Batal</button>
                <button class="settings-action-btn settings-action-btn--play" @click="startCustomRangePlayback">Putar</button>
              </div>
            </div>
          </template>

          <template v-else>
            <header class="picker-header">
              <button type="button" class="picker-back" @click="activePickerType = 'none'">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m15 18-6-6 6-6"/></svg>
              </button>
              <div>
                <strong class="qari-sheet__title">{{ pickerTitle }}</strong>
              </div>
            </header>

            <div v-if="activePickerType === 'startSurah' || activePickerType === 'endSurah'" class="picker-search-bar">
              <input v-model="settingsSurahSearch" type="search" placeholder="Cari nama atau nomor surat..." class="picker-search-input">
            </div>

            <div class="picker-options-list">
              <!-- Surahs list -->
              <template v-if="activePickerType === 'startSurah' || activePickerType === 'endSurah'">
                <button
                  v-for="s in filteredSettingsSurahs"
                  :key="s.id"
                  type="button"
                  class="surah-picker__item"
                  :class="{
                    'surah-picker__item--active': activePickerType === 'startSurah' ? settingsStartSurah === s.number : settingsEndSurah === s.number,
                    'picker-option-item--disabled': isSurahDisabled(s.number)
                  }"
                  :disabled="isSurahDisabled(s.number)"
                  @click="selectSurahOption(s.number)"
                >
                  <span class="surah-picker__number">{{ s.number }}</span>
                  <span class="surah-picker__names">
                    <strong>{{ s.name_latin }}</strong>
                    <small>{{ s.total_ayah }} ayat</small>
                  </span>
                  <span class="surah-picker__arabic">{{ s.name_arabic }}</span>
                  <svg v-if="(activePickerType === 'startSurah' ? settingsStartSurah === s.number : settingsEndSurah === s.number)" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                    <path d="m5 10 3 3 7-7"/>
                  </svg>
                </button>
              </template>

              <!-- Ayahs grid -->
              <template v-else-if="activePickerType === 'startAyah' || activePickerType === 'endAyah'">
                <div class="ayah-picker-grid">
                  <button
                    v-for="a in (activePickerType === 'startAyah' ? startSurahTotalAyah : endSurahTotalAyah)"
                    :key="a"
                    type="button"
                    class="ayah-picker-cell"
                    :class="{
                      'ayah-picker-cell--active': activePickerType === 'startAyah' ? settingsStartAyah === a : settingsEndAyah === a,
                      'ayah-picker-cell--disabled': isAyahDisabled(a)
                    }"
                    :disabled="isAyahDisabled(a)"
                    @click="selectAyahOption(a)"
                  >
                    {{ a }}
                  </button>
                </div>
              </template>

              <!-- Generic repeat count selections -->
              <template v-else>
                <button
                  v-for="opt in repeatOptions"
                  :key="opt.value"
                  type="button"
                  class="picker-option-item"
                  :class="{
                    'picker-option-item--active': activePickerType === 'ayahRepeat' ? settingsAyahRepeat === opt.value : settingsRangeRepeat === opt.value
                  }"
                  @click="selectRepeatOption(opt.value)"
                >
                  <span>{{ opt.label }}</span>
                </button>
              </template>
            </div>
          </template>
        </section>
      </div>
    </Transition>

    <Transition name="sheet">
      <div v-if="showQariPicker" class="qari-overlay" @click="closeQariPicker">
        <section class="qari-sheet" :class="{ 'qari-sheet--dark': mushafTheme === 'dark' }" role="dialog" aria-modal="true" aria-labelledby="qari-picker-title" :style="qariPickerSheet.sheetStyle.value" @click.stop>
          <div class="qari-sheet__handle" v-bind="qariPickerSheet.bindHandle"></div>
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

    <Transition name="translate-sheet">
      <div v-if="showTranslationDrawer" class="translation-bottom-sheet" :class="{ 'translation-bottom-sheet--dark': mushafTheme === 'dark' }" :style="translationSheet.sheetStyle.value" @click.stop>
        <!-- Drag handle -->
        <div
          class="translation-sheet-handle"
          v-bind="translationSheet.bindHandle"
        >
          <div class="translation-sheet-handle__bar"></div>
        </div>

        <!-- Header -->
        <header class="translation-sheet-header">
          <div class="translation-sheet-header__left">
            <div class="translation-sheet-dropdown-trigger" @click="showEditionPicker = true">
              <span>{{ selectedEdition.name }}</span>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="dropdown-chevron"><path d="m7 10 5 5 5-5"/></svg>
            </div>
            <p class="translation-sheet-subtitle">
              <span v-if="activeSurahMeta">{{ activeSurahMeta.number }}. {{ activeSurahMeta.name_latin }} &bull; </span>
              Hal {{ pageNumber }}, {{ activeJuzLabel }}
            </p>
          </div>
          <div class="translation-sheet-header__right-actions">
            <button
              type="button"
              class="translation-sheet-settings-toggle"
              :class="{ 'translation-sheet-settings-toggle--active': showDisplaySettingsSheet }"
              aria-label="Pengaturan Tampilan"
              @click="showDisplaySettingsSheet = true"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="settings-cog-icon">
                <circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
              </svg>
            </button>
            <button type="button" class="translation-sheet-close" aria-label="Tutup terjemahan" @click="showTranslationDrawer = false">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 6 12 12M18 6 6 18"/></svg>
            </button>
          </div>
        </header>

        <!-- Scrollable list -->
        <div ref="translationListRef" class="translation-sheet-list" @scroll.passive="handleTranslationScroll">
          <template v-for="ayah in pageData?.ayahs" :key="ayah.id">
            <div
              :ref="el => { if (el && ayah.verse_key === `${activeHighlightVerse.surah}:${activeHighlightVerse.ayah}`) activeTranslationItemRef = el as HTMLElement }"
              class="translation-sheet-item"
              :class="{ 'translation-sheet-item--active': ayah.verse_key === `${activeHighlightVerse.surah}:${activeHighlightVerse.ayah}` }"
              @click="selectAyahFromTranslation(ayah.surah_id, ayah.ayah_number)"
            >
              <!-- Islamic Geometric Number Frame -->
              <div class="translation-sheet-item__number-wrap">
                <svg viewBox="0 0 100 100" class="ayah-frame-svg">
                  <rect x="22" y="22" width="56" height="56" rx="8" fill="none" stroke="currentColor" stroke-width="5" transform="rotate(45 50 50)"/>
                  <rect x="22" y="22" width="56" height="56" rx="8" fill="none" stroke="currentColor" stroke-width="5"/>
                  <circle cx="50" cy="50" r="23" fill="#fff" stroke="currentColor" stroke-width="2.5"/>
                </svg>
                <span :style="{ fontSize: ayah.ayah_number >= 100 ? '0.62rem' : (ayah.ayah_number >= 10 ? '0.74rem' : '0.82rem') }">
                  {{ ayah.ayah_number }}
                </span>
              </div>

              <div class="translation-sheet-item__content">
                <!-- Transliterasi (Latin) -->
                <p
                  v-if="showTransliteration && transliterations[ayah.verse_key]"
                  class="translation-sheet-item__latin"
                  :style="{ fontSize: (translationFontSize * 0.92) + 'rem' }"
                >
                  {{ transliterations[ayah.verse_key] }}
                </p>
                <!-- Translation -->
                <p class="translation-sheet-item__text" :style="{ fontSize: translationFontSize + 'rem' }">
                  <span v-if="editionLoading" class="translation-text-loading-skeleton">Memuat terjemahan...</span>
                  <span v-else>{{ getTranslationText(ayah) }}</span>
                </p>
              </div>
            </div>
          </template>
        </div>

        <!-- Edition Picker (Bottom Sheet Modal) -->
        <Transition name="sheet">
          <div v-if="showEditionPicker" class="qari-overlay" @click="showEditionPicker = false">
            <section class="qari-sheet" :class="{ 'qari-sheet--dark': mushafTheme === 'dark' }" role="dialog" aria-modal="true" aria-labelledby="edition-picker-title" :style="editionPickerSheet.sheetStyle.value" @click.stop>
              <div class="qari-sheet__handle" v-bind="editionPickerSheet.bindHandle"></div>
              <header class="qari-sheet__header">
                <div>
                  <span>Terjemahan &amp; Tafsir</span>
                  <h2 id="edition-picker-title">Pilih Edisi</h2>
                  <p>Pilih terjemahan atau tafsir Al-Qur'an</p>
                </div>
                <button type="button" aria-label="Tutup pilihan edisi" @click="showEditionPicker = false">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 6 12 12M18 6 6 18"/></svg>
                </button>
              </header>
              <div class="qari-list">
                <button v-for="edition in editions" :key="edition.id" type="button" class="qari-item" :class="{ 'qari-item--active': edition.id === selectedEdition.id }" @click="selectEdition(edition)">
                  <span>
                    <strong>{{ edition.name }}</strong>
                    <small>{{ edition.language === 'id' ? 'Bahasa Indonesia' : 'English' }} &middot; {{ edition.type === 'tafsir' ? 'Tafsir' : 'Terjemahan' }}</small>
                  </span>
                  <svg v-if="edition.id === selectedEdition.id" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>
                </button>
              </div>
            </section>
          </div>
        </Transition>

        <!-- Display Settings Bottom Sheet (Slide Up) -->
        <Transition name="sheet">
          <div v-if="showDisplaySettingsSheet" class="qari-overlay" @click="showDisplaySettingsSheet = false">
            <section class="qari-sheet" :class="{ 'qari-sheet--dark': mushafTheme === 'dark' }" role="dialog" aria-modal="true" aria-labelledby="display-settings-title" :style="displaySettingsSheet.sheetStyle.value" @click.stop>
              <div class="qari-sheet__handle" v-bind="displaySettingsSheet.bindHandle"></div>
              <header class="qari-sheet__header">
                <div>
                  <span>Preferensi Tampilan</span>
                  <h2 id="display-settings-title">Pengaturan Tampilan</h2>
                  <p>Sesuaikan tema halaman, transliterasi, tajwid, dan ukuran teks</p>
                </div>
                <button type="button" aria-label="Tutup pengaturan" @click="showDisplaySettingsSheet = false">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 6 12 12M18 6 6 18"/></svg>
                </button>
              </header>
              <div class="qari-list settings-sheet-body">
                <!-- Group 1: Visual & Tema -->
                <div class="settings-card">
                  <div class="settings-card-title">TAMPILAN MUSHAF</div>
                  
                  <!-- Row 1: Themes -->
                  <div class="settings-row">
                    <div class="settings-row-header">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="setting-label-icon"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M5 5l1.5 1.5M17.5 17.5 19 19M2 12h2M20 12h2M6.5 17.5 5 19M19 5l-1.5 1.5"/></svg>
                      <span class="settings-row-label">Tema Halaman</span>
                    </div>
                    <div class="theme-segmented-control-new" style="margin-top: 4px;">
                      <button
                        type="button"
                        class="theme-segment-btn-new theme-segment-btn-new--classic"
                        :class="{ 'theme-segment-btn-new--active': mushafTheme === 'classic' }"
                        @click="mushafTheme = 'classic'"
                      >
                        Terang
                      </button>
                      <button
                        type="button"
                        class="theme-segment-btn-new theme-segment-btn-new--dark"
                        :class="{ 'theme-segment-btn-new--active': mushafTheme === 'dark' }"
                        @click="mushafTheme = 'dark'"
                      >
                        Malam
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Group 2: Konten & Terjemahan -->
                <div class="settings-card">
                  <div class="settings-card-title">TEKS & TERJEMAHAN</div>
                  
                  <!-- Row 1: Transliterasi -->
                  <div class="settings-row settings-row--switch">
                    <div class="settings-row-header">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="setting-label-icon"><polyline points="4 7 4 4 20 4 20 7"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/></svg>
                      <span class="settings-row-label">Transliterasi</span>
                    </div>
                    <label class="tss-switch">
                      <input type="checkbox" v-model="showTransliteration" />
                      <span class="tss-slider-toggle"></span>
                    </label>
                  </div>

                  <!-- Row 2: Tajwid -->
                  <div class="settings-row settings-row--switch">
                    <div class="settings-row-header">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="setting-label-icon"><path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 14.7255 3.09032 17.1962 4.85857 19C5.03454 19.176 5.09437 19.434 5.00898 19.6705C4.82587 20.1777 4.79373 20.7308 4.93329 21.2662C5.07285 21.8016 5.37877 22.2891 5.81188 22.6517C5.97341 22.787 6.1856 22.8427 6.39088 22.8038C6.98592 22.6908 7.59546 22.7712 8.15689 23.0371C8.36159 23.134 8.60155 23.1118 8.78465 22.9786C9.7562 22.2713 10.9275 22 12 22Z"/><circle cx="7.5" cy="10.5" r="1.5"/><circle cx="11.5" cy="7.5" r="1.5"/><circle cx="16.5" cy="9.5" r="1.5"/><circle cx="15.5" cy="14.5" r="1.5"/></svg>
                      <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="settings-row-label">Warna Tajwid</span>
                        <button
                          type="button"
                          class="tss-info-link-btn"
                          @click="showTajweedLegendSheet = true"
                          aria-label="Panduan Tajwid"
                        >
                          Panduan
                        </button>
                      </div>
                    </div>
                    <label class="tss-switch">
                      <input type="checkbox" v-model="showTajweedColors" />
                      <span class="tss-slider-toggle"></span>
                    </label>
                  </div>

                  <!-- Row 3: Font Size -->
                  <div class="settings-row settings-row--slider">
                    <div class="settings-row-header">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="setting-label-icon"><path d="M4 9h12M4 15h12M10 3v18"/></svg>
                      <span class="settings-row-label">Ukuran Teks Terjemahan</span>
                      <span class="tss-font-value-new">{{ translationFontSizeLevel }}%</span>
                    </div>
                    <div class="tss-slider-wrap-new">
                      <input
                        v-model.number="translationFontSizeLevel"
                        type="range"
                        min="20"
                        max="100"
                        step="20"
                        class="tss-slider-new"
                        :style="{ background: 'linear-gradient(to right, #064e3b 0%, #064e3b ' + ((translationFontSizeLevel - 20) / 80 * 100) + '%, #e2e8f0 ' + ((translationFontSizeLevel - 20) / 80 * 100) + '%, #e2e8f0 100%)' }"
                      />
                    </div>
                  </div>
                </div>
              </div>
            </section>
          </div>
        </Transition>

        <!-- Tajweed Legend Bottom Sheet (Slide Up) -->
        <Transition name="sheet">
          <div v-if="showTajweedLegendSheet" class="qari-overlay" @click="showTajweedLegendSheet = false">
            <section class="qari-sheet" :class="{ 'qari-sheet--dark': mushafTheme === 'dark' }" role="dialog" aria-modal="true" aria-labelledby="tajweed-legend-title" :style="legendSheet.sheetStyle.value" @click.stop>
              <div class="qari-sheet__handle" v-bind="legendSheet.bindHandle"></div>
              <header class="qari-sheet__header">
                <div>
                  <span>Panduan Membaca</span>
                  <h2 id="tajweed-legend-title">Warna Tajwid</h2>
                  <p>Kaidah pewarnaan hukum bacaan Mushaf</p>
                </div>
                <button type="button" aria-label="Tutup panduan" @click="showTajweedLegendSheet = false">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 6 12 12M18 6 6 18"/></svg>
                </button>
              </header>
              <div class="qari-list" style="padding: 16px; gap: 14px;">
                <div class="tajweed-legend-row-item">
                  <span class="tajweed-legend-badge tss-color-dot--madd"></span>
                  <div class="tajweed-legend-info-box">
                    <strong>Mad (Panjang)</strong>
                    <p>Hukum pemanjangan harakat huruf (merah terang).</p>
                  </div>
                </div>
                <div class="tajweed-legend-row-item">
                  <span class="tajweed-legend-badge tss-color-dot--ghunnah"></span>
                  <div class="tajweed-legend-info-box">
                    <strong>Ghunnah (Dengung)</strong>
                    <p>Suara dengung yang ditahan pada huruf Noon atau Meem tasydid (hijau).</p>
                  </div>
                </div>
                <div class="tajweed-legend-row-item">
                  <span class="tajweed-legend-badge tss-color-dot--ikhfa"></span>
                  <div class="tajweed-legend-info-box">
                    <strong>Ikhfa (Samar)</strong>
                    <p>Menyamarkan bunyi Nun sukun atau Tanwin (merah muda/magenta).</p>
                  </div>
                </div>
                <div class="tajweed-legend-row-item">
                  <span class="tajweed-legend-badge tss-color-dot--idgham"></span>
                  <div class="tajweed-legend-info-box">
                    <strong>Idgham (Melebur)</strong>
                    <p>Memasukkan bunyi Nun sukun/Tanwin ke huruf berikutnya (biru).</p>
                  </div>
                </div>
                <div class="tajweed-legend-row-item">
                  <span class="tajweed-legend-badge tss-color-dot--qalqalah"></span>
                  <div class="tajweed-legend-info-box">
                    <strong>Qalqalah (Memantul)</strong>
                    <p>Bunyi memantul pada huruf Ba, Jim, Dal, Ta, Qaf saat sukun/waqaf (cyan/biru muda).</p>
                  </div>
                </div>
              </div>
            </section>
          </div>
        </Transition>

        <!-- Player inside Translation Sheet (Fixed at Bottom) -->
        <footer class="translation-sheet-player" :class="{ 'translation-sheet-player--dark': mushafTheme === 'dark', 'mushaf-player--dark': mushafTheme === 'dark' }">
          <div class="mushaf-player__actions">
            <!-- Play/Pause -->
            <button type="button" class="mushaf-player__play" :aria-label="isPlaying ? 'Jeda murottal' : 'Putar murottal'" @click="togglePlayer">
              <svg v-if="!isPlaying" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="m8 5 11 7-11 7V5Z"/></svg>
              <svg v-else viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 5h4v14H7zM13 5h4v14h-4z"/></svg>
            </button>

            <!-- Repeat Button -->
            <button type="button" class="mushaf-player__btn" :class="{ 'mushaf-player__btn--active': isCustomRangeActive || localRepeatCount > 1 }" @click="toggleRepeatMode" aria-label="Pengulangan Murotal">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/>
              </svg>
              <span v-if="isCustomRangeActive" class="mushaf-player__repeat-badge" style="font-size: 0.52rem;">Rentang</span>
              <span v-else-if="localRepeatCount > 1" class="mushaf-player__repeat-badge" style="font-size: 0.52rem;">
                {{ localRepeatCount === 99999 ? '\u221E' : localRepeatCount }}
              </span>
            </button>

            <!-- Settings Button -->
            <button type="button" class="mushaf-player__btn" @click="openAudioSettings" aria-label="Pengaturan Murotal">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg>
            </button>
          </div>

          <button type="button" class="mushaf-player__qari-select" @click="openQariPicker">
            <img :src="activeQari.image" :alt="activeQariName">
            <span class="mushaf-player__info">
              <strong>{{ activeQariName }}</strong>
              <small>{{ playerAyahLabel }}</small>
            </span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg>
          </button>
        </footer>
      </div>
    </Transition>

    <Transition name="fade">
      <div v-if="showAyahDrawer" class="navigator-overlay" @click="showAyahDrawer = false"></div>
    </Transition>

    <Transition name="translate-sheet">
      <div v-if="showAyahDrawer" class="translation-bottom-sheet ayah-options-sheet" :class="{ 'translation-bottom-sheet--dark': mushafTheme === 'dark', 'ayah-options-sheet--dark': mushafTheme === 'dark' }" :style="ayahDrawerSheet.sheetStyle.value" @click.stop>
        <!-- Drag handle -->
        <div class="translation-sheet-handle" v-bind="ayahDrawerSheet.bindHandle">
          <div class="translation-sheet-handle__bar"></div>
        </div>

        <!-- Header -->
        <header class="translation-sheet-header">
          <div class="translation-sheet-header__left">
            <h3 class="ayah-drawer-title">Surat {{ selectedAyahForDrawer?.surah }}, Ayat {{ selectedAyahForDrawer?.ayah }}</h3>
            <p class="ayah-drawer-subtitle">{{ selectedAyahSurahName }} &middot; Juz {{ juzLabel }}</p>
          </div>
          <button type="button" class="translation-sheet-close" aria-label="Tutup opsi" @click="showAyahDrawer = false">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 6 12 12M18 6 6 18"/></svg>
          </button>
        </header>

        <!-- Divider with Star Ornament -->
        <div class="ayah-drawer-divider">
          <span class="ayah-drawer-divider__ornament">
            <svg viewBox="0 0 24 24" fill="currentColor" style="width: 12px; height: 12px;">
              <path d="M12 2l2.4 4.8 4.8 2.4-4.8 2.4-2.4 4.8-2.4-4.8-4.8-2.4 4.8-2.4z"/>
            </svg>
          </span>
        </div>

        <div class="ayah-options-content">
          <!-- Primary play action card -->
          <button type="button" class="ayah-primary-card" @click="playAyahFromDrawer">
            <svg class="play-icon-simple" viewBox="0 0 24 24" fill="currentColor">
              <polygon points="6 3 20 12 6 21 6 3"/>
            </svg>
            <span class="ayah-primary-card__text">Putar ayat ini</span>
          </button>

          <!-- Secondary list actions -->
          <div class="ayah-list-actions">
            <!-- 1. Terjemahan -->
            <button type="button" class="ayah-list-item" @click="openTranslationForAyah">
              <div class="ayah-list-item__icon-wrapper">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2zM22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
              </div>
              <span class="ayah-list-item__label">Lihat Terjemahan Lengkap</span>
              <svg class="ayah-list-item__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
            </button>

            <!-- 2. Salin -->
            <button type="button" class="ayah-list-item" @click="copyAyahText">
              <div class="ayah-list-item__icon-wrapper Arabic-symbol">
                <span>&#1593;</span>
              </div>
              <span class="ayah-list-item__label">Salin Teks Arab</span>
              <svg class="ayah-list-item__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
            </button>

            <!-- 3. Tandai Terakhir Baca / Hapus -->
            <button type="button" class="ayah-list-item" @click="toggleLastRead">
              <div class="ayah-list-item__icon-wrapper" :class="{ 'ayah-list-item__icon-wrapper--active': isSelectedAyahLastRead }">
                <template v-if="isSelectedAyahLastRead">
                  <svg viewBox="0 0 24 24" fill="currentColor" stroke="none"><path d="M4 21V4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v14l-4-2-4 2-4-2-4 2z"/></svg>
                </template>
                <template v-else>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21V4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v14l-4-2-4 2-4-2-4 2z"/></svg>
                </template>
              </div>
              <span class="ayah-list-item__label">{{ isSelectedAyahLastRead ? 'Hapus Tandai' : 'Tandai Terakhir Baca' }}</span>
              <svg class="ayah-list-item__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
            </button>
            <!-- 4. Bookmark (menggunakan icon bintang untuk favorit) -->
            <button type="button" class="ayah-list-item" @click="toggleSelectedAyahBookmark">
              <div class="ayah-list-item__icon-wrapper" :class="{ 'ayah-list-item__icon-wrapper--active': selectedAyahBookmarked }">
                <template v-if="selectedAyahBookmarked">
                  <svg viewBox="0 0 24 24" fill="currentColor" stroke="none"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                </template>
                <template v-else>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                </template>
              </div>
              <span class="ayah-list-item__label">{{ selectedAyahBookmarked ? 'Hapus Bookmark' : 'Simpan Bookmark' }}</span>
              <svg class="ayah-list-item__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
            </button>

            <!-- 5. Bagikan -->
            <button type="button" class="ayah-list-item" @click="shareAyah">
              <div class="ayah-list-item__icon-wrapper">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
              </div>
              <span class="ayah-list-item__label">Bagikan Ayat</span>
              <svg class="ayah-list-item__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <Transition name="sheet">
      <div v-if="navigatorOpen" class="navigator-overlay" @click="closeNavigator">
        <section
          class="navigator-sheet"
          :class="{ 'navigator-sheet--picker-open': showSurahPicker || showAyahPicker || showSectionPicker, 'navigator-sheet--dark': mushafTheme === 'dark' }"
          role="dialog"
          aria-modal="true"
          aria-labelledby="navigator-title"
          :style="navigatorSheet.sheetStyle.value"
          @click.stop
        >
          <div class="navigator-sheet__handle" v-bind="navigatorSheet.bindHandle"></div>

          <div class="navigator-sheet__content">
            <header class="navigator-sheet__header">
              <div>
                <span>Mushaf Hafalan</span>
                <h2 id="navigator-title">Pilih Bacaan</h2>
                <p>Surat, ayat, atau halaman yang ingin dibuka</p>
              </div>
              <button type="button" aria-label="Tutup pilihan bacaan" @click="closeNavigator">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 6 12 12M18 6 6 18"/></svg>
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
                <label>{{ navigationTypeLabel }} <span>{{ navigationAvailability }} tersedia</span></label>
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

          <Transition name="picker" @after-enter="handleSurahPickerEnter">
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

              <div ref="surahPickerListRef" class="surah-picker__list">
                <button
                  v-for="surah in filteredSurahOptions"
                  :key="surah.id"
                  type="button"
                  class="surah-picker__item"
                  :class="{ 'surah-picker__item--active': surah.id === selectedSurahId }"
                  :data-surah-id="surah.id"
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
          <Transition name="picker" @after-enter="handleAyahPickerEnter">
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

              <div ref="ayahPickerGridRef" class="ayah-picker__grid">
                <button
                  v-for="ayah in availableAyahs"
                  :key="ayah"
                  type="button"
                  :class="{ 'ayah-picker__number--active': ayah === selectedAyah }"
                  :data-ayah-number="ayah"
                  :aria-label="'Pilih ayat ' + ayah"
                  @click="chooseAyah(ayah)"
                >
                  {{ ayah }}
                </button>
              </div>
            </div>
          </Transition>
          <Transition name="picker" @after-enter="handleSectionPickerEnter">
            <div v-if="showSectionPicker" class="surah-picker section-picker">
              <header class="surah-picker__header">
                <button type="button" aria-label="Kembali ke navigasi" @click="showSectionPicker = false">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
                </button>
                <div>
                  <span>{{ navigationTypeLabel }}</span>
                  <strong>Pilih {{ navigationTypeLabel }}</strong>
                </div>
              </header>

              <p class="ayah-picker__summary">Tersedia {{ navigationTypeLabel.toLowerCase() }} {{ navigationAvailability }}</p>
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
definePageMeta({
  key: 'mushaf-page'
})

interface MushafSurah {
  id: number
  number: number
  name_latin: string
  name_arabic: string
  revelation_place?: string
  total_ayah?: number
  starts_at_line?: number | null
}

interface MushafWord {
  word_id: number
  verse_key: string
  position: number
  char_type: 'word' | 'end' | string
  code_v2: string
  text_tajweed?: string | null
}

interface MushafLine {
  line_number: number
  words: MushafWord[]
  unicode_fallback?: Array<{
    verse_key: string
    ayah_number: number
    text: string
  }>
}

interface MushafPageData {
  page_number: number
  juz: number[]
  surahs: MushafSurah[]
  lines: MushafLine[]
  verse_progress: Record<string, string>
  ayahs: Array<{
    id: number
    surah_id: number
    verse_key: string
    ayah_number: number
    page: number
    progress_status: string
    text_arabic?: string
    translation_id?: string
  }>
}

interface SurahOption extends MushafSurah {
  total_ayah: number
  revelation_place: string
  page_start: number | null
}

interface AyahLookup {
  page: number
}

const route = useRoute()
const router = useRouter()
const { apiFetch } = useApi()
const { open: openMurojaahDrawer } = useMurojaahDrawer()
const showToast = inject<(msg: string, type?: string) => void>('showToast')
const { loadBookmarks, isBookmarked, toggleBookmark } = useAyahBookmarks()

const pageData = ref<MushafPageData | null>(null)
const surahOptions = ref<SurahOption[]>([])
const imageLoaded = ref(false)
const classicImgLoaded = ref(false)
const hasRenderedPage = ref(false)
const imageError = ref(false)
const lineCount = 15
const revealedLines = ref<boolean[]>(Array(lineCount).fill(true))
const viewportRef = ref<HTMLElement | null>(null)
const swipeStartX = ref<number | null>(null)
const swipeStartY = ref<number | null>(null)
const swipeDirection = ref<'horizontal' | 'vertical' | null>(null)
const swipeStartTime = ref(0)
const swipeOffset = ref(0)
const swipeAnimating = ref(false)
const suppressNextLineTap = ref(false)
const longPressTimeout = ref<number | null>(null)
const isLongPressActive = ref(false)

const navigatorOpen = ref(false)
const navigatorLoading = ref(false)
const navigatorError = ref('')
const showTranslationDrawer = ref(false)
const translationListRef = ref<HTMLElement | null>(null)
const activeTranslationItemRef = ref<HTMLElement | null>(null)

const showAyahDrawer = ref(false)
const selectedAyahForDrawer = ref<{surah: number, ayah: number, verse_key: string, text?: string} | null>(null)

interface LastReadAyah {
  surah: number
  ayah: number
  verse_key: string
  page: number
  surah_name?: string
  text?: string
  marked_at?: string
}

const lastReadAyah = ref<LastReadAyah | null>(null)

interface TranslationEdition {
  id: string
  name: string
  language: string
  type: 'translation' | 'tafsir'
}

const editions: TranslationEdition[] = [
  { id: 'local', name: 'Terjemah Kemenag 2019', language: 'id', type: 'translation' },
  { id: 'id.jalalayn', name: 'Tafsir Al-Jalalain', language: 'id', type: 'tafsir' },
  { id: 'en.sahih', name: 'Sahih International', language: 'en', type: 'translation' },
  { id: 'id.muntakhab', name: 'Tafsir Ringkas Kemenag', language: 'id', type: 'tafsir' }
]

const selectedEdition = ref<TranslationEdition>(editions[0])
const showEditionPicker = ref(false)
const editionCache = ref<Record<string, Record<number, Record<string, string>>>>({}) // editionId -> { pageNumber -> { verseKey -> text } }
const editionLoading = ref(false)

const getTranslationText = (ayah: any) => {
  if (selectedEdition.value.id === 'local') {
    return ayah.translation_id || 'Terjemahan tidak tersedia.'
  }
  return editionCache.value[selectedEdition.value.id]?.[pageNumber.value]?.[ayah.verse_key] || 'Memuat terjemahan...'
}

const loadEdition = async (editionId: string) => {
  if (editionId === 'local') return
  if (editionCache.value[editionId]?.[pageNumber.value]) return

  try {
    editionLoading.value = true
    const res = await fetch(`https://api.alquran.cloud/v1/page/${pageNumber.value}/${editionId}`).then(r => r.json())
    if (res.code === 200 && res.data?.ayahs) {
      if (!editionCache.value[editionId]) {
        editionCache.value[editionId] = {}
      }
      const pageCache: Record<string, string> = {}
      for (const ayah of res.data.ayahs) {
        const key = `${ayah.surah.number}:${ayah.numberInSurah}`
        pageCache[key] = ayah.text
      }
      editionCache.value[editionId][pageNumber.value] = pageCache
    }
  } catch (e) {
    console.error('Failed to load translation edition:', e)
  } finally {
    editionLoading.value = false
  }
}

const selectEdition = async (edition: TranslationEdition) => {
  selectedEdition.value = edition
  showEditionPicker.value = false
  if (edition.id !== 'local') {
    await loadEdition(edition.id)
  }
}

const currentScrollSurahId = ref<number | null>(null)
const currentScrollJuz = ref<number | null>(null)
const translationFontSizeLevel = useCookie<number>('mushaf_translation_font_size', {
  default: () => 60,
  maxAge: 60 * 60 * 24 * 365,
  path: '/'
})
const showTransliteration = useCookie<boolean>('mushaf_show_transliteration', {
  default: () => true,
  maxAge: 60 * 60 * 24 * 365,
  path: '/'
})
const showTajweedColors = useCookie<boolean>('mushaf_show_tajweed_colors', {
  default: () => true,
  maxAge: 60 * 60 * 24 * 365,
  path: '/'
})
const showTajweedLegendSheet = ref(false)
const showDisplaySettingsSheet = ref(false)
const transliterations = ref<Record<string, string>>({})
const transliterationsLoading = ref(false)

const activeSurahMeta = computed(() => {
  const id = currentScrollSurahId.value || pageData.value?.ayahs[0]?.surah_id
  if (!id) return null
  return pageData.value?.surahs.find(s => s.id === id) || null
})

const activeJuzLabel = computed(() => {
  const juz = currentScrollJuz.value || pageData.value?.ayahs[0]?.juz || 1
  return `Juz ${juz}`
})

const translationFontSize = computed(() => {
  const level = translationFontSizeLevel.value
  if (level === 20) return 0.75
  if (level === 40) return 0.825
  if (level === 60) return 0.9
  if (level === 80) return 1.0
  return 1.125
})

const fetchTransliterations = async () => {
  if (!showTransliteration.value) return
  if (!pageData.value?.surahs.length) return
  transliterationsLoading.value = true
  try {
    for (const surah of pageData.value.surahs) {
      const hasSurah = Object.keys(transliterations.value).some(k => k.startsWith(`${surah.number}:`))
      if (!hasSurah) {
        const res = await fetch(`https://equran.id/api/v2/surat/${surah.number}`).then(r => r.json())
        if (res.code === 200 && res.data?.ayat) {
          for (const ayat of res.data.ayat) {
            const key = `${surah.number}:${ayat.nomorAyat}`
            transliterations.value[key] = ayat.teksLatin
          }
        }
      }
    }
  } catch (e) {
    console.error('Failed to fetch transliterations:', e)
  } finally {
    transliterationsLoading.value = false
  }
}

watch(showTransliteration, (newVal) => {
  if (newVal) {
    fetchTransliterations()
  }
})

const handleTranslationScroll = (event: Event) => {
  const container = event.target as HTMLElement
  if (!container || !pageData.value?.ayahs) return

  const scrollTop = container.scrollTop
  const items = container.querySelectorAll('.translation-sheet-item')

  let activeIndex = 0
  for (let i = 0; i < items.length; i++) {
    const item = items[i] as HTMLElement
    if (item.offsetTop - container.offsetTop >= scrollTop - 20) {
      activeIndex = i
      break
    }
  }

  const activeAyah = pageData.value.ayahs[activeIndex]
  if (activeAyah) {
    currentScrollSurahId.value = activeAyah.surah_id
    currentScrollJuz.value = activeAyah.juz
  }
}

const getAyahByVerseKey = (verseKey: string) =>
  pageData.value?.ayahs.find(ayah => ayah.verse_key === verseKey)

const getAyahArabicText = (verseKey: string): string => {
  const ayah = getAyahByVerseKey(verseKey)
  if (ayah?.text_arabic) return ayah.text_arabic.trim()

  const segments: string[] = []
  for (const line of pageData.value?.lines || []) {
    const fallback = line.unicode_fallback?.find(item => item.verse_key === verseKey)
    if (fallback?.text) segments.push(fallback.text.trim())
  }

  return segments.join(' ').trim()
}

const openAyahOptions = (verseKey: string) => {
  const [surah, ayah] = verseKey.split(':').map(Number)
  selectedSurahId.value = surah
  selectedAyah.value = ayah

  selectedAyahForDrawer.value = {
    surah,
    ayah,
    verse_key: verseKey,
    text: getAyahArabicText(verseKey)
  }
  showAyahDrawer.value = true
}

const playAyahFromDrawer = () => {
  showAyahDrawer.value = false
  if (selectedAyahForDrawer.value) {
    selectAyahFromTranslation(selectedAyahForDrawer.value.surah, selectedAyahForDrawer.value.ayah)
  }
}

const openTranslationForAyah = () => {
  showAyahDrawer.value = false
  showTranslationDrawer.value = true
}

const selectedAyahSurahName = computed(() => {
  if (!selectedAyahForDrawer.value) return ''
  const surah = surahOptions.value.find(s => s.id === selectedAyahForDrawer.value.surah)
  return surah ? surah.name_latin : ''
})

const isLastReadVerse = (verseKey: string): boolean =>
  lastReadAyah.value?.verse_key === verseKey

const isBookmarkedVerse = (verseKey: string): boolean =>
  isBookmarked(verseKey)

const loadLastReadAyah = () => {
  if (process.server) return
  try {
    const raw = localStorage.getItem('murojaah_last_read')
    lastReadAyah.value = raw ? JSON.parse(raw) : null
  } catch {
    lastReadAyah.value = null
  }
}

const selectedAyahBookmarked = computed(() =>
  selectedAyahForDrawer.value ? isBookmarked(selectedAyahForDrawer.value.verse_key) : false
)

const isSelectedAyahLastRead = computed(() =>
  selectedAyahForDrawer.value ? lastReadAyah.value?.verse_key === selectedAyahForDrawer.value.verse_key : false
)

const toggleLastRead = () => {
  if (!selectedAyahForDrawer.value) return
  if (isSelectedAyahLastRead.value) {
    // Remove last read
    localStorage.removeItem('murojaah_last_read')
    lastReadAyah.value = null
    showToast?.('Tandai terakhir baca dihapus', 'info')
  } else {
    // Set last read
    const payload: LastReadAyah = {
      surah: selectedAyahForDrawer.value.surah,
      ayah: selectedAyahForDrawer.value.ayah,
      verse_key: selectedAyahForDrawer.value.verse_key,
      page: pageNumber.value,
      surah_name: selectedAyahSurahName.value,
      text: selectedAyahForDrawer.value.text || getAyahArabicText(selectedAyahForDrawer.value.verse_key),
      marked_at: new Date().toISOString()
    }
    localStorage.setItem('murojaah_last_read', JSON.stringify(payload))
    lastReadAyah.value = payload
    showToast?.(`Surat ${selectedAyahSurahName.value} Ayat ${selectedAyahForDrawer.value.ayah} ditandai sebagai terakhir baca`, 'fluent')
  }
  showAyahDrawer.value = false
}

const toggleSelectedAyahBookmark = () => {
  if (!selectedAyahForDrawer.value) return
  const saved = toggleBookmark({
    surah: selectedAyahForDrawer.value.surah,
    ayah: selectedAyahForDrawer.value.ayah,
    verse_key: selectedAyahForDrawer.value.verse_key,
    page: pageNumber.value,
    surah_name: selectedAyahSurahName.value,
    text: selectedAyahForDrawer.value.text || getAyahArabicText(selectedAyahForDrawer.value.verse_key),
    translation: getAyahByVerseKey(selectedAyahForDrawer.value.verse_key)?.translation_id || ''
  })
  showToast?.(saved ? 'Ayat disimpan ke bookmark' : 'Bookmark ayat dihapus', saved ? 'fluent' : 'info')
  showAyahDrawer.value = false
}

const markLastRead = () => {
  if (!selectedAyahForDrawer.value) return
  const payload: LastReadAyah = {
    surah: selectedAyahForDrawer.value.surah,
    ayah: selectedAyahForDrawer.value.ayah,
    verse_key: selectedAyahForDrawer.value.verse_key,
    page: pageNumber.value,
    surah_name: selectedAyahSurahName.value,
    text: selectedAyahForDrawer.value.text || getAyahArabicText(selectedAyahForDrawer.value.verse_key),
    marked_at: new Date().toISOString()
  }
  localStorage.setItem('murojaah_last_read', JSON.stringify(payload))
  lastReadAyah.value = payload
  showToast?.(`Surat ${selectedAyahSurahName.value} Ayat ${selectedAyahForDrawer.value.ayah} ditandai sebagai terakhir baca`, 'fluent')
  showAyahDrawer.value = false
}

const fallbackCopyText = (text: string) => {
  const textArea = document.createElement('textarea')
  textArea.value = text
  textArea.style.top = '0'
  textArea.style.left = '0'
  textArea.style.position = 'fixed'
  textArea.style.opacity = '0'
  document.body.appendChild(textArea)
  textArea.focus()
  textArea.select()
  try {
    const successful = document.execCommand('copy')
    document.body.removeChild(textArea)
    return successful
  } catch (err) {
    document.body.removeChild(textArea)
    return false
  }
}

const shareAyah = async () => {
  if (!selectedAyahForDrawer.value) return
  const arabicText = selectedAyahForDrawer.value.text || getAyahArabicText(selectedAyahForDrawer.value.verse_key)
  const ayahUrl = `${window.location.origin}/mushaf/${pageNumber.value}?surah=${selectedAyahForDrawer.value.surah}&ayah=${selectedAyahForDrawer.value.ayah}`
  const text = `QS. ${selectedAyahSurahName.value} ${selectedAyahForDrawer.value.ayah}\n\n${arabicText}\n\n${ayahUrl}`
  showAyahDrawer.value = false
  if (navigator.share) {
    try {
      await navigator.share({
        title: `QS. ${selectedAyahSurahName.value} ${selectedAyahForDrawer.value.ayah}`,
        text
      })
    } catch (e) {
      console.error('Share failed', e)
    }
  } else {
    try {
      if (navigator.clipboard && navigator.clipboard.writeText) {
        await navigator.clipboard.writeText(text)
      } else {
        fallbackCopyText(text)
      }
      showToast?.('Teks ayat disalin untuk dibagikan!', 'fluent')
    } catch (e) {
      if (fallbackCopyText(text)) {
        showToast?.('Teks ayat disalin untuk dibagikan!', 'fluent')
      } else {
        console.error('Copy failed', e)
        showToast?.('Gagal menyiapkan ayat untuk dibagikan', 'forgot')
      }
    }
  }
}

const copyAyahText = async () => {
  if (selectedAyahForDrawer.value) {
    const textToCopy = selectedAyahForDrawer.value.text || getAyahArabicText(selectedAyahForDrawer.value.verse_key)
    if (!textToCopy) {
      showToast?.('Tidak ada teks Arab untuk disalin', 'doubtful')
      showAyahDrawer.value = false
      return
    }

    try {
      if (navigator.clipboard && navigator.clipboard.writeText) {
        await navigator.clipboard.writeText(textToCopy)
      } else {
        fallbackCopyText(textToCopy)
      }
      showToast?.('Teks Arab berhasil disalin!', 'fluent')
    } catch (e) {
      if (fallbackCopyText(textToCopy)) {
        showToast?.('Teks Arab berhasil disalin!', 'fluent')
      } else {
        console.error('Failed to copy', e)
        showToast?.('Gagal menyalin teks Arab', 'forgot')
      }
    }
  } else {
    showToast?.('Tidak ada teks Arab untuk disalin', 'doubtful')
  }
  showAyahDrawer.value = false
}

const selectedSurahId = ref(1)
const selectedAyah = ref(1)
const directPage = ref(1)
const showSurahPicker = ref(false)
const showAyahPicker = ref(false)
const showSectionPicker = ref(false)
const surahPickerListRef = ref<HTMLElement | null>(null)
const ayahPickerGridRef = ref<HTMLElement | null>(null)
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
const shouldAutoplayNextPage = useState<boolean>('shouldAutoplayNextPage', () => false)
const playingPageNumber = ref<number | null>(null)
const playingAyahsList = ref<any[]>([])

const { fetchTimings } = useMurottalAudio()
const loadedTimings = ref<any>(null)
const timingsLoading = ref(false)

const legacyAutoNextAyah = useCookie<boolean>('auto_next_ayah', {
  default: () => false,
  maxAge: 60 * 60 * 24 * 365,
  path: '/'
})

const listeningAutoNextAyah = useCookie<boolean>('listening_auto_next_ayah', {
  default: () => legacyAutoNextAyah.value ?? false,
  maxAge: 60 * 60 * 24 * 365,
  path: '/'
})

const mushafTheme = useCookie<'classic' | 'nabawiyyah' | 'dark'>('mushaf_theme', {
  default: () => 'classic',
  maxAge: 60 * 60 * 24 * 365,
  path: '/'
})

if (!['classic', 'nabawiyyah', 'dark'].includes(mushafTheme.value as string)) {
  mushafTheme.value = 'classic'
}

const mushafMode = useCookie<'digital' | 'klasik'>('mushaf_mode', {
  default: () => 'digital',
  maxAge: 60 * 60 * 24 * 365,
  path: '/'
})

//Konfirgurasi Frame Border
const SHOW_DIGITAL_FRAME = true


// Tipe koordinat per baris: [minX, minY, maxX, maxY]
interface KlassikAyahCoord {
  s: number  // sura_number
  a: number  // ayah_number
  l: [number, number, number, number][]  // array of lines: [minX, minY, maxX, maxY]
}

const klassikImgRef = ref<HTMLImageElement | null>(null)
const klassikPageCoords = ref<KlassikAyahCoord[] | null>(null)
const klassikHoverAyah = ref<KlassikAyahCoord | null>(null)
const klassikSelectedAyah = ref<KlassikAyahCoord | null>(null)
let klassikRawCoordsCache: Record<number, KlassikAyahCoord[]> = {}
let klassikCoordsCache: Record<number, KlassikAyahCoord[]> = {}

// Statically returns the exact scan dimensions of each page based on its type
const getKlassikPageDimensions = (pg: number): { w: number; h: number } => {
  if (pg === 1 || pg === 2) {
    return { w: 4884, h: 7665 }; // High-res cover templates
  }
  if (pg >= 3 && pg <= 61) {
    return { w: 1563, h: 2453 }; // Taller format standard pages (Juz 1-3)
  }
  return { w: 1563, h: 2234 }; // Normal standard format pages (Juz 4-30)
}

// Maps a raw coordinate (ax, ay) from Android 1920x3106 DB space
// to our WebP page coordinate space (1563x2234/2453 or 4884x7665 for page 1/2 covers)
const mapCoordToNativeImage = (ax: number, ay: number, pg: number, imgW: number, imgH: number): { x: number; y: number } => {
  let dbXMin, dbXMax, dbYMin, dbYMax;
  let ourXMin, ourXMax, ourYMin, ourYMax;

  if (pg === 1) {
    // Al-Fatihah cover page: Android DB bounds
    dbXMin = 391; dbXMax = 1534; dbYMin = 335; dbYMax = 1438;
    // Map to our 4884x7665 page 1 central text block:
    ourXMin = (1000 * imgW) / 4884;
    ourXMax = (3799 * imgW) / 4884;
    ourYMin = (1750 * imgH) / 7665;
    ourYMax = (6500 * imgH) / 7665;
  } else if (pg === 2) {
    // Al-Baqarah start cover page: Android DB bounds
    dbXMin = 393; dbXMax = 1533; dbYMin = 465; dbYMax = 1446;
    // Map to our 4884x7665 page 2 central text block:
    ourXMin = (1000 * imgW) / 4884;
    ourXMax = (3799 * imgW) / 4884;
    ourYMin = (1750 * imgH) / 7665;
    ourYMax = (6500 * imgH) / 7665;
  } else {
    dbXMin = 95; dbXMax = 1805; dbYMin = 45; dbYMax = 3013;
    
    // Map to our standard page text block (measured in native WebP page pixels):
    if (imgH === 2453) {
      ourXMin = 280; // calibrated to resolve left shift (Page 3-61)
      ourXMax = 1285; // calibrated to resolve left shift (Page 3-61)
      ourYMin = 406; // verified text start for taller format (Page 3-61)
      ourYMax = 2032; // verified text end for taller format (Page 3-61)
    } else {
      ourXMin = 260; // verified left edge of text column (Page 62-604)
      ourXMax = 1275; // verified right edge of text column (Page 62-604)
      ourYMin = 301; // verified text start for standard format (Page 62-604)
      ourYMax = 1933; // verified text end for standard format (Page 62-604)
    }
  }

  const scaleX = (ourXMax - ourXMin) / (dbXMax - dbXMin);
  const scaleY = (ourYMax - ourYMin) / (dbYMax - dbYMin);

  return {
    x: ourXMin + (ax - dbXMin) * scaleX,
    y: ourYMin + (ay - dbYMin) * scaleY
  };
}

// Load coordinate data for a given page and map it dynamically
const loadKlassikCoords = async (pg: number) => {
  if (!pg) return
  if (klassikCoordsCache[pg]) {
    klassikPageCoords.value = klassikCoordsCache[pg]
    return
  }
  
  let rawCoords = klassikRawCoordsCache[pg]
  if (!rawCoords) {
    try {
      const res = await fetch('/data/ayah_coords.json')
      const allCoords: Record<string, KlassikAyahCoord[]> = await res.json()
      for (const [k, v] of Object.entries(allCoords)) {
        klassikRawCoordsCache[Number(k)] = v
      }
      rawCoords = klassikRawCoordsCache[pg]
    } catch (e) {
      console.warn('Failed to load raw coords', e)
      klassikPageCoords.value = null
      return
    }
  }

  if (!rawCoords) {
    klassikPageCoords.value = null
    return
  }

  // Get dimensions statically to avoid layout shifting
  const { w: imgW, h: imgH } = getKlassikPageDimensions(pg);

  // Deep clone to apply page-specific coordinate corrections safely
  const processedCoords = JSON.parse(JSON.stringify(rawCoords)) as KlassikAyahCoord[]
  if (pg === 603) {
    // Correct database errors for Surah 111 (Al-Masad) Ayahs 1, 2, 3, 4, 5
    const a1 = processedCoords.find(x => x.s === 111 && x.a === 1)
    const a2 = processedCoords.find(x => x.s === 111 && x.a === 2)
    const a3 = processedCoords.find(x => x.s === 111 && x.a === 3)
    const a4 = processedCoords.find(x => x.s === 111 && x.a === 4)
    const a5 = processedCoords.find(x => x.s === 111 && x.a === 5)
    if (a1) a1.l = [[960, 2447, 1823, 2619]]
    if (a2) a2.l = [[115, 2450, 960, 2590]] // Remove segment 2 on line 2 (which is actually part of Ayah 3)
    if (a3) a3.l = [[850, 2658, 1817, 2824]] // Correct Y bounds and right margin (covers sayasla)
    if (a4) a4.l = [[118, 2648, 850, 2824]] // Covers wamraatuhu hammaalata
    if (a5) a5.l = [[105, 2858, 1821, 3026]] // Covers entire line 3 (fi jidiha)
  } else if (pg === 604) {
    // Correct database errors for Surah 112 (Al-Ikhlas) Ayahs 1, 2, 3, 4
    const a1 = processedCoords.find(x => x.s === 112 && x.a === 1)
    const a2 = processedCoords.find(x => x.s === 112 && x.a === 2)
    const a3 = processedCoords.find(x => x.s === 112 && x.a === 3)
    const a4 = processedCoords.find(x => x.s === 112 && x.a === 4)
    if (a1) a1.l = [[1260, 438, 1810, 590]]
    if (a2) a2.l = [[690, 438, 1260, 590]]
    if (a3) a3.l = [[110, 438, 690, 590]] // Remove segment 2 on line 2 (which is part of Ayah 4) and unify Y
    if (a4) a4.l = [[118, 632, 1808, 804]] // Covers entire line 2 (walam yakun)

    // Correct database errors and unify Y for Surah 113 (Al-Falaq) Ayahs 1, 2, 3, 4, 5
    const f1 = processedCoords.find(x => x.s === 113 && x.a === 1)
    const f2 = processedCoords.find(x => x.s === 113 && x.a === 2)
    const f3 = processedCoords.find(x => x.s === 113 && x.a === 3)
    const f4 = processedCoords.find(x => x.s === 113 && x.a === 4)
    const f5 = processedCoords.find(x => x.s === 113 && x.a === 5)
    
    // Line 1 of Al-Falaq: Unify Y to 1243-1407
    if (f1) f1.l = [[952, 1243, 1808, 1407]]
    if (f2) f2.l = [[311, 1243, 953, 1407]]
    if (f3) f3.l = [[133, 1243, 312, 1407], [992, 1451, 1809, 1616]] // Segment 1 Line 1, Segment 2 Line 2
    
    // Line 2 of Al-Falaq: Unify Y to 1451-1616
    if (f4) f4.l = [[126, 1451, 993, 1616]] // Remove erroneous segment 2 on Line 3
    
    // Line 3 of Al-Falaq: Unify Y to 1652-1810
    if (f5) f5.l = [[126, 1652, 1809, 1810]] // Entire Line 3 (wamin sharri hasidin...)

    // Correct database errors and unify Y for Surah 114 (An-Nas) Ayahs 1, 2, 3, 4, 5, 6
    const n1 = processedCoords.find(x => x.s === 114 && x.a === 1)
    const n2 = processedCoords.find(x => x.s === 114 && x.a === 2)
    const n3 = processedCoords.find(x => x.s === 114 && x.a === 3)
    const n4 = processedCoords.find(x => x.s === 114 && x.a === 4)
    const n5 = processedCoords.find(x => x.s === 114 && x.a === 5)
    const n6 = processedCoords.find(x => x.s === 114 && x.a === 6)

    // Line 1 of An-Nas: Unify Y to 2248-2408
    if (n1) n1.l = [[971, 2248, 1809, 2408]]
    if (n2) n2.l = [[319, 2248, 972, 2408]]
    if (n3) n3.l = [[127, 2248, 320, 2408], [1397, 2440, 1805, 2616]] // Segment 1 Line 1, Segment 2 Line 2

    // Line 2 of An-Nas: Unify Y to 2440-2616
    if (n4) n4.l = [[311, 2440, 1398, 2616]]
    if (n5) n5.l = [[126, 2440, 312, 2616], [352, 2657, 1571, 2815]] // Segment 1 Line 2, Segment 2 Line 3
  }

  klassikCoordsCache[pg] = processedCoords.map(ayah => {
    // 1. Map to raw coordinates
    const mappedLines = ayah.l.map(([ax, ay, bx, by]) => {
      let surahOffset = 0
      if (pg === 603) {
        if (ayah.s === 110) surahOffset = -15 // Shift Surah An-Nasr upwards moderately
        if (ayah.s === 111) surahOffset = -30 // Shift Surah Al-Masad upwards moderately
      } else if (pg === 604) {
        if (ayah.s === 112) surahOffset = 25 // Shift Surah Al-Ikhlas downwards moderately
        if (ayah.s === 113) surahOffset = 10
        if (ayah.s === 114) surahOffset = -10
      }

      const p1 = mapCoordToNativeImage(ax, ay + surahOffset, pg, imgW, imgH)
      const p2 = mapCoordToNativeImage(bx, by + surahOffset, pg, imgW, imgH)
      
      // Calculate dynamic horizontal boundaries
      const ourXMinLimit = (imgH === 2453) ? 280 : 260;
      const ourXMaxLimit = (imgH === 2453) ? 1285 : 1275;

      // Detect if segment borders touch the left/right text margins in DB space (within 100px)
      const dbXMinLimit = 95;
      const dbXMaxLimit = 1805;

      const isLeftOuter = (ax < dbXMinLimit + 100);
      const isRightOuter = (bx > dbXMaxLimit - 100);

      // Expand outer border segments to touch margins; shrink inner segments slightly to avoid overlapping adjacent words
      const paddingLeft = isLeftOuter ? -12 : 12;
      const paddingRight = isRightOuter ? 12 : -12;

      // Handle rare case where segment is extremely narrow and padding overlaps itself
      let x1 = Math.round(p1.x) + paddingLeft;
      let x2 = Math.round(p2.x) + paddingRight;
      if (x1 >= x2) {
        // Keep original bounds for narrow elements to prevent visual flip
        x1 = Math.round(p1.x);
        x2 = Math.round(p2.x);
      }

      x1 = Math.max(ourXMinLimit, x1);
      x2 = Math.min(ourXMaxLimit, x2);

      return {
        x1,
        y1: Math.round(p1.y),
        x2,
        y2: Math.round(p2.y)
      }
    })

    // 2. Adjust vertical bounds of consecutive lines to touch exactly
    const n = mappedLines.length
    for (let i = 0; i < n; i++) {
      // Expand outer edges slightly for a richer highlight padding
      if (i === 0) {
        mappedLines[i].y1 -= 6
      }
      if (i === n - 1) {
        mappedLines[i].y2 += 6
      }

      // Connect consecutive lines
      if (i < n - 1) {
        const mid = Math.round((mappedLines[i].y2 + mappedLines[i+1].y1) / 2)
        mappedLines[i].y2 = mid
        mappedLines[i+1].y1 = mid
      }
    }

    // Convert back to [x, y, x2, y2] array format for template rect loop
    const linesArray = mappedLines.map(line => [
      line.x1,
      line.y1,
      line.x2,
      line.y2
    ])

    return {
      ...ayah,
      l: linesArray
    }
  })
  klassikPageCoords.value = klassikCoordsCache[pg]
}

// Returns layout configuration to crop white outer margins in CSS dynamically
const getKlassikLayout = (pg: number) => {
  if (!pg) {
    return {
      wrapperStyle: {},
      imgStyle: {},
      viewBox: '0 0 1563 2234'
    }
  }

  const { w: imgW, h: imgH } = getKlassikPageDimensions(pg);
  let xMin, yMin, frameW, frameH;

  if (pg === 1 || pg === 2) {
    // Page 1 & 2 Cover layout: crop outer whitespace, leave ornate cover frame
    xMin = (96 * imgW) / 1563;
    yMin = 0;
    frameW = (1369 * imgW) / 1563;
    frameH = imgH;
  } else {
    // Standard Pages: crop outer white margins, keep printed ornate border
    if (imgH === 2453) {
      // Taller format pages (Juz 1-3)
      xMin = 173;
      yMin = 120; // Show the printed paper header at the top
      frameW = 1216;
      frameH = 2078; // Expand height to keep bottom border aligned
    } else {
      // Normal format pages (Juz 4-30)
      xMin = 90;
      yMin = 50; // Show the printed paper header at the top
      frameW = 1381;
      frameH = 2038; // Expand height to keep bottom border aligned
    }
  }

  // Calculate percentage crop styling for absolute positioned image inside the container wrapper
  const scaleX = imgW / frameW;
  const scaleY = imgH / frameH;
  const leftPct = -(xMin / frameW) * 100;
  const topPct = -(yMin / frameH) * 100;

  return {
    wrapperStyle: {
      position: 'relative',
      width: '100%',
      aspectRatio: `${frameW} / ${frameH}`,
      overflow: 'hidden',
      display: 'block'
    },
    imgStyle: {
      position: 'absolute',
      width: `${scaleX * 100}%`,
      height: `${scaleY * 100}%`,
      left: `${leftPct}%`,
      top: `${topPct}%`
    },
    viewBox: `${xMin} ${yMin} ${frameW} ${frameH}`
  }
}

// When the center image loads, trigger coordinate loading
const onClassicImgLoad = () => {
  classicImgLoaded.value = true
  loadKlassikCoords(pageNumber.value)
}

// Convert pointer event to local coordinate space matching the SVG viewBox
const getKlassikSvgPoint = (e: PointerEvent | MouseEvent): { x: number; y: number } | null => {
  const wrapper = document.querySelector(`.mushaf-classic-wrapper[data-page="${pageNumber.value}"]`)
  if (!wrapper) return null
  const rect = wrapper.getBoundingClientRect()
  const relX = e.clientX - rect.left
  const relY = e.clientY - rect.top
  
  const layout = getKlassikLayout(pageNumber.value)
  // Parse viewBox coordinates xmin, ymin, width, height
  const parts = layout.viewBox.split(' ').map(Number)
  const xMin = parts[0]
  const yMin = parts[1]
  const viewBoxW = parts[2]
  const viewBoxH = parts[3]
  
  return {
    x: xMin + (relX / rect.width) * viewBoxW,
    y: yMin + (relY / rect.height) * viewBoxH
  }
}

// Find which ayah's bounding box contains the given point
const findKlassikAyah = (x: number, y: number): KlassikAyahCoord | null => {
  if (!klassikPageCoords.value) return null
  // Find ayah that contains this point in any of its lines
  for (const ayah of klassikPageCoords.value) {
    for (const [ax, ay, bx, by] of ayah.l) {
      // Add small padding to hit area for easier selection
      if (x >= ax - 8 && x <= bx + 8 && y >= ay - 6 && y <= by + 6) {
        return ayah
      }
    }
  }
  return null
}

let classicPointerTimer: number | null = null
let classicPointerStart = { x: 0, y: 0 }
let isClassicLongPress = false

const handleClassicPointerDown = (e: PointerEvent) => {
  // Only handle primary button clicks/touches
  if (e.button !== 0) return
  
  classicPointerStart = { x: e.clientX, y: e.clientY }
  isClassicLongPress = false

  // Set timeout for 500ms (standard long press duration)
  classicPointerTimer = window.setTimeout(() => {
    isClassicLongPress = true
    
    // Perform long press action: select ayah
    const pt = getKlassikSvgPoint(e)
    if (pt) {
      const found = findKlassikAyah(pt.x, pt.y)
      if (found) {
        klassikSelectedAyah.value = found
        klassikHoverAyah.value = null
        openAyahOptions(`${found.s}:${found.a}`)
      }
    }
  }, 500)
}

const handleClassicPointerMove = (e: PointerEvent) => {
  if (classicPointerTimer) {
    const dx = e.clientX - classicPointerStart.x
    const dy = e.clientY - classicPointerStart.y
    // If moved by more than 10px, cancel the long press timer (it is a scroll or swipe!)
    if (Math.hypot(dx, dy) > 10) {
      window.clearTimeout(classicPointerTimer)
      classicPointerTimer = null
    }
  }
}

const handleClassicPointerUp = (e: PointerEvent) => {
  if (classicPointerTimer) {
    window.clearTimeout(classicPointerTimer)
    classicPointerTimer = null
  }

  // If it was not a long press, treat as a single tap: toggle header/footer
  if (!isClassicLongPress) {
    toggleFullscreen()
  }
  
  klassikHoverAyah.value = null
  isClassicLongPress = false
}

const handleClassicPointerCancel = () => {
  if (classicPointerTimer) {
    window.clearTimeout(classicPointerTimer)
    classicPointerTimer = null
  }
  klassikHoverAyah.value = null
  isClassicLongPress = false
}

const handleClassicHover = (e: MouseEvent) => {
  const pt = getKlassikSvgPoint(e)
  if (!pt) return
  klassikHoverAyah.value = findKlassikAyah(pt.x, pt.y)
}


watch(mushafTheme, () => {
  prefetchPages()
})

let playerAudio: HTMLAudioElement | null = null
let lastSyncedVerseKey = ''
let isContinuousPageTransition = false
let preloadedAudio: HTMLAudioElement | null = null
let preloadedAudioUrl = ''
let idleReturnTimer: number | null = null

const showAudioSettings = ref(false)
const isCustomRangeActive = useState<boolean>('mushafMurottalRangeActive', () => false)
const localRepeatCount = ref(1)
const currentLocalAyahRepeatCount = ref(1)

const activePickerType = ref<'none' | 'startSurah' | 'startAyah' | 'endSurah' | 'endAyah' | 'ayahRepeat' | 'rangeRepeat'>('none')
const settingsSurahSearch = ref('')

const isFullscreenMode = useState<boolean>('mushafFullscreenMode', () => false)
const isSettingsInitialized = useState<boolean>('mushafMurottalSettingsInitialized', () => false)

const settingsStartSurah = useState<number>('mushafMurottalStartSurah', () => 1)
const settingsStartAyah = useState<number>('mushafMurottalStartAyah', () => 1)
const settingsEndSurah = useState<number>('mushafMurottalEndSurah', () => 1)
const settingsEndAyah = useState<number>('mushafMurottalEndAyah', () => 1)
const settingsAyahRepeat = useState<number>('mushafMurottalAyahRepeat', () => 1)
const settingsRangeRepeat = useState<number>('mushafMurottalRangeRepeat', () => 1)

const activeMurottalQueue = useState<{ surah: number; ayah: number; verse_key: string }[]>('mushafMurottalQueue', () => [])
const queueIndex = useState<number>('mushafMurottalQueueIndex', () => 0)
const currentAyahRepeatCount = useState<number>('mushafMurottalAyahRepeatCount', () => 1)
const currentRangeRepeatCount = useState<number>('mushafMurottalRangeRepeatCount', () => 1)

const pageNumber = computed(() => {
  const value = Number(route.params.pageNumber)
  return Number.isFinite(value) ? Math.min(604, Math.max(1, Math.trunc(value))) : 1
})

watch(pageNumber, () => {
  klassikSelectedAyah.value = null
  klassikHoverAyah.value = null
  klassikPageCoords.value = klassikCoordsCache[pageNumber.value] || null
  if (!klassikPageCoords.value && mushafMode.value === 'klasik') {
    loadKlassikCoords(pageNumber.value)
  }
})

watch(mushafMode, (val) => {
  klassikSelectedAyah.value = null
  klassikHoverAyah.value = null
  if (val === 'klasik') {
    loadKlassikCoords(pageNumber.value)
  }
})

// Close ayah drawer also clears selected highlight
watch(showAyahDrawer, (val) => {
  if (!val) {
    klassikSelectedAyah.value = null
  }
})

const carouselPages = computed(() => [
  pageNumber.value < 604 ? pageNumber.value + 1 : null,
  pageNumber.value,
  pageNumber.value > 1 ? pageNumber.value - 1 : null,
])

const activeHighlightVerse = computed(() => {
  if (isPlaying.value) {
    if (isCustomRangeActive.value) {
      const qv = activeMurottalQueue.value[queueIndex.value]
      if (qv) return { surah: qv.surah, ayah: qv.ayah }
    } else {
      const ayah = playerAyah.value
      if (ayah) {
        const [surah, number] = ayah.verse_key.split(':').map(Number)
        return { surah, ayah: number }
      }
    }
  }
  return { surah: selectedSurahId.value || 0, ayah: selectedAyah.value || 0 }
})

const klassikActiveHighlight = computed(() => {
  // 1. If audio is actively playing, prioritize the playing verse
  if (isPlaying.value && activeHighlightVerse.value && klassikPageCoords.value) {
    const { surah, ayah } = activeHighlightVerse.value
    const found = klassikPageCoords.value.find(x => x.s === surah && x.a === ayah)
    if (found) return found
  }
  // 2. Otherwise, fall back to selected tapped verse
  if (klassikSelectedAyah.value) {
    return klassikSelectedAyah.value
  }
  // 3. Fallback: if showAyahDrawer is open but not playing, highlight selectedAyah
  if (showAyahDrawer.value && klassikPageCoords.value) {
    const found = klassikPageCoords.value.find(x => x.s === selectedSurahId.value && x.a === selectedAyah.value)
    if (found) return found
  }
  return null
})

const activeVerseTranslation = computed(() => {
  if (!pageData.value || !pageData.value.ayahs) return ''
  const active = pageData.value.ayahs.find(
    x => x.verse_key === `${activeHighlightVerse.value.surah}:${activeHighlightVerse.value.ayah}`
  )
  return active?.translation_id || 'Terjemahan tidak tersedia untuk ayat ini.'
})

const selectAyahFromTranslation = (surahId: number, ayahNumber: number) => {
  // If the audio is already playing this exact verse, do not restart it
  if (isPlaying.value && activeHighlightVerse.value.surah === surahId && activeHighlightVerse.value.ayah === ayahNumber) {
    return
  }

  selectedAyah.value = ayahNumber
  selectedSurahId.value = surahId

  if (!isCustomRangeActive.value && pageData.value?.ayahs) {
    const idx = pageData.value.ayahs.findIndex(x => x.verse_key === `${surahId}:${ayahNumber}`)
    if (idx !== -1) {
      playingPageNumber.value = pageNumber.value
      playingAyahsList.value = [...pageData.value.ayahs]
      playerAyahIndex.value = idx
      playPlayerAyah()
    }
  } else if (isCustomRangeActive.value) {
    const idx = activeMurottalQueue.value.findIndex(x => x.surah === surahId && x.ayah === ayahNumber)
    if (idx !== -1) {
      queueIndex.value = idx
      currentLocalAyahRepeatCount.value = 1
      playPlayerAyah()
    } else {
      isCustomRangeActive.value = false
      const pgIdx = pageData.value?.ayahs?.findIndex(x => x.verse_key === `${surahId}:${ayahNumber}`) ?? -1
      if (pgIdx !== -1 && pageData.value?.ayahs) {
        playingPageNumber.value = pageNumber.value
        playingAyahsList.value = [...pageData.value.ayahs]
        playerAyahIndex.value = pgIdx
        playPlayerAyah()
      }
    }
  }
}

const translationSheet = useBottomSheet({
  mode: 'resize',
  initialHeight: 320,
  minHeight: 130,
  maxHeight: () => Math.round(window.innerHeight * 0.88),
  closeThreshold: 70,
  onClose: () => { showTranslationDrawer.value = false },
})

const ayahDrawerSheet = useBottomSheet({
  mode: 'dismiss',
  closeThreshold: 80,
  onClose: () => { showAyahDrawer.value = false },
})

const navigatorSheet = useBottomSheet({
  mode: 'dismiss',
  closeThreshold: 80,
  onClose: () => { navigatorOpen.value = false },
})

const qariPickerSheet = useBottomSheet({
  mode: 'dismiss',
  closeThreshold: 80,
  onClose: () => { showQariPicker.value = false },
})

const audioSettingsSheet = useBottomSheet({
  mode: 'dismiss',
  closeThreshold: 80,
  onClose: () => { showAudioSettings.value = false },
})

const editionPickerSheet = useBottomSheet({
  mode: 'dismiss',
  closeThreshold: 80,
  onClose: () => { showEditionPicker.value = false },
})

const legendSheet = useBottomSheet({
  mode: 'dismiss',
  closeThreshold: 80,
  onClose: () => { showTajweedLegendSheet.value = false },
})

const displaySettingsSheet = useBottomSheet({
  mode: 'dismiss',
  closeThreshold: 80,
  onClose: () => { showDisplaySettingsSheet.value = false },
})

watch(showTranslationDrawer, async (val) => {
  if (val) {
    translationSheet.reset()
    fetchTransliterations()
    if (selectedEdition.value.id !== 'local') {
      loadEdition(selectedEdition.value.id)
    }
    if (pageData.value?.ayahs.length) {
      currentScrollSurahId.value = pageData.value.ayahs[0].surah_id
      currentScrollJuz.value = pageData.value.ayahs[0].juz
    }
    await nextTick()
    setTimeout(() => {
      if (activeTranslationItemRef.value) {
        activeTranslationItemRef.value.scrollIntoView({ behavior: 'smooth', block: 'center' })
      }
    }, 150)
  }
})

watch([pageNumber, selectedEdition], async ([newPage, newEdition]) => {
  if (newEdition.id !== 'local') {
    await loadEdition(newEdition.id)
  }
})

watch(showAyahDrawer, (val) => { if (val) ayahDrawerSheet.reset() })
watch(navigatorOpen, (val) => { if (val) navigatorSheet.reset() })
watch(showTajweedLegendSheet, (val) => { if (val) legendSheet.reset() })
watch(showDisplaySettingsSheet, (val) => { if (val) displaySettingsSheet.reset() })
watch(showQariPicker, (val) => { if (val) qariPickerSheet.reset() })
watch(showAudioSettings, (val) => { if (val) audioSettingsSheet.reset() })
watch(showEditionPicker, (val) => { if (val) editionPickerSheet.reset() })

const trackStyle = computed(() => ({ transform: `translate3d(calc(-33.333333% + ${swipeOffset.value}px), 0, 0)` }))
const isSwipeActive = computed(() => swipeAnimating.value || Math.abs(swipeOffset.value) > 1)

const qcfPageCache = useState<Record<number, MushafPageData>>('qcfPageCache', () => ({}))
const qcfLoading = ref(!qcfPageCache.value[pageNumber.value])
let qcfFitFrame: number | null = null

const fitQcfLines = async () => {
  if (process.server) return
  await nextTick()
  if (qcfFitFrame !== null) cancelAnimationFrame(qcfFitFrame)

  qcfFitFrame = requestAnimationFrame(() => {
    document.querySelectorAll<HTMLElement>('.mushaf-line--qcf').forEach(line => {
      const content = line.querySelector<HTMLElement>('.mushaf-line__qcf-content')
      if (!content) return

      if (line.closest('.mushaf-page-box--opening, .mushaf-page-box--closing')) {
        content.style.removeProperty('--qcf-line-scale')
        content.style.width = ''
        content.style.justifyContent = ''
        return
      }

      // Reset styles to measure the clean natural width
      content.style.width = 'max-content'
      content.style.justifyContent = 'center'
      content.style.transform = 'none'
      const naturalWidth = content.getBoundingClientRect().width
      content.style.transform = ''

      const availableWidth = line.clientWidth
      if (!naturalWidth || !availableWidth) return

      const ratio = availableWidth / naturalWidth

      if (ratio < 1.0) {
        // Line overflows: squeeze slightly (scaleX) to fit exactly
        const scale = Math.max(0.90, ratio)
        content.style.width = 'max-content'
        content.style.justifyContent = 'center'
        content.style.setProperty('--qcf-line-scale', scale.toFixed(4))
      } else if (ratio <= 1.28) {
        // Normal line: justify perfectly using flex space-between (no horizontal stretching/distortion)
        content.style.width = '100%'
        content.style.justifyContent = 'space-between'
        content.style.setProperty('--qcf-line-scale', '1')
      } else {
        // Short line (e.g., end of surah): center naturally without stretching
        content.style.width = 'max-content'
        content.style.justifyContent = 'center'
        content.style.setProperty('--qcf-line-scale', '1')
      }
    })
    qcfFitFrame = null
  })
}

const loadQcfPage = async (page: number) => {
  if (page < 1 || page > 604 || qcfPageCache.value[page]) return
  try {
    const data = await apiFetch<{ data: MushafPageData }>(`/mushaf/pages/${page}`)
    if (data?.data?.lines) {
      qcfPageCache.value[page] = data.data
      fitQcfLines()
    }
  } catch (e) {
    console.error(`[QCF] Failed to load page ${page}`, e)
  }
}

const loadQcfPages = async () => {
  const needsLoading = !qcfPageCache.value[pageNumber.value]
  if (needsLoading) {
    qcfLoading.value = true
  }
  await loadQcfPage(pageNumber.value)
  if (needsLoading) {
    qcfLoading.value = false
  }
  fitQcfLines()
  setTimeout(fitQcfLines, 100) // Ensure layout settles after DOM update
  loadQcfPage(Math.max(1, pageNumber.value - 1))
  loadQcfPage(Math.min(604, pageNumber.value + 1))
}

const loadedFonts = ref<Set<number>>(new Set())
const loadQcfFont = (page: number) => {
  if (!page || page < 1 || page > 604) return
  if (loadedFonts.value.has(page) || process.server) return
  if (typeof document === 'undefined') return
  loadedFonts.value.add(page)
  const fontUrl = `/fonts/qcf/p${page}.woff2?v=2`
  const style = document.createElement('style')
  style.textContent = `@font-face { font-family: "QCF-p${page}"; src: url("${fontUrl}") format("woff2"); font-display: block; }`
  document.head.appendChild(style)
  document.fonts?.load(`16px "QCF-p${page}"`).then(() => {
    fitQcfLines()
    setTimeout(fitQcfLines, 100) // Recalculate once browser settles the new font layout
    setTimeout(fitQcfLines, 400) // Safety check
  })
}

const qcfFontFamily = (page: number): string => {
  if (!page) return 'serif'
  if (process.client) loadQcfFont(page)
  return `"QCF-p${page}", serif`
}

const getPageLines = (page: number): MushafLine[] => {
  const lines = qcfPageCache.value[page]?.lines || pageData.value?.lines || []
  return page >= 600
    ? [...lines].sort((a, b) => Number(a.line_number) - Number(b.line_number))
    : lines
}

interface GroupedMushafWord extends MushafWord {
  attachedOrnament?: MushafWord | null
}

const isMushafEndWord = (word: Partial<MushafWord> | null | undefined): boolean => {
  const type = String(word?.char_type || '').trim().toLowerCase()
  return type === 'end' || type.includes('ayah') || type.includes('verse') || type.includes('pause')
}

const getGroupedWords = (words: MushafWord[]): GroupedMushafWord[] => {
  const grouped: GroupedMushafWord[] = []
  for (let i = 0; i < words.length; i++) {
    const word = words[i]
    if (isMushafEndWord(word)) {
      if (grouped.length === 0) {
        grouped.push({ ...word, attachedOrnament: word, code_v2: '' })
      } else {
        grouped[grouped.length - 1].attachedOrnament = word
      }
    } else {
      grouped.push({ ...word, attachedOrnament: null })
    }
  }
  return grouped
}

const formatArabicNumber = (value: number): string =>
  new Intl.NumberFormat('ar-EG', { useGrouping: false }).format(value)

const ayahNumberFromVerseKey = (verseKey: string): number =>
  Number(verseKey.split(':')[1] || 0)

const tajweedClassesForWord = (text?: string | null): string[] => {
  if (!text) return []
  const rules = new Set<string>()
  for (const match of text.matchAll(/\[([a-z])(?::\d+)?\[/gi)) {
    rules.add(match[1].toLowerCase())
  }
  const priority = ['q', 'f', 'c', 'i', 'g', 'u', 'a', 'w', 'm', 'o', 'p', 'n']
  const rule = priority.find(candidate => rules.has(candidate))
  return rule ? [`tajweed-${rule}`] : []
}

const formatTajweedText = (text: string): string => {
  if (!text) return ''

  let cleanText = text
    .replace(/^\uFEFF/, '')
    .replace(/\u0672/g, '\u0670')
    .replace(/[\u0610-\u061A\u06D6-\u06E4\u06E7-\u06ED\u08A0-\u08FF]/g, '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')

  const tajweedPattern = /\[([a-z0-9:]+)\[([^\]]*?)(?:\]|$)/g

  const removeMarkupArtifacts = (value: string): string => value
    .replace(/\[?[a-z](?::\d+)?\[/gi, '')
    .replace(/\]?[a-z](?::\d+)?\]/gi, '')
    .replace(/\b[a-z]:\d+\b/gi, '')
    .replace(/[\[\]]/g, '')

  return removeMarkupArtifacts(cleanText.replace(tajweedPattern, (_match, ruleInfo, characters) => {
    if (!characters) return ''
    const rule = String(ruleInfo).split(':')[0]
    return `<span class="tajweed-${rule}">${characters}</span>`
  }))
}

const surahNameGlyph = (surahNumber?: number): string =>
  `surah${String(surahNumber || 1).padStart(3, '0')}`

const getPageJuz = (page: number): string => {
  const juz = qcfPageCache.value[page]?.juz || pageData.value?.juz || []
  return juz.length ? 'Juz ' + juz.join('-') : ''
}

const getPageSurah = (page: number): string => {
  const surahs = qcfPageCache.value[page]?.surahs || pageData.value?.surahs || []
  if (!surahs.length) return ''
  return surahs[0].number + '. ' + surahs[0].name_latin
}

const getPageSurahCount = (page: number): number =>
  (qcfPageCache.value[page]?.surahs || pageData.value?.surahs || []).length

const hasBottomSurahBanner = (page: number): boolean => {
  const surahs = qcfPageCache.value[page]?.surahs || pageData.value?.surahs || []
  return surahs.some(s => s.starts_at_line !== null && s.starts_at_line !== undefined && Number(s.starts_at_line) >= 11)
}

const getSurahBannerAtLine = (lineNumber: any, page: number): MushafSurah | null => {
  const surahs = qcfPageCache.value[page]?.surahs || pageData.value?.surahs || []
  return surahs.find(s => s.starts_at_line !== null && s.starts_at_line !== undefined && Number(s.starts_at_line) === Number(lineNumber)) || null
}

const getBismillahAtLine = (lineNumber: any, page: number): MushafSurah | null => {
  const surahs = qcfPageCache.value[page]?.surahs || pageData.value?.surahs || []
  const found = surahs.find(s => s.bismillah_at_line !== null && s.bismillah_at_line !== undefined && Number(s.bismillah_at_line) === Number(lineNumber)) || null
  if (found && (found.number === 1 || found.number === 9)) return null
  return found
}

const isLastLineOfSurah = (lineNumber: number, page: number): boolean => {
  const surahs = qcfPageCache.value[page]?.surahs || pageData.value?.surahs || []
  const nextSurah = surahs.find(s => s.starts_at_line !== null && Number(s.starts_at_line) > Number(lineNumber))
  if (!nextSurah) return false

  const lines = qcfPageCache.value[page]?.lines || pageData.value?.lines || []
  const hasLineInBetween = lines.some(l => Number(l.line_number) > Number(lineNumber) && Number(l.line_number) < Number(nextSurah.starts_at_line))
  return !hasLineInBetween
}

const getSurahForAyah = (surahId: number): MushafSurah | undefined => {
  const surahs = pageData.value?.surahs || []
  return surahs.find(s => s.id === surahId)
}

const isActiveVerse = (verseKey: string): boolean => {
  const active = activeHighlightVerse.value
  return verseKey === `${active.surah}:${active.ayah}`
}

const getVerseProgressClass = (verseKey: string): string => {
  const progress = pageData.value?.verse_progress?.[verseKey] || 'unreviewed'
  if (progress === 'fluent') return 'mushaf-word--fluent'
  if (progress === 'doubtful') return 'mushaf-word--doubtful'
  if (progress === 'forgot') return 'mushaf-word--forgot'
  return ''
}

watch(activeHighlightVerse, async (newVerse) => {
  if (process.server) return
  if (!newVerse || !newVerse.surah || !newVerse.ayah) return
  await nextTick()
  const key = `${newVerse.surah}:${newVerse.ayah}`
  const centerSlide = document.querySelector('.mushaf-slide:nth-child(2)')
  const el = centerSlide?.querySelector(`.mushaf-word--active[data-verse="${key}"]`) as HTMLElement | null
  if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' })

  // Auto-scroll the full translation list to center
  if (showTranslationDrawer.value && activeTranslationItemRef.value && translationListRef.value) {
    activeTranslationItemRef.value.scrollIntoView({ behavior: 'smooth', block: 'center' })
  }

  // Auto-update the single Ayah Options Drawer (Terjemahan) to follow Murottal
  if (showAyahDrawer.value) {
    const activeAyahData = pageData.value?.ayahs.find(a => a.surah_id === newVerse.surah && a.ayah_number === newVerse.ayah)
    if (activeAyahData) {
      selectedAyahForDrawer.value = {
        surah: activeAyahData.surah_id,
        ayah: activeAyahData.ayah_number,
        verse_key: activeAyahData.verse_key,
        text: getAyahArabicText(activeAyahData.verse_key)
      }
    }
  }
}, { deep: true })
const primarySurah = computed(() => {
  if (!pageData.value?.surahs) return null

  const querySurahId = Number(route.query.surah)
  if (querySurahId) {
    const found = pageData.value.surahs.find(s => s.id === querySurahId || s.number === querySurahId)
    if (found) return found
  }

  const startingSurah = pageData.value.surahs.find(s => s.starts_at_line !== null && s.starts_at_line !== undefined)
  if (startingSurah) return startingSurah

  return pageData.value.surahs[0] || null
})
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
const navigationAvailability = computed(() => ({ surah: '114', juz: '1-30', hizb: '1-60', manzil: '1-7', page: '1-604' }[navigationType.value]))
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
const playerAyahs = computed(() => {
  if (playingAyahsList.value.length > 0) {
    return playingAyahsList.value
  }
  return pageData.value?.ayahs || []
})

const playerAyah = computed(() => {
  if (isCustomRangeActive.value && activeMurottalQueue.value[queueIndex.value]) {
    const qv = activeMurottalQueue.value[queueIndex.value]
    const list = playingAyahsList.value.length > 0 ? playingAyahsList.value : (pageData.value?.ayahs || [])
    const found = list.find(x => x.verse_key === qv.verse_key)
    if (found) return found
    return { verse_key: qv.verse_key }
  }
  return playerAyahs.value[playerAyahIndex.value] || playerAyahs.value[0]
})

const playerAyahLabel = computed(() => {
  const pNum = playingPageNumber.value || pageNumber.value
  if (isCustomRangeActive.value && activeMurottalQueue.value[queueIndex.value]) {
    const qv = activeMurottalQueue.value[queueIndex.value]
    return `Ayat ${qv.verse_key} \u00B7 Halaman ${pNum}`
  }
  return playerAyah.value ? `Ayat ${playerAyah.value.verse_key} \u00B7 Halaman ${pNum}` : `Halaman ${pNum}`
})

const playerProgress = computed(() => playerDuration.value > 0 ? Math.min(100, (playerCurrentTime.value / playerDuration.value) * 100) : 0)

const findSectionForPage = (starts: number[]) => {
  let value = 1
  starts.forEach((start, index) => { if (start <= pageNumber.value) value = index + 1 })
  return value
}
const localImageUrl = (page: number) => {
  return '/images/mushaf/' + String(page).padStart(3, '0') + '.webp?v=2'
}

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
    const cached = qcfPageCache.value[pageNumber.value]
    if (cached) {
      pageData.value = cached as any
    } else {
      const response = await apiFetch<{ data: MushafPageData }>('/mushaf/pages/' + pageNumber.value)
      pageData.value = response.data
      qcfPageCache.value[pageNumber.value] = response.data as any
    }

    if (isPlaying.value) {
      let playingSurahNum: number | null = null
      if (loadedTimings.value && !loadedTimings.value.fallback) {
        playingSurahNum = Number(loadedTimings.value.key.split('_')[2])
      } else if (playerAyah.value) {
        const [sNum] = playerAyah.value.verse_key.split(':').map(Number)
        playingSurahNum = sNum
      }

      if (playingSurahNum) {
        const isPlayingSurahOnNewPage = pageData.value?.surahs.some(s => s.number === playingSurahNum || s.id === playingSurahNum)
        if (!isPlayingSurahOnNewPage) {
          stopPlayer()
          loadedTimings.value = null
        }
      }
    }

    await loadSurahOptions()
    const querySurah = Number(route.query.surah)
    const queryAyah = Number(route.query.ayah)
    if (querySurah) {
      const surahMeta = surahOptions.value.find(s => s.number === querySurah || s.id === querySurah)
      if (surahMeta) {
        settingsStartSurah.value = surahMeta.number
        settingsEndSurah.value = surahMeta.number
        settingsStartAyah.value = queryAyah || 1
        settingsEndAyah.value = surahMeta.total_ayah
        isSettingsInitialized.value = true
      }
    } else {
      const hasValidSettings = Number.isFinite(settingsStartSurah.value) && Number.isFinite(settingsEndSurah.value)
      if ((!isSettingsInitialized.value || !hasValidSettings) && pageData.value?.ayahs.length) {
        const currentSurah = pageData.value.ayahs[0]
        const sNum = Number(currentSurah.verse_key.split(':')[0])
        settingsStartSurah.value = sNum
        settingsEndSurah.value = sNum
        const surahMeta = surahOptions.value.find(s => s.number === sNum)
        settingsStartAyah.value = 1
        settingsEndAyah.value = surahMeta ? surahMeta.total_ayah : 7
        isSettingsInitialized.value = true
      }
    }

    if (shouldAutoplayNextPage.value) {
      shouldAutoplayNextPage.value = false
      if (!isCustomRangeActive.value) {
        playerAyahIndex.value = 0
        playingPageNumber.value = pageNumber.value
        playingAyahsList.value = [...(pageData.value?.ayahs || [])]
      }
      playPlayerAyah()
    } else if (isPlaying.value && loadedTimings.value && !loadedTimings.value.fallback) {
      playingPageNumber.value = pageNumber.value
      playingAyahsList.value = [...(pageData.value?.ayahs || [])]
      const timeMs = (playerAudio?.currentTime || 0) * 1000
      const activeT = loadedTimings.value.verse_timings.find((t: any) => timeMs >= t.timestamp_from && timeMs < t.timestamp_to)
      if (activeT) {
        const idx = playingAyahsList.value.findIndex(x => x.verse_key === activeT.verse_key)
        if (idx !== -1) {
          playerAyahIndex.value = idx
        }
      }
    }
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
  const wasAutoplay = shouldAutoplayNextPage.value
  const wasRangeActive = isCustomRangeActive.value
  const savedQueue = [...activeMurottalQueue.value]
  const savedIndex = queueIndex.value
  const wasPlaying = isPlaying.value

  if (isContinuousPageTransition) {
    isContinuousPageTransition = false
  } else {
    if (isPlaying.value) {
      stopPlayer()
      loadedTimings.value = null
    }
  }

  if (wasAutoplay) {
    stopPlayer()
    playerAyahIndex.value = 0
    playingPageNumber.value = null
    playingAyahsList.value = []
    shouldAutoplayNextPage.value = true
  }
  if (wasRangeActive) {
    isCustomRangeActive.value = true
    activeMurottalQueue.value = savedQueue
    queueIndex.value = savedIndex
  }
  imageLoaded.value = false
  classicImgLoaded.value = false
  nextTick(() => {
    if (klassikImgRef.value && klassikImgRef.value.complete) {
      classicImgLoaded.value = true
    }
  })
  imageError.value = false
  revealedLines.value = Array(lineCount).fill(true)
  directPage.value = pageNumber.value
  loadPageMetadata()
  nextTick(prefetchPages)

  loadQcfPages()
  if (process.client) {
    carouselPages.value.forEach(p => {
      if (p) loadQcfFont(p)
    })
  }

  startIdleTimer()
}


const goToPage = (page: number) => {
  if (page < 1 || page > 604 || page === pageNumber.value) return
  return router.push({ path: '/mushaf/' + page })
}

const settleSwipe = () => {
  swipeAnimating.value = true
  swipeOffset.value = 0
  window.setTimeout(() => { swipeAnimating.value = false }, 240)
}

const handlePointerDown = (event: PointerEvent) => {
  if (event.pointerType === 'touch') return
  if (swipeAnimating.value) return
  clearIdleTimer()
  swipeStartX.value = event.clientX
  swipeStartY.value = event.clientY
  swipeDirection.value = null
  swipeStartTime.value = performance.now()
  swipeOffset.value = 0
  suppressNextLineTap.value = false

  // Set up long press timer (e.g., 400ms)
  isLongPressActive.value = false
  if (longPressTimeout.value) {
    clearTimeout(longPressTimeout.value)
    longPressTimeout.value = null
  }

  // Find mushaf word element at down coordinates
  let target = event.target as HTMLElement
  let wordEl = target?.closest('.mushaf-word') as HTMLElement
  if (!wordEl && event.clientX && event.clientY) {
    const fromPoint = document.elementFromPoint(event.clientX, event.clientY) as HTMLElement
    wordEl = fromPoint?.closest('.mushaf-word') as HTMLElement
    target = fromPoint
  }

  // Trigger long press if we clicked a word
  if (wordEl) {
    const verseKey = wordEl.getAttribute('data-verse')
    if (verseKey) {
      longPressTimeout.value = window.setTimeout(() => {
        isLongPressActive.value = true
        if (typeof navigator !== 'undefined' && navigator.vibrate) {
          const hasActivation = typeof navigator.userActivation !== 'undefined'
            ? navigator.userActivation.hasBeenActive
            : true
          if (hasActivation) {
            try {
              navigator.vibrate(24)
            } catch (e) {}
          }
        }
        openAyahOptions(verseKey)
        longPressTimeout.value = null
      }, 450)
    }
  }
}

const handleTouchStart = (event: TouchEvent) => {
  if (swipeAnimating.value) return
  const touch = event.touches[0]
  if (!touch) return

  clearIdleTimer()
  swipeStartX.value = touch.clientX
  swipeStartY.value = touch.clientY
  swipeDirection.value = null
  swipeStartTime.value = performance.now()
  swipeOffset.value = 0
  suppressNextLineTap.value = false

  // Long press for touch
  isLongPressActive.value = false
  if (longPressTimeout.value) {
    clearTimeout(longPressTimeout.value)
    longPressTimeout.value = null
  }

  const target = event.target as HTMLElement
  let wordEl = target?.closest('.mushaf-word') as HTMLElement
  if (!wordEl && touch.clientX && touch.clientY) {
    const fromPoint = document.elementFromPoint(touch.clientX, touch.clientY) as HTMLElement
    wordEl = fromPoint?.closest('.mushaf-word') as HTMLElement
  }

  if (wordEl) {
    const verseKey = wordEl.getAttribute('data-verse')
    if (verseKey) {
      longPressTimeout.value = window.setTimeout(() => {
        isLongPressActive.value = true
        if (typeof navigator !== 'undefined' && navigator.vibrate) {
          try { navigator.vibrate(24) } catch (e) {}
        }
        openAyahOptions(verseKey)
        longPressTimeout.value = null
      }, 450)
    }
  }
}

const handleTouchMove = (event: TouchEvent) => {
  if (swipeStartX.value === null || swipeStartY.value === null || swipeAnimating.value) return

  const touch = event.touches[0]
  if (!touch) return

  const deltaX = touch.clientX - swipeStartX.value
  const deltaY = touch.clientY - swipeStartY.value

  // Cancel long press if user moves finger more than 8px (to allow swiping/panning)
  if (Math.abs(deltaX) > 8 || Math.abs(deltaY) > 8) {
    if (longPressTimeout.value) {
      clearTimeout(longPressTimeout.value)
      longPressTimeout.value = null
    }
  }

  if (!swipeDirection.value) {
    // Detect swipe direction sooner (4px) to beat iOS Safari's native scroll-lock
    if (Math.abs(deltaX) > 4 || Math.abs(deltaY) > 4) {
      // Much more forgiving angle for thumb swipes (allows diagonal-ish horizontal swipes)
      if (Math.abs(deltaX) > Math.abs(deltaY) * 1.2) {
        swipeDirection.value = 'horizontal'
      } else {
        swipeDirection.value = 'vertical'
      }
    } else {
      return
    }
  }

  if (swipeDirection.value === 'horizontal') {
    if (event.cancelable) event.preventDefault() // Stop iOS Safari from stealing the gesture for edge swiping
    let distance = deltaX
    const pullingPastFirst = pageNumber.value === 1 && distance < 0
    const pullingPastLast = pageNumber.value === 604 && distance > 0
    if (pullingPastFirst || pullingPastLast) {
      // Smooth rubber-banding: resistance increases the further you pull
      const absDist = Math.abs(distance)
      distance = Math.sign(distance) * (absDist * 0.15 + 12 * Math.log2(absDist / 12 + 1) * 0.08)
    }
    swipeOffset.value = distance
    if (Math.abs(distance) > 20) suppressNextLineTap.value = true
  }
}

const handlePointerMove = (event: PointerEvent) => {
  if (event.pointerType === 'touch') return
  if (swipeStartX.value === null || swipeStartY.value === null || swipeAnimating.value) return

  const deltaX = event.clientX - swipeStartX.value
  const deltaY = event.clientY - swipeStartY.value

  // Cancel long press if user moves mouse/finger more than 8px (to allow swiping/panning)
  if (Math.abs(deltaX) > 8 || Math.abs(deltaY) > 8) {
    if (longPressTimeout.value) {
      clearTimeout(longPressTimeout.value)
      longPressTimeout.value = null
    }
  }

  if (!swipeDirection.value) {
    // Detect swipe direction sooner (4px) to beat iOS Safari's native scroll-lock
    if (Math.abs(deltaX) > 4 || Math.abs(deltaY) > 4) {
      // Much more forgiving angle for thumb swipes (allows diagonal-ish horizontal swipes)
      if (Math.abs(deltaX) > Math.abs(deltaY) * 0.6) {
        swipeDirection.value = 'horizontal'
      } else {
        swipeDirection.value = 'vertical'
      }
    } else {
      return
    }
  }

  if (swipeDirection.value === 'horizontal') {
    if (event.cancelable) event.preventDefault() // Stop iOS Safari from stealing the gesture for edge swiping
    let distance = deltaX
    const pullingPastFirst = pageNumber.value === 1 && distance < 0
    const pullingPastLast = pageNumber.value === 604 && distance > 0
    if (pullingPastFirst || pullingPastLast) {
      // Smooth rubber-banding: resistance increases the further you pull
      const absDist = Math.abs(distance)
      distance = Math.sign(distance) * (absDist * 0.15 + 12 * Math.log2(absDist / 12 + 1) * 0.08)
    }
    swipeOffset.value = distance
    if (Math.abs(distance) > 20) suppressNextLineTap.value = true
  }
}

const handlePointerUp = async (event: PointerEvent) => {
  if (event.pointerType === 'touch') return
  if (swipeStartX.value === null || swipeAnimating.value) return
  const distance = event.clientX - swipeStartX.value
  const elapsed = Math.max(1, performance.now() - swipeStartTime.value)
  const velocity = distance / elapsed
  const viewportWidth = viewportRef.value?.clientWidth || window.innerWidth
  const direction = distance > 0 ? 1 : -1
  const targetPage = pageNumber.value + direction
  const canMove = targetPage >= 1 && targetPage <= 604

  // Cancel any pending long press
  if (longPressTimeout.value) {
    clearTimeout(longPressTimeout.value)
    longPressTimeout.value = null
  }

  const shouldMove = swipeDirection.value === 'horizontal' && canMove && (Math.abs(distance) > viewportWidth * .22 || (Math.abs(velocity) > .55 && Math.abs(distance) > 30))

  swipeStartX.value = null
  swipeStartY.value = null
  swipeDirection.value = null

  if (!shouldMove) {
    settleSwipe()
    startIdleTimer()

    // If it was a long press, ignore the release/up click (it already opened the drawer)
    if (isLongPressActive.value) {
      isLongPressActive.value = false
      return
    }

    // Otherwise, this is a standard SINGLE-TAP!
    // Single tap on the screen toggles fullscreen!
    toggleFullscreen()
    return
  }

  swipeAnimating.value = true
  swipeOffset.value = direction > 0 ? viewportWidth : -viewportWidth
  if (typeof navigator !== 'undefined' && navigator.vibrate) {
    try { navigator.vibrate(12) } catch (e) {}
  }

  window.setTimeout(async () => {
    await goToPage(targetPage)
    swipeAnimating.value = false
    swipeOffset.value = 0
    startIdleTimer()
  }, 360)
}

const handleTouchEnd = async (event: TouchEvent) => {
  if (swipeStartX.value === null || swipeAnimating.value) return
  const touch = event.changedTouches[0]
  const distance = touch ? touch.clientX - swipeStartX.value : 0
  const elapsed = Math.max(1, performance.now() - swipeStartTime.value)
  const velocity = distance / elapsed
  const viewportWidth = viewportRef.value?.clientWidth || window.innerWidth
  const direction = distance > 0 ? 1 : -1
  const targetPage = pageNumber.value + direction
  const canMove = targetPage >= 1 && targetPage <= 604

  // Cancel any pending long press
  if (longPressTimeout.value) {
    clearTimeout(longPressTimeout.value)
    longPressTimeout.value = null
  }

  const shouldMove = swipeDirection.value === 'horizontal' && canMove && (Math.abs(distance) > viewportWidth * .22 || (Math.abs(velocity) > .55 && Math.abs(distance) > 30))

  swipeStartX.value = null
  swipeStartY.value = null
  swipeDirection.value = null

  if (!shouldMove) {
    settleSwipe()
    startIdleTimer()

    // If it was a long press, ignore the release/up click (it already opened the drawer)
    if (isLongPressActive.value) {
      isLongPressActive.value = false
      return
    }

    // Otherwise, this is a standard SINGLE-TAP!
    // Prevent accidental toggles on dragging cancel by checking distance threshold
    if (Math.abs(distance) < 5) {
      toggleFullscreen()
    }
    return
  }

  swipeAnimating.value = true
  swipeOffset.value = direction > 0 ? viewportWidth : -viewportWidth
  if (typeof navigator !== 'undefined' && navigator.vibrate) {
    try { navigator.vibrate(12) } catch (e) {}
  }

  window.setTimeout(async () => {
    await goToPage(targetPage)
    swipeAnimating.value = false
    swipeOffset.value = 0
    startIdleTimer()
  }, 360)
}

const cancelSwipe = () => {
  if (longPressTimeout.value) {
    clearTimeout(longPressTimeout.value)
    longPressTimeout.value = null
  }
  isLongPressActive.value = false
  if (swipeStartX.value === null) return
  swipeStartX.value = null
  swipeStartY.value = null
  swipeDirection.value = null
  settleSwipe()
  startIdleTimer()
}

const openModeDrawer = () => openMurojaahDrawer('learning', 'mushaf')
const openQariPicker = () => { showQariPicker.value = true }
const closeQariPicker = () => { showQariPicker.value = false }
const selectQari = (qariId: string) => {
  const wasPlaying = isPlaying.value
  selectedQari.value = qariId
  loadedTimings.value = null
  if (wasPlaying) {
    stopPlayer()
    playPlayerAyah()
  } else {
    stopPlayer()
  }
  closeQariPicker()
}
const openAyahMode = (mode: 'learning' | 'listening') => {
  const ayah = pageData.value?.ayahs[0]
  if (!ayah) return
  router.push({ path: `/remote/${ayah.surah_id}/${ayah.ayah_number}`, query: { mode } })
}

const openSectionPicker = () => {
  showSectionPicker.value = true
}

const scrollActivePickerItem = (container: HTMLElement | null, selector: string) => {
  if (!container) return
  const active = container.querySelector<HTMLElement>(selector)
  active?.scrollIntoView({ block: 'center', behavior: 'auto' })
}

const handleSurahPickerEnter = async () => {
  await nextTick()
  scrollActivePickerItem(surahPickerListRef.value, `[data-surah-id="${selectedSurahId.value}"]`)
}

const handleAyahPickerEnter = async () => {
  await nextTick()
  scrollActivePickerItem(ayahPickerGridRef.value, `[data-ayah-number="${selectedAyah.value}"]`)
}

const handleSectionPickerEnter = () => {
  scrollActivePickerItem(sectionGridRef.value, `[data-section-value="${selectedSection.value}"]`)
}

watch([showSurahPicker, selectedSurahId, () => filteredSurahOptions.value.length], async () => {
  if (!showSurahPicker.value) return
  await nextTick()
  requestAnimationFrame(() => handleSurahPickerEnter())
})

watch([showAyahPicker, selectedAyah, selectedSurahTotalAyah], async () => {
  if (!showAyahPicker.value) return
  await nextTick()
  requestAnimationFrame(() => handleAyahPickerEnter())
})

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

const clearIdleTimer = () => {
  if (idleReturnTimer) {
    window.clearTimeout(idleReturnTimer)
    idleReturnTimer = null
  }
}

const startIdleTimer = () => {
  clearIdleTimer()
  if (isPlaying.value && playingPageNumber.value && playingPageNumber.value !== pageNumber.value) {
    idleReturnTimer = window.setTimeout(() => {
      if (isPlaying.value && playingPageNumber.value && playingPageNumber.value !== pageNumber.value) {
        slideToPage(playingPageNumber.value)
      }
    }, 5000)
  }
}

const stopPlayer = () => {
  clearIdleTimer()
  if (playerAudio) {
    playerAudio.onplay = null
    playerAudio.onpause = null
    playerAudio.onended = null
    playerAudio.onerror = null
    playerAudio.ontimeupdate = null
    playerAudio.onloadedmetadata = null
    playerAudio.pause()
    playerAudio.currentTime = 0
    // On iOS Safari, we MUST keep the audio element alive and "blessed".
    // Destroying it (removeAttribute('src') + load() + null) breaks the
    // user gesture blessing, causing silent playback on subsequent plays.
    // Instead, just pause and keep the reference intact.
  }
  isPlaying.value = false
  playerCurrentTime.value = 0
  playerDuration.value = 0
}


let bismillahPendingSurah = 0

const isBismillahSurah = (surah: number) => surah !== 1 && surah !== 9

const playPlayerAyah = () => {
  if (playingAyahsList.value.length === 0 && pageData.value?.ayahs) {
    playingPageNumber.value = pageNumber.value
    playingAyahsList.value = [...pageData.value.ayahs]
  }

  let verse: { surah: number; ayah: number } | null = null
  if (isCustomRangeActive.value) {
    verse = activeMurottalQueue.value[queueIndex.value]
  } else {
    const ayah = playerAyah.value
    if (ayah) {
      const [surah, number] = ayah.verse_key.split(':').map(Number)
      verse = { surah, ayah: number }
    }
  }

  if (!verse) return

  const currentSurahNum = verse.surah
  const qariId = QARI_MAP[selectedQari.value]

  if (qariId) {
    const timingsCacheKey = `timings_${qariId}_${currentSurahNum}`
    if (!loadedTimings.value || loadedTimings.value.key !== timingsCacheKey) {
      timingsLoading.value = true
      fetchTimings(selectedQari.value, currentSurahNum).then((data) => {
        timingsLoading.value = false
        if (data && data.verse_timings && data.verse_timings.length > 0) {
          loadedTimings.value = {
            key: timingsCacheKey,
            audio_url: data.audio_url,
            verse_timings: data.verse_timings
          }
          playPlayerAyah()
        } else {
          loadedTimings.value = { key: timingsCacheKey, fallback: true }
          playPlayerAyah()
        }
      }).catch((e) => {
        console.error("Timings load error:", e)
        timingsLoading.value = false
        loadedTimings.value = { key: timingsCacheKey, fallback: true }
        playPlayerAyah()
      })
      return
    }
  }

  // --- GAPLESS AUDIO CONTINUOUS PLAYBACK ENGINE ---
  if (loadedTimings.value && !loadedTimings.value.fallback) {
    const timings = loadedTimings.value.verse_timings
    const targetKey = `${verse.surah}:${verse.ayah}`
    const activeTiming = timings.find((t: any) => t.verse_key === targetKey)

    if (activeTiming) {
      bismillahPendingSurah = 0 // Bismillah is built into full chapter MP3
      const src = loadedTimings.value.audio_url

      if (!playerAudio) {
        playerAudio = new Audio()
        playerAudio.preload = 'auto'
      }

      const isSameSrc = playerAudio.src === src

      playerAudio.onplay = () => { isPlaying.value = true }
      playerAudio.onpause = () => { isPlaying.value = false }
      playerAudio.onerror = (e) => {
        console.error('Audio playback error:', e)
        isPlaying.value = false
        showToast?.('Gagal memutar murottal. Periksa koneksi internet.', 'forgot')
      }

      playerAudio.onended = () => {
        if (isCustomRangeActive.value) {
          stopPlayer()
          isCustomRangeActive.value = false
        } else {
          const nextTargetPage = (playingPageNumber.value || pageNumber.value) + 1
          if (nextTargetPage <= 604) {
            shouldAutoplayNextPage.value = true
            if (nextTargetPage === pageNumber.value) {
              loadPageMetadata()
            } else {
              slideToPage(nextTargetPage)
            }
          } else {
            isPlaying.value = false
            playerCurrentTime.value = playerDuration.value
          }
        }
      }

      playerAudio.ontimeupdate = () => {
        if (playerAudio?.seeking) return
        const timeMs = (playerAudio?.currentTime || 0) * 1000
        playerCurrentTime.value = playerAudio?.currentTime || 0

        const activeT = timings.find((t: any) => timeMs >= t.timestamp_from && timeMs < t.timestamp_to)
        if (activeT) {
          const idx = playingAyahsList.value.findIndex(x => x.verse_key === activeT.verse_key)
          if (idx !== -1) {
            if (playerAyahIndex.value !== idx) {
              playerAyahIndex.value = idx
              
              // Check if page needs to slide
              const activeAyahData = playingAyahsList.value[idx]
              if (activeAyahData && activeAyahData.page && activeAyahData.page !== pageNumber.value) {
                slideToPage(activeAyahData.page)
              }
            }
          } else {
            // Verse is not on the current page! We need to slide to the page containing this verse.
            const [sNum, aNum] = activeT.verse_key.split(':').map(Number)
            if (lastSyncedVerseKey !== activeT.verse_key) {
              lastSyncedVerseKey = activeT.verse_key
              syncPageForVerse(sNum, aNum, { isContinuous: true })
            }
          }

          // Ayah Repeat / Custom Range handling
          if (isCustomRangeActive.value) {
            const lastVerse = activeMurottalQueue.value[activeMurottalQueue.value.length - 1]
            const lastTiming = timings.find((t: any) => t.verse_key === lastVerse.verse_key)
            
            if (lastTiming && timeMs >= lastTiming.timestamp_to - 100) {
              if (currentRangeRepeatCount.value < settingsRangeRepeat.value) {
                currentRangeRepeatCount.value++
                queueIndex.value = 0
                currentAyahRepeatCount.value = 1
                const firstVerse = activeMurottalQueue.value[0]
                const firstTiming = timings.find((t: any) => t.verse_key === firstVerse.verse_key)
                if (firstTiming && playerAudio) {
                  playerAudio.currentTime = firstTiming.timestamp_from / 1000
                }
              } else {
                stopPlayer()
                isCustomRangeActive.value = false
              }
              return
            }

            if (settingsAyahRepeat.value > 1 && timeMs >= activeT.timestamp_to - 100) {
              if (currentAyahRepeatCount.value < settingsAyahRepeat.value) {
                currentAyahRepeatCount.value++
                playerAudio.currentTime = activeT.timestamp_from / 1000
              } else {
                currentAyahRepeatCount.value = 1
              }
            }
          } else {
            if (localRepeatCount.value > 1 && timeMs >= activeT.timestamp_to - 100) {
              if (currentLocalAyahRepeatCount.value < localRepeatCount.value) {
                currentLocalAyahRepeatCount.value++
                playerAudio.currentTime = activeT.timestamp_from / 1000
              } else {
                currentLocalAyahRepeatCount.value = 1
              }
            }
          }
        }
      }

      const playWithSeek = () => {
        if (!playerAudio) return
        const isStartOfSurah = Number(verse.ayah) === 1
        const seekTime = (isStartOfSurah && isBismillahSurah(verse.surah)) ? 0 : (activeTiming.timestamp_from / 1000)

        const diff = Math.abs(playerAudio.currentTime - seekTime)
        if (diff > 1.2) {
          playerAudio.currentTime = seekTime
        }

        playerAudio.play().catch((err: Error) => {
          isPlaying.value = false
          if (err.name === 'NotAllowedError') {
            showToast?.('Ketuk layar terlebih dahulu, lalu tekan play kembali', 'doubtful')
          } else if (err.name === 'NotSupportedError') {
            showToast?.('Format audio tidak didukung browser ini', 'forgot')
          } else {
            showToast?.('Gagal memutar murottal. Periksa koneksi internet.', 'forgot')
          }
        })
      }

      if (!isSameSrc) {
        playerAudio.src = src
        playerAudio.load()
        playerAudio.onloadedmetadata = () => {
          playerDuration.value = Number.isFinite(playerAudio?.duration) ? (playerAudio?.duration || 0) : 0
          playWithSeek()
        }
      } else {
        playerAudio.onloadedmetadata = () => {
          playerDuration.value = Number.isFinite(playerAudio?.duration) ? (playerAudio?.duration || 0) : 0
        }
        playWithSeek()
      }
      playerAudio.preload = 'auto'
      return
    }
  }

  // --- ORIGINAL FALLBACK PLAYBACK ENGINE (VERSE-BY-VERSE) ---
  if (bismillahPendingSurah > 0) {
    const surah = bismillahPendingSurah
    bismillahPendingSurah = 0
    const src = `https://everyayah.com/data/${selectedQari.value}/${String(surah).padStart(3, '0')}000.mp3`
    if (!playerAudio) {
      playerAudio = new Audio()
      playerAudio.preload = 'auto'
    }
    if (playerAudio.src !== src) {
      playerAudio.src = src
    }
    playerAudio.load()
    playerAudio.preload = 'auto'
    playerAudio.ontimeupdate = () => { playerCurrentTime.value = playerAudio?.currentTime || 0 }
    playerAudio.onloadedmetadata = () => { playerDuration.value = Number.isFinite(playerAudio?.duration) ? (playerAudio?.duration || 0) : 0 }
    playerAudio.onplay = () => { isPlaying.value = true }
    playerAudio.onpause = () => { isPlaying.value = false }
    playerAudio.onerror = (e) => {
      console.error('Bismillah audio error:', e)
      playPlayerAyah()
    }
    playerAudio.onended = () => {
      playPlayerAyah()
    }
    playerAudio.play().catch((err: Error) => {
      isPlaying.value = false
      console.error('Bismillah play error:', err)
      playPlayerAyah()
    })
    return
  }

  const src = playerAudioUrl()
  if (!src) return
  if (!playerAudio) {
    playerAudio = new Audio()
    playerAudio.preload = 'auto'
  }
  if (playerAudio.src !== src) {
    playerAudio.src = src
  }
  playerAudio.load()
  playerAudio.preload = 'auto'
  if (preloadedAudioUrl === src) {
    preloadedAudio = null
    preloadedAudioUrl = ''
  }
  playerAudio.ontimeupdate = () => { playerCurrentTime.value = playerAudio?.currentTime || 0 }
  playerAudio.onloadedmetadata = () => { playerDuration.value = Number.isFinite(playerAudio?.duration) ? (playerAudio?.duration || 0) : 0 }
  playerAudio.onplay = () => { isPlaying.value = true }
  playerAudio.onpause = () => { isPlaying.value = false }
  playerAudio.onerror = (e) => {
    console.error('Audio error:', e)
    isPlaying.value = false
  }
  playerAudio.onended = () => {
    if (isCustomRangeActive.value) {
      if (currentAyahRepeatCount.value < settingsAyahRepeat.value) {
        currentAyahRepeatCount.value += 1
        playPlayerAyah()
      } else {
        currentAyahRepeatCount.value = 1
        if (queueIndex.value < activeMurottalQueue.value.length - 1) {
          queueIndex.value += 1
          const nextVerse = activeMurottalQueue.value[queueIndex.value]
          syncPageForVerse(nextVerse.surah, nextVerse.ayah).then((pageChanged) => {
            if (!pageChanged) playPlayerAyah()
          })
        } else {
          if (currentRangeRepeatCount.value < settingsRangeRepeat.value) {
            currentRangeRepeatCount.value += 1
            queueIndex.value = 0
            const firstVerse = activeMurottalQueue.value[0]
            syncPageForVerse(firstVerse.surah, firstVerse.ayah).then((pageChanged) => {
              if (!pageChanged) playPlayerAyah()
            })
          } else {
            stopPlayer()
            isCustomRangeActive.value = false
          }
        }
      }
    } else {
      if (currentLocalAyahRepeatCount.value < localRepeatCount.value) {
        currentLocalAyahRepeatCount.value += 1
        playPlayerAyah()
      } else {
        currentLocalAyahRepeatCount.value = 1
        if (playerAyahIndex.value < playerAyahs.value.length - 1) {
          playerAyahIndex.value += 1
          playPlayerAyah()
        } else {
          const nextTargetPage = (playingPageNumber.value || pageNumber.value) + 1
          if (nextTargetPage <= 604) {
            shouldAutoplayNextPage.value = true
            if (nextTargetPage === pageNumber.value) {
              loadPageMetadata()
            } else {
              slideToPage(nextTargetPage)
            }
          } else {
            isPlaying.value = false
            playerCurrentTime.value = playerDuration.value
          }
        }
      }
    }
  }
  preloadFollowingAyah()
  playerAudio.play().catch((err: Error) => {
    isPlaying.value = false
    if (err.name === 'NotAllowedError') {
      showToast?.('Ketuk layar terlebih dahulu, lalu tekan play kembali', 'doubtful')
    } else if (err.name === 'NotSupportedError') {
      showToast?.('Format audio tidak didukung browser ini', 'forgot')
    } else {
      showToast?.('Gagal memutar murottal. Periksa koneksi internet.', 'forgot')
    }
  })
}

const togglePlayer = () => {
  if (isPlaying.value && playerAudio) {
    playerAudio.pause()
    return
  }
  if (playerAudio?.src && playerAudio.currentTime > 0 && playerAudio.currentTime < playerAudio.duration) {
    playerAudio.play().catch((err: Error) => {
      isPlaying.value = false
      if (err.name === 'NotAllowedError') {
        showToast?.('Ketuk layar terlebih dahulu, lalu tekan play kembali', 'doubtful')
      } else {
        showToast?.('Gagal memutar murottal. Periksa koneksi internet.', 'forgot')
      }
    })
    return
  }

  if (!pageData.value?.ayahs) {
    shouldAutoplayNextPage.value = true
    return
  }

  if (!isCustomRangeActive.value) {
    playingPageNumber.value = pageNumber.value
    playingAyahsList.value = [...pageData.value.ayahs]
    
    // Play from the selected verse if it exists on the page
    const selSurah = selectedSurahId.value
    const selAyah = selectedAyah.value
    const idx = pageData.value.ayahs.findIndex(x => x.verse_key === `${selSurah}:${selAyah}`)
    if (idx !== -1) {
      playerAyahIndex.value = idx
    } else {
      playerAyahIndex.value = 0
    }
  }
  playPlayerAyah()
}

const resetPlayer = () => {
  stopPlayer()
  playerAyahIndex.value = 0
  shouldAutoplayNextPage.value = false
  isCustomRangeActive.value = false
  currentLocalAyahRepeatCount.value = 1
  playingPageNumber.value = null
  playingAyahsList.value = []
}

const audioUrlForVerse = (verse: { surah: number; ayah: number }) =>
  `https://everyayah.com/data/${selectedQari.value}/${String(verse.surah).padStart(3, '0')}${String(verse.ayah).padStart(3, '0')}.mp3`

const preloadFollowingAyah = () => {
  let nextVerse: { surah: number; ayah: number } | null = null
  if (isCustomRangeActive.value) {
    nextVerse = activeMurottalQueue.value[queueIndex.value + 1] || null
  } else {
    const nextAyah = playerAyahs.value[playerAyahIndex.value + 1]
    if (nextAyah) {
      const [surah, ayah] = nextAyah.verse_key.split(':').map(Number)
      nextVerse = { surah, ayah }
    }
  }
  if (!nextVerse) return
  const url = audioUrlForVerse(nextVerse)
  if (url === preloadedAudioUrl) return
  preloadedAudio = new Audio(url)
  preloadedAudio.preload = 'auto'
  preloadedAudioUrl = url
  preloadedAudio.load()
}

const playerAudioUrl = () => {
  let verse: { surah: number; ayah: number } | null = null
  if (isCustomRangeActive.value) {
    verse = activeMurottalQueue.value[queueIndex.value]
  } else {
    const ayah = playerAyah.value
    if (!ayah) return ''
    const [surah, number] = ayah.verse_key.split(':').map(Number)
    verse = { surah, ayah: number }
  }
  if (!verse) return ''
  return audioUrlForVerse(verse)
}

const startSurahTotalAyah = computed(() => {
  const surah = surahOptions.value.find(s => s.number === settingsStartSurah.value)
  return surah ? surah.total_ayah : 7
})

const endSurahTotalAyah = computed(() => {
  const surah = surahOptions.value.find(s => s.number === settingsEndSurah.value)
  return surah ? surah.total_ayah : 7
})

const openAudioSettings = async () => {
  await loadSurahOptions()
  activePickerType.value = 'none'
  showAudioSettings.value = true
}

const closeAudioSettings = () => {
  showAudioSettings.value = false
}

const onStartSurahChange = () => {
  settingsStartAyah.value = 1
  settingsEndSurah.value = settingsStartSurah.value
  settingsEndAyah.value = endSurahTotalAyah.value
}

const onEndSurahChange = () => {
  settingsEndAyah.value = endSurahTotalAyah.value
  if (settingsEndSurah.value < settingsStartSurah.value) {
    settingsStartSurah.value = settingsEndSurah.value
    settingsStartAyah.value = 1
  }
}

const generateVerseQueue = (startSurah: number, startAyah: number, endSurah: number, endAyah: number) => {
  const queue: { surah: number; ayah: number; verse_key: string }[] = []
  if (startSurah > endSurah || (startSurah === endSurah && startAyah > endAyah)) {
    return queue
  }
  for (let s = startSurah; s <= endSurah; s++) {
    const surahMeta = surahOptions.value.find(x => x.number === s)
    if (!surahMeta) continue
    const total = surahMeta.total_ayah
    const sAyah = (s === startSurah) ? startAyah : 1
    const eAyah = (s === endSurah) ? endAyah : total
    for (let a = sAyah; a <= eAyah; a++) {
      queue.push({ surah: s, ayah: a, verse_key: `${s}:${a}` })
    }
  }
  return queue
}

const slideToPage = async (targetPage: number) => {
  isContinuousPageTransition = true
  const distance = targetPage - pageNumber.value
  if (Math.abs(distance) !== 1) {
    await goToPage(targetPage)
    return
  }

  const viewportWidth = viewportRef.value?.clientWidth || window.innerWidth
  swipeAnimating.value = true
  swipeOffset.value = distance > 0 ? viewportWidth : -viewportWidth
  await new Promise(resolve => window.setTimeout(resolve, 360))
  await goToPage(targetPage)
  swipeAnimating.value = false
  swipeOffset.value = 0
}

const syncPageForVerse = async (surah: number, ayah: number, options: { isContinuous?: boolean } = {}): Promise<boolean> => {
  const isOnCurrentPage = pageData.value?.ayahs.some(x => x.verse_key === `${surah}:${ayah}`)
  if (isOnCurrentPage) return false
  try {
    const res = await apiFetch<{ data: { page: number } }>(`/surahs/${surah}/ayahs/${ayah}`)
    if (res.data && res.data.page !== pageNumber.value) {
      if (!options.isContinuous) {
        shouldAutoplayNextPage.value = true
      }
      await slideToPage(res.data.page)
      return true
    }
  } catch (e) {
    console.error('Failed to sync page for verse:', e)
  }
  return false
}

const toggleFullscreen = () => {
  if (suppressNextLineTap.value) {
    suppressNextLineTap.value = false
    return
  }
  isFullscreenMode.value = !isFullscreenMode.value
}

const startCustomRangePlayback = async () => {
  if (settingsStartSurah.value > settingsEndSurah.value) return
  if (settingsStartSurah.value === settingsEndSurah.value && settingsStartAyah.value > settingsEndAyah.value) {
    settingsEndAyah.value = settingsStartAyah.value
  }
  const queue = generateVerseQueue(
    settingsStartSurah.value,
    settingsStartAyah.value,
    settingsEndSurah.value,
    settingsEndAyah.value
  )
  if (queue.length === 0) return
  activeMurottalQueue.value = queue
  queueIndex.value = 0
  currentAyahRepeatCount.value = 1
  currentRangeRepeatCount.value = 1
  isCustomRangeActive.value = true
  closeAudioSettings()
  stopPlayer()
  const pageChanged = await syncPageForVerse(queue[0].surah, queue[0].ayah)
  if (!pageChanged) playPlayerAyah()
}

const toggleRepeatMode = () => {
  if (isCustomRangeActive.value) {
    isCustomRangeActive.value = false
    resetPlayer()
    return
  }

  const cycle = [1, 2, 3, 4, 5, 10, 20, 99999]
  const currentIndex = cycle.indexOf(localRepeatCount.value)
  const nextIndex = (currentIndex + 1) % cycle.length
  localRepeatCount.value = cycle[nextIndex]
  currentLocalAyahRepeatCount.value = 1
}

const startSurahName = computed(() => {
  const s = surahOptions.value.find(x => x.number === settingsStartSurah.value)
  return s ? `${s.number}. ${s.name_latin}` : 'Pilih Surat'
})

const endSurahName = computed(() => {
  const s = surahOptions.value.find(x => x.number === settingsEndSurah.value)
  return s ? `${s.number}. ${s.name_latin}` : 'Pilih Surat'
})

const ayahRepeatLabel = computed(() => `${settingsAyahRepeat.value}x`)
const rangeRepeatLabel = computed(() => settingsRangeRepeat.value === 99999 ? '\u221E' : settingsRangeRepeat.value + 'x')

const pickerTitle = computed(() => {
  switch (activePickerType.value) {
    case 'startSurah': return 'Dari Surat'
    case 'startAyah': return 'Dari Ayat'
    case 'endSurah': return 'Hingga Surat'
    case 'endAyah': return 'Hingga Ayat'
    case 'ayahRepeat': return 'Pengulangan Ayat'
    case 'rangeRepeat': return 'Pengulangan Keseluruhan'
    default: return ''
  }
})

const filteredSettingsSurahs = computed(() => {
  const query = settingsSurahSearch.value.trim().toLowerCase()
  if (!query) return surahOptions.value
  return surahOptions.value.filter(s =>
    s.name_latin.toLowerCase().includes(query) ||
    s.name_arabic.includes(query) ||
    String(s.number) === query
  )
})

const repeatOptions = computed(() => {
  if (activePickerType.value === 'ayahRepeat') {
    return [
      { value: 1, label: '1x (Sekali putar)' },
      { value: 2, label: '2x' },
      { value: 3, label: '3x' },
      { value: 5, label: '5x' },
      { value: 10, label: '10x' },
      { value: 20, label: '20x' },
    ]
  }
  if (activePickerType.value === 'rangeRepeat') {
    return [
      { value: 1, label: '1x (Sekali putar)' },
      { value: 2, label: '2x' },
      { value: 3, label: '3x' },
      { value: 5, label: '5x' },
      { value: 10, label: '10x' },
      { value: 20, label: '20x' },
      { value: 99999, label: '\u221E (Tanpa Batas)' }
    ]
  }
  return []
})

const isSurahDisabled = (num: number) => {
  if (activePickerType.value === 'endSurah') {
    return num < settingsStartSurah.value
  }
  return false
}

const isAyahDisabled = (num: number) => {
  if (activePickerType.value === 'endAyah') {
    return settingsStartSurah.value === settingsEndSurah.value && num < settingsStartAyah.value
  }
  return false
}

const selectSurahOption = (num: number) => {
  if (activePickerType.value === 'startSurah') {
    settingsStartSurah.value = num
    onStartSurahChange()
  } else if (activePickerType.value === 'endSurah') {
    settingsEndSurah.value = num
    onEndSurahChange()
  }
  activePickerType.value = 'none'
  settingsSurahSearch.value = ''
}

const selectAyahOption = (num: number) => {
  if (activePickerType.value === 'startAyah') {
    settingsStartAyah.value = num
  } else if (activePickerType.value === 'endAyah') {
    settingsEndAyah.value = num
  }
  activePickerType.value = 'none'
}

const selectRepeatOption = (val: number) => {
  if (activePickerType.value === 'ayahRepeat') {
    settingsAyahRepeat.value = val
  } else if (activePickerType.value === 'rangeRepeat') {
    settingsRangeRepeat.value = val
  }
  activePickerType.value = 'none'
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
    const queryAyah = Number(route.query.ayah)
    const pageAyahs = pageData.value?.ayahs || []
    const queryAyahOnPage = Number.isFinite(queryAyah)
      ? pageAyahs.find(ayah => ayah.surah_id === primarySurah.value?.id && ayah.ayah_number === queryAyah)
      : null
    const selectedAyahOnPage = pageAyahs.find(
      ayah => ayah.surah_id === primarySurah.value?.id && ayah.ayah_number === selectedAyah.value
    )
    const firstAyahInSurahOnPage = pageAyahs.find(ayah => ayah.surah_id === primarySurah.value?.id)
    selectedAyah.value = queryAyahOnPage?.ayah_number || selectedAyahOnPage?.ayah_number || firstAyahInSurahOnPage?.ayah_number || 1
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

    const surahMeta = surahOptions.value.find(s => s.id === selectedSurahId.value)
    const surahNum = surahMeta ? surahMeta.number : 1

    router.push({
      path: '/mushaf/' + response.data.page,
      query: { surah: surahNum, ayah: selectedAyah.value }
    })
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

watch(activePickerType, async (newVal) => {
  if (newVal === 'none') return
  if (newVal === 'startAyah' || newVal === 'endAyah') return
  await nextTick()
  setTimeout(() => {
    const activeEl = document.querySelector('.qari-sheet .surah-picker__item--active, .qari-sheet .picker-option-item--active')
    if (activeEl) {
      activeEl.scrollIntoView({ block: 'center', behavior: 'auto' })
    }
  }, 60)
})

watch(() => route.params.pageNumber, handlePageChange)

onMounted(() => {
  loadLastReadAyah()
  loadBookmarks()
  handlePageChange()
  loadSurahOptions()
  window.addEventListener('resize', fitQcfLines)
  if (viewportRef.value) {
    viewportRef.value.addEventListener('touchstart', handleTouchStart, { passive: false })
    viewportRef.value.addEventListener('touchmove', handleTouchMove, { passive: false })
    viewportRef.value.addEventListener('touchend', handleTouchEnd, { passive: false })
    viewportRef.value.addEventListener('touchcancel', cancelSwipe, { passive: false })
  }

  // Call multiple times as font resources load and UI layout finishes rendering
  setTimeout(fitQcfLines, 80)
  setTimeout(fitQcfLines, 300)
  setTimeout(fitQcfLines, 800)

  if (typeof document !== 'undefined' && document.fonts) {
    document.fonts.ready.then(() => {
      fitQcfLines()
      setTimeout(fitQcfLines, 100)
    })
  }
})

onBeforeUnmount(() => {
  clearIdleTimer()
  stopPlayer()
  window.removeEventListener('resize', fitQcfLines)
  if (qcfFitFrame !== null) cancelAnimationFrame(qcfFitFrame)
  if (viewportRef.value) {
    viewportRef.value.removeEventListener('touchstart', handleTouchStart)
    viewportRef.value.removeEventListener('touchmove', handleTouchMove)
    viewportRef.value.removeEventListener('touchend', handleTouchEnd)
    viewportRef.value.removeEventListener('touchcancel', cancelSwipe)
  }
})

useHead({
  title: computed(() => 'Mushaf Hafalan - Halaman ' + pageNumber.value),
  bodyAttrs: {
    class: computed(() => `theme-body-${mushafTheme.value}`)
  }
})
</script>

<style scoped>

/*
 * Content area is constrained to 100dvh (no root scrolling in klasik mode)
 */
.mushaf-page--klasik .mushaf-content {
  padding-top: calc(62px + var(--safe-top, 0px));
  padding-bottom: calc(62px + var(--safe-bottom, 0px));
  overflow: hidden !important;
  height: 100dvh;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

/* Viewport: constrained to the safe height between fixed header (62px) and fixed player (62px) */
.mushaf-page--klasik .mushaf-viewport {
  max-width: 100% !important;
  width: 100% !important;
  height: calc(100dvh - 124px - var(--safe-top, 0px) - var(--safe-bottom, 0px)) !important;
  min-height: 0 !important;
  box-shadow: none !important;
  border-radius: 0 !important;
  overflow: hidden !important;
  display: flex;
  flex-direction: column;
}

/* Match viewport background to active theme to avoid white/black bleeding */
.mushaf-page--klasik.mushaf-theme--classic .mushaf-viewport,
.mushaf-page--klasik.mushaf-theme--classic .mushaf-slide {
  background: #ffffff !important;
}
.mushaf-page--klasik.mushaf-theme--nabawiyyah .mushaf-viewport,
.mushaf-page--klasik.mushaf-theme--nabawiyyah .mushaf-slide {
  background: #fbf3db !important;
}
.mushaf-page--klasik.mushaf-theme--dark .mushaf-viewport,
.mushaf-page--klasik.mushaf-theme--dark .mushaf-slide {
  background: #151d1a !important;
}

/* Slide: aligns cropped page box to the top and enables vertical scroll when needed */
.mushaf-page--klasik .mushaf-slide {
  overflow-y: auto !important;
  background: transparent !important;
  height: 100% !important;
  display: flex !important;
  align-items: flex-start !important;
  justify-content: center !important;
  padding-bottom: 80px !important; /* padding to avoid player footer overlay */
}

.mushaf-page-box-klasik {
  width: 100% !important;
  height: auto !important;
  max-width: 600px !important; /* Cap to golden reading width so page fits screen height and remains laser-sharp */
  padding: 0 !important;
  margin: 0 auto !important;
  border: 0 !important;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.04) !important;
  background: transparent !important;
  aspect-ratio: unset !important;
}

/* Container: simple passthrough, transparent */
.mushaf-classic-container {
  width: 100%;
  background: transparent;
  display: block;
}

/*
 * Wrapper: relative positioning anchor for SVG overlay.
 * Styles are applied inline from getKlassikLayout to crop white paper margins.
 */
.mushaf-classic-wrapper {
  margin: 0 auto;
}

/*
 * Image: positioned absolute inside wrapper to crop outer margins.
 * Transition filter for smooth theme switching.
 */
.mushaf-classic-img {
  display: block;
  width: 100%;
  height: auto;
  user-select: none;
  -webkit-user-drag: none;
  border-radius: 0;
  box-shadow: none;
  transition: filter 0.3s ease;
}

/* Dark mode: invert scanned pages */
.mushaf-classic-img--dark {
  filter: invert(0.9) hue-rotate(180deg) brightness(0.85) contrast(1.15);
}

/* Sepia / Nabawiyyah theme */
.mushaf-classic-img--sepia {
  filter: sepia(0.35) brightness(0.97) contrast(1.08);
}

/*
 * SVG overlay: sits exactly on top of the visible cropped wrapper area.
 */
.mushaf-classic-overlay {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  cursor: pointer;
  pointer-events: all;
  z-index: 5;
}

/* Hover highlight (semi-transparent green) */
.klassik-hover-rect {
  fill: rgba(10, 107, 79, 0.10);
  stroke: rgba(10, 107, 79, 0.30);
  stroke-width: 2; /* sleeker, thinner border */
  pointer-events: none;
}

/* Selected highlight (stronger green) */
.klassik-selected-rect {
  fill: rgba(10, 107, 79, 0.20);
  stroke: rgba(10, 107, 79, 0.65);
  stroke-width: 3; /* sleeker, thinner border */
  pointer-events: none;
}

/* Dark theme overrides for highlights */
.mushaf-page--dark .klassik-hover-rect {
  fill: rgba(52, 211, 153, 0.08);
  stroke: rgba(52, 211, 153, 0.25);
}
.mushaf-page--dark .klassik-selected-rect {
  fill: rgba(52, 211, 153, 0.18);
  stroke: rgba(52, 211, 153, 0.55);
}



.mushaf-page {
  height: 100dvh;
  display: flex;
  flex-direction: column;
  overflow: hidden; /* Lock scroll at root level */
}

/* Theme-specific background colors for page wrappers */
.mushaf-page--nabawiyyah {
  background: #fbf3db; /* Warm paper cream background */
}
.mushaf-page--nabawiyyah .mushaf-content,
.mushaf-page--nabawiyyah .mushaf-viewport,
.mushaf-page--nabawiyyah .mushaf-track,
.mushaf-page--nabawiyyah .mushaf-slide {
  background: #fbf3db;
}

.mushaf-page--classic {
  background: #ffffff; /* Pure white background */
}
.mushaf-page--classic .mushaf-content,
.mushaf-page--classic .mushaf-viewport,
.mushaf-page--classic .mushaf-track,
.mushaf-page--classic .mushaf-slide {
  background: #ffffff;
}

.mushaf-page--dark {
  background: #151d1a; /* Deep warm black/green tint */
}
.mushaf-page--dark .mushaf-content,
.mushaf-page--dark .mushaf-viewport,
.mushaf-page--dark .mushaf-track,
.mushaf-page--dark .mushaf-slide {
  background: #151d1a;
}

/* Global body theme backgrounds */
:global(body.theme-body-dark) {
  background-color: #151d1a !important;
}
:global(body.theme-body-nabawiyyah) {
  background-color: #fbf3db !important;
}
:global(body.theme-body-classic) {
  background-color: #ffffff !important;
}

.mushaf-header {
  position: fixed; /* Fixed at the top */
  top: 0;
  left: 0;
  width: 100%;
  z-index: 20;
  min-height: 76px;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: calc(var(--safe-top) + 9px) 14px 9px;
  border-bottom: 1px solid rgba(31, 54, 45, .08);
  background: rgba(255, 255, 253, .97);
  backdrop-filter: blur(18px);
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.mushaf-header--hidden {
  pointer-events: none;
  transform: translateY(-110%);
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

.mushaf-page--fullscreen .mushaf-content {
  padding-top: calc(var(--safe-top) + 12px);
  padding-bottom: calc(var(--safe-bottom) + 12px);
}

.mushaf-header--hidden { transform: translateY(-100%); opacity: 0; }
.mushaf-player--hidden { transform: translateY(100%); opacity: 0; }

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
  color: rgba(255, 255, 255, 0.9);
  background: rgba(255, 255, 255, 0.15);
  transition: background 0.15s ease;
}

.mushaf-header__browse:active {
  background: rgba(255, 255, 255, 0.28);
}

.mushaf-header__translate {
  width: 42px;
  height: 42px;
  display: grid;
  place-items: center;
  flex: 0 0 auto;
  border: 0;
  border-radius: 50%;
  color: rgba(255, 255, 255, 0.9);
  background: rgba(255, 255, 255, 0.15);
  transition: background 0.15s ease;
}

.mushaf-header__translate:active {
  background: rgba(255, 255, 255, 0.28);
}

.mushaf-header__translate svg {
  width: 20px;
  height: 20px;
}

.mushaf-header__browse svg {
  width: 21px;
  height: 21px;
}

.mushaf-content {
  width: 100%;
  margin: 0 auto;
  flex: 1;
  display: flex;
  align-items: stretch;
  justify-content: center;
  overflow-y: auto; /* Isolated scroll on the content container */
  -webkit-overflow-scrolling: touch;
  padding-top: calc(76px + var(--safe-top)); /* Leaves space for fixed header */
  padding-bottom: calc(70px + var(--safe-bottom) + 16px); /* Leaves space for fixed player */
}

.mushaf-viewport {
  position: relative;
  width: 100%;
  max-width: 100%; /* Stretch fully to left and right */
  flex: 1;
  height: 100%;
  min-height: 0;
  overflow: hidden;
  background: #fffefa;
  touch-action: pan-y pinch-zoom;
  user-select: none;
}

/* Ensure horizontal touch moves bubble up to viewport without browser cancellation */
.mushaf-slide,
.mushaf-page-box,
.mushaf-word,
.line-mask {
  touch-action: pan-y pinch-zoom;
}

.mushaf-track {
  position: absolute;
  inset: 0;
  width: 300%;
  height: 100%;
  display: flex;
  will-change: transform;
}

.mushaf-track--animating {
  transition: transform 360ms cubic-bezier(.22, 1, .36, 1);
}

.mushaf-slide {
  width: 33.333333%;
  height: 100%;
  flex: 0 0 33.333333%;
  overflow-y: auto;
  overscroll-behavior: contain;
  -webkit-overflow-scrolling: touch; /* Momentum scrolling for iOS */
  background: #fffefa;
  container-type: size;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding: 0 10px !important;
  /* Hide scrollbars so no grey separator lines appear in the middle of the screen */
  scrollbar-width: none !important;
  -ms-overflow-style: none !important;
}
.mushaf-slide::-webkit-scrollbar {
  display: none !important;
}

.mushaf-viewport--dragging { cursor: grabbing; }

.mushaf-image {
  width: 100%;
  height: 100%;
  display: block;
  object-fit: contain;
  pointer-events: none;
  -webkit-user-drag: none;
  transform-origin: center;
  transition: transform 0.2s ease;
}

.mushaf-image--classic {
  transform: scale(1.1); /* Classic JPG needs zoom to hide wide margins */
}

.mushaf-image--nabawiyyah {
  transform: scale(1.0); /* Nabawiyyah GIF already fits perfectly */
}

/* Golden Rule Centering Page Box */

.mushaf-page-box {
  position: relative;
  height: auto;
  min-height: 100%;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  overflow: visible;
  border-radius: 4px;
  max-width: 860px;
  width: 100%;
  flex: 1;
  container-type: inline-size; /* For responsive typography */
}

.mushaf-theme--nabawiyyah {
  background: #fbf3db !important;
  color: #2c1a04;
}

.mushaf-theme--classic {
  background: #FFFFFF !important;
  color: #000000;
}

.mushaf-meta {
  position: relative;
  z-index: 2;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 20px 4px;
  font-size: 0.6rem;
  font-weight: 700;
  letter-spacing: 0.04em;
}

.mushaf-theme--nabawiyyah .mushaf-meta { color: #8C6219; }
.mushaf-theme--classic .mushaf-meta { color: #224b8a; }

.mushaf-meta__page {
  font-size: 0.65rem;
  font-weight: 900;
}

.mushaf-qcf-content {
  position: relative;
  z-index: 1;
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: space-evenly;
  padding: 0 6px 10px;
  direction: rtl;
}

.mushaf-text-frame {
  flex: 1;
  display: flex;
  flex-direction: column;
  position: relative;
  border-radius: 2px;
  margin: 0 4px;
}

/* Simulate the thick, multi-layered ornate borders of the Madinah Mushaf */
.mushaf-theme--nabawiyyah .mushaf-text-frame {
  border: 4px double #9A6C1D;
  box-shadow:
    inset 0 0 0 3px #FFF8F0,
    inset 0 0 0 4px #C9943A,
    0 0 0 3px #FFF8F0,
    0 0 0 4px #C9943A;
}

.mushaf-theme--classic .mushaf-text-frame {
  border: 4px double #1a3a6b;
  box-shadow:
    inset 0 0 0 3px #FFFFFF,
    inset 0 0 0 4px #1a3a6b,
    0 0 0 3px #FFFFFF,
    0 0 0 4px #1a3a6b;
}

.mushaf-text-frame__inner {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: space-evenly; /* Distribute 15 lines evenly */
  padding: 8px 12px;
}

/* For pages with < 15 lines (e.g. Al-Fatihah) */
.mushaf-qcf-content--short .mushaf-text-frame__inner {
  justify-content: center;
}

/* Each of the 15 baris / lines */
.mushaf-line {
  display: flex;
  flex-direction: row; /* RTL */
  justify-content: center; /* Words are pre-justified by font spacing, just center them */
  align-items: center;
  width: 100%;
  font-size: 6.2cqw; /* Scaled typography. Slightly smaller to ensure no overflow */
  line-height: 1.52;
  margin: 0.8cqw 0;
  white-space: nowrap; /* Prevent wrapping */
}

.mushaf-line--bismillah {
  margin-bottom: 2cqw;
}

.mushaf-bismillah-text {
  font-size: 1.15em;
  letter-spacing: 0;
}

/* Word and its ayah number ornament grouped as one flex item */
.mushaf-word-group {
  display: inline-flex;
  align-items: center;
  gap: 0;
  flex-shrink: 0;
}

.mushaf-word-group .mushaf-word--end {
  margin-right: -0.05em; /* Natural spacing next to word, no overlap */
}

.mushaf-word-group--with-ornament {
  margin-left: -0.05em; /* Natural spacing, no overlap */
}

/* Individual word spans */
.mushaf-word {
  /* Use block so we don't get flex-squished. Just render naturally. */
  display: inline-block;
  cursor: pointer;
  border-radius: 4px;
  padding: 0; /* No artificial padding, let the font handle it */
  margin: 0;
  transition: background 0.15s ease, color 0.15s ease;
  flex-shrink: 0; /* Never squish the pre-justified font! */
  -webkit-touch-callout: none;
  user-select: none;
}

/* Verse number marker (end glyph) */
.mushaf-word--end {
  font-size: 0.88em;
  opacity: 0.9;
}

.mushaf-word--active {
  color: #065F46 !important;
  background: rgba(16, 185, 129, 0.18) !important;
}

.mushaf-word:not(.mushaf-word--end):active {
  background: var(--active-word-bg);
  border-radius: 4px;
}

.mushaf-word--active.mushaf-word--end {
  background: rgba(16, 185, 129, 0.28) !important;
}

/* Progress status colors */
.mushaf-word--fluent   { color: #065F46; }
.mushaf-word--doubtful { color: #92400E; }
.mushaf-word--forgot   { color: #9F1239; }

.mushaf-surah-banner {
  display: flex;
  justify-content: center;
  align-items: center;
  margin: 1.5cqw 0 2cqw;
}

.mushaf-surah-banner__inner {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 1.2cqw 4cqw;
  border-radius: 4px;
  width: 80%;
  position: relative;
}

/* Fancy double border for the banner to mimic ornate SVG */
.mushaf-surah-banner__inner::before {
  content: '';
  position: absolute;
  inset: 2px;
  border-radius: 2px;
  border: 1px dashed;
  opacity: 0.4;
}

.mushaf-theme--nabawiyyah .mushaf-surah-banner__inner {
  background: linear-gradient(135deg, rgba(201,148,58,0.15) 0%, rgba(201,148,58,0.05) 100%);
  border: 2px solid #C9943A;
  box-shadow: 0 2px 4px rgba(201, 148, 58, 0.15);
}
.mushaf-theme--nabawiyyah .mushaf-surah-banner__inner::before { border-color: #C9943A; }

.mushaf-theme--classic .mushaf-surah-banner__inner {
  background: linear-gradient(135deg, rgba(26,58,107,0.1) 0%, rgba(26,58,107,0.02) 100%);
  border: 2px solid #1a3a6b;
  box-shadow: 0 2px 4px rgba(26, 58, 107, 0.1);
}
.mushaf-theme--classic .mushaf-surah-banner__inner::before { border-color: #1a3a6b; }

.mushaf-surah-banner__name {
  font-family: 'UthmanicHafs', serif; /* Use standard font for banner if QCF isn't loading perfectly for this */
  font-size: 4.5cqw;
  font-weight: 700;
}
.mushaf-theme--nabawiyyah .mushaf-surah-banner__name { color: #7B4E0A; }
.mushaf-theme--classic .mushaf-surah-banner__name { color: #1a3a6b; }

.mushaf-surah-banner__sub {
  font-size: 2.2cqw;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  margin-top: 0.5cqw;
  direction: ltr;
}
.mushaf-theme--nabawiyyah .mushaf-surah-banner__sub { color: rgba(122,76,8,0.65); }
.mushaf-theme--classic .mushaf-surah-banner__sub { color: rgba(26, 58, 107, 0.6); }

.mushaf-page-footer {
  display: flex;
  justify-content: center;
  padding: 0 0 6px;
  font-size: 0.55rem;
  font-weight: 800;
  letter-spacing: 0.05em;
}
.mushaf-theme--nabawiyyah .mushaf-page-footer { color: rgba(107,76,17,0.5); }
.mushaf-theme--classic .mushaf-page-footer { color: rgba(26, 58, 107, 0.4); }

.qcf-loading {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 1.8cqw;
}

.qcf-skeleton-line {
  height: 4.5cqw;
  border-radius: 4px;
  background: linear-gradient(90deg, rgba(0,0,0,0.05) 0%, rgba(0,0,0,0.02) 50%, rgba(0,0,0,0.05) 100%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

.qcf-skeleton-line:nth-child(odd) { width: 100%; }
.qcf-skeleton-line:nth-child(even) { width: 92%; margin-left: auto; }

@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}



.translation-bottom-sheet {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 1200;
  background: #fff;
  border-radius: 22px 22px 0 0;
  box-shadow: 0 -4px 32px rgba(0, 0, 0, 0.18);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  transition: height 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
  will-change: height;
  /* Allow tap-through to Quran behind when collapsed */
  pointer-events: all;
}

.translation-sheet-handle {
  flex: 0 0 auto;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 10px 0 6px;
  cursor: grab;
  touch-action: none;
  user-select: none;
}

.translation-sheet-handle:active {
  cursor: grabbing;
}

.translation-sheet-handle__bar {
  width: 40px;
  height: 4px;
  background: #d4dbd8;
  border-radius: 99px;
}

.translation-sheet-header {
  flex: 0 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 4px 16px 10px;
  border-bottom: 1px solid #f0f4f2;
}

.translation-sheet-settings {
  padding: 12px 16px;
  border-bottom: 1px solid #f0f4f2;
  background: #fcfdfe;
  flex: 0 0 auto;
}

.mushaf-theme-selector {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.theme-selector-label {
  font-size: 0.78rem;
  font-weight: 700;
  color: #3b5249;
}

.theme-selector-value {
  display: inline-flex;
  align-items: center;
  min-height: 30px;
  border: 1px solid #dce8e3;
  background: #f3f8f6;
  padding: 5px 16px;
  font-size: 0.72rem;
  font-weight: 700;
  color: #0a6b4f;
  border-radius: 99px;
}

.translation-sheet-header__left {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.translation-sheet-badge {
  font-size: 0.78rem;
  font-weight: 800;
  color: #0a6b4f;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.translation-sheet-subtitle {
  font-size: 0.72rem;
  color: #8a9e97;
  font-weight: 500;
}

.translation-sheet-close {
  width: 34px;
  height: 34px;
  display: grid;
  place-items: center;
  border: none;
  border-radius: 50%;
  background: #f0f4f2;
  color: #56706a;
  flex: 0 0 auto;
}

.translation-sheet-close svg {
  width: 16px;
  height: 16px;
}

/* --- Clean Minimal Surah Header in Translation Sheet --- */
.translation-surah-header {
  margin-top: 24px;
  margin-bottom: 12px;
  text-align: center;
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
}

.translation-surah-header--first {
  margin-top: 8px;
}

.translation-surah-header__title {
  font-size: 1.1rem;
  font-weight: 800;
  color: #064e3b;
  margin: 0;
  letter-spacing: -0.02em;
}

.translation-surah-header__meta {
  font-size: 0.72rem;
  font-weight: 700;
  color: #56706a;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  margin: 0;
}

.translation-surah-header__line {
  width: 48px;
  height: 2px;
  background: #e6ecea;
  border-radius: 99px;
  margin-top: 8px;
}

/* --- Dropdown trigger inside Translation Sheet Header --- */
.translation-sheet-dropdown-trigger {
  display: flex;
  align-items: center;
  gap: 4px;
  cursor: pointer;
  color: #1e2e28;
  font-weight: 800;
  font-size: 1.05rem;
  letter-spacing: -0.01em;
  line-height: 1.2;
}

.dropdown-chevron {
  width: 14px;
  height: 14px;
  color: #0a6b4f;
  transition: transform 0.2s ease;
}
.translation-bottom-sheet--dark .translation-sheet-dropdown-trigger  {
      color: #a8b7b1 !important;
}
.translation-bottom-sheet--dark .dropdown-chevron {
      color: #a8b7b1 !important;
}
/* --- Player inside Translation Sheet (Fixed at Bottom) --- */
.translation-sheet-player {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex: 0 0 auto;
  border-top: 1px solid rgba(31, 54, 45, 0.08);
  background: rgba(255, 255, 253, 0.97);
  padding: 10px 16px;
}

/* --- Settings inside Translation Sheet (Fixed at Bottom, above player) --- */
/* Old Settings Panel removed from bottom of drawer */

.tss-font-size {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
}

.tss-font-icon {
  width: 20px;
  height: 20px;
  color: #0a6b4f;
  flex-shrink: 0;
}

.tss-font-value {
  font-size: 0.8rem;
  font-weight: 700;
  color: #0a6b4f;
  min-width: 38px;
  text-align: right;
  flex-shrink: 0;
}

.tss-slider-wrap {
  position: relative;
  flex: 1;
  display: flex;
  align-items: center;
}

.tss-slider {
  -webkit-appearance: none;
  width: 100%;
  height: 4px;
  background: #e6ecea;
  border-radius: 99px;
  outline: none;
}

.tss-slider::-webkit-slider-thumb {
  -webkit-appearance: none;
  width: 14px;
  height: 14px;
  background: #0a6b4f;
  border-radius: 50%;
  cursor: pointer;
  box-shadow: 0 2px 6px rgba(10, 107, 79, 0.3);
  transition: transform 0.1s ease;
}

.tss-slider::-webkit-slider-thumb:active {
  transform: scale(1.2);
}

.tss-toggle {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

.tss-toggle-label {
  font-size: 0.8rem;
  font-weight: 600;
  color: #1e2e28;
}

.tss-switch {
  position: relative;
  display: inline-block;
  width: 36px;
  height: 20px;
}

.tss-switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.tss-slider-toggle {
  position: absolute;
  cursor: pointer;
  inset: 0;
  background-color: #cbd5e1;
  transition: .3s;
  border-radius: 99px;
}

.tss-slider-toggle:before {
  position: absolute;
  content: "";
  height: 14px;
  width: 14px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  transition: .3s;
  border-radius: 50%;
}

.tss-switch input:checked + .tss-slider-toggle {
  background-color: #0a6b4f;
}

.tss-switch input:checked + .tss-slider-toggle:before {
  transform: translateX(16px);
}

/* --- Translation Sheet Item Customizations --- */
.translation-sheet-item__number-wrap {
  position: relative;
  width: 42px;
  height: 42px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.ayah-frame-svg {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  color: #c6952b; /* Premium Gold border */
}

.translation-sheet-item__number-wrap span {
  position: relative;
  font-size: 0.72rem;
  font-weight: 800;
  color: #1e2e28;
  z-index: 1;
}
.translation-bottom-sheet--dark .translation-sheet-item__number-wrap span {
 color: #a8b7b1 !important;
}

.translation-sheet-list {
  flex: 1;
  min-height: 0; /* Allow the list to shrink and scroll inside the flex container */
  overflow-y: auto;
  overflow-x: hidden; /* Prevent horizontal scrolling & iOS wobble */
  -webkit-overflow-scrolling: touch;
  padding: 0 20px;
  display: flex;
  flex-direction: column;
  background: #fff;
}

.translation-sheet-item {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 18px 20px; /* Pad content back from edges */
  margin-left: -20px; /* Stretch container from absolute left to right */
  margin-right: -20px;
  border-bottom: 1px dashed #e6ecea;
  cursor: pointer;
  text-align: left;
  transition: background 0.15s ease, padding 0.15s ease;
}

.translation-sheet-item:last-child {
  border-bottom: none;
}

.translation-sheet-item__number {
  flex: 0 0 auto;
  width: 28px;
  height: 28px;
  display: grid;
  place-items: center;
  background: #0a6b4f;
  color: #fff;
  border-radius: 50%;
}

.translation-text-loading-skeleton {
  display: inline-block;
  color: #8a9e97;
  font-style: italic;
  animation: pulse-loading-translation 1.5s infinite ease-in-out;
}

@keyframes pulse-loading-translation {
  0% { opacity: 0.5; }
  50% { opacity: 1; }
  100% { opacity: 0.5; }
}

.translation-sheet-item:active {
  background: rgba(0, 0, 0, 0.02);
}

.translation-sheet-item--active {
  background: rgba(10, 107, 79, 0.03);
  border-left: 4px solid #0a6b4f;
  padding-left: 16px; /* Offset the 4px border-left to keep layout aligned */
  padding-right: 20px;
}

.translation-sheet-item__number {
  flex: 0 0 auto;
  width: 28px;
  height: 28px;
  display: grid;
  place-items: center;
  background: #0a6b4f;
  color: #fff;
  border-radius: 50%;
  font-size: 0.72rem;
  font-weight: 800;
}

.translation-sheet-item--active .translation-sheet-item__number {
  background: #0a6b4f;
  box-shadow: 0 0 0 3px rgba(10, 107, 79, 0.2);
}

.translation-sheet-item__text {
  flex: 1;
  font-size: 0.875rem;
  line-height: 1.6;
  color: #1e2e28;
  font-weight: 450;
}

.translation-sheet-item__latin {
  font-size: 0.82rem;
  line-height: 1.5;
  color: #718a80; /* Soft gray-green color to differentiate */
  font-style: italic; /* Italicized style */
  margin-bottom: 6px;
  font-weight: 400;
}

.translation-sheet-item--active .translation-sheet-item__text {
  color: #0a3d2e;
  font-weight: 550;
}

.translation-sheet-item--active .translation-sheet-item__latin {
  color: #4a6156; /* Richer green-gray when active */
}

/* Slide-up animation for sheet */
.translate-sheet-enter-active {
  transition: transform 0.3s cubic-bezier(0.34, 1.2, 0.64, 1), opacity 0.2s ease;
}
.translate-sheet-leave-active {
  transition: transform 0.25s ease-in, opacity 0.2s ease;
}
.translate-sheet-enter-from,
.translate-sheet-leave-to {
  transform: translateY(100%);
  opacity: 0;
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
  pointer-events: none; /* Let scroll wheel and clicks pass through */
}

.line-mask {
  min-height: 0;
  border: 0;
  border-bottom: 1px solid rgba(130, 115, 78, .05);
  background: rgba(251, 249, 242, .99);
  cursor: pointer;
  transition: opacity .16s ease;
  pointer-events: auto; /* Active mask blocks events to reveal on interaction */
}

.line-mask:nth-child(odd) {
  background: rgba(247, 248, 240, .99);
}

.line-mask--revealed {
  opacity: 0;
  pointer-events: none;
}

/* Navigator Sheet (Pilih Bacaan) - Mushaf style */
.navigator-overlay {
  position: fixed;
  inset: 0;
  z-index: 1300;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  background: rgba(4, 39, 25, 0.5);
  backdrop-filter: blur(6px);
}

.navigator-sheet {
  position: relative;
  width: 100%;
  max-width: 500px;
  margin: 0 auto;
  background: #FFFDF7;
  border-radius: 24px 24px 0 0;
  overflow: hidden;
  max-height: 92dvh;
  display: flex;
  flex-direction: column;
  user-select: none;
  -webkit-user-select: none;
}

.navigator-sheet--picker-open {
  height: 96dvh;
  max-height: 96dvh;
}

.navigator-sheet__content {
  padding: 16px 20px calc(24px + env(safe-area-inset-bottom));
  display: flex;
  flex-direction: column;
  gap: 20px;
  flex: 1;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
}

.navigator-sheet__handle {
  flex: 0 0 auto;
  display: flex;
  justify-content: center;
  padding: 12px 0;
  cursor: grab;
  touch-action: none;
  user-select: none;
}

.navigator-sheet__handle::before {
  content: '';
  width: 42px;
  height: 4px;
  border-radius: 999px;
  background: rgba(0,0,0,0.15);
}

.navigator-sheet__handle:active {
  cursor: grabbing;
}

.navigator-sheet__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.navigator-sheet__header > div > span {
  display: block;
  font-size: 0.65rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: #087d59;
  margin-bottom: 4px;
}

.navigator-sheet__header h2 {
  font-size: 1.35rem;
  font-weight: 800;
  color: #042719;
  letter-spacing: -0.02em;
  margin: 0 0 3px;
}

.navigator-sheet__header p {
  font-size: 0.78rem;
  color: #6B7280;
  margin: 0;
}

.navigator-sheet__header > button {
  width: 34px;
  height: 34px;
  flex: 0 0 34px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: #F3F4F6;
  color: #4B5563;
  border: none;
  cursor: pointer;
  transition: background 0.2s;
}

.navigator-sheet__header > button:hover {
  background: #E5E7EB;
}

.navigator-sheet__header > button svg {
  width: 16px;
  height: 16px;
}

.navigator-field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.navigator-field label {
  font-size: 0.72rem;
  font-weight: 700;
  color: #374151;
  letter-spacing: 0.02em;
}

.navigator-field label > span {
  margin-left: 4px;
  color: #9aa29e;
  font-weight: 600;
}

.navigator-field--wide { width: 100%; }

.navigator-field--surah {
  flex: 1;
  min-width: 0;
}

.navigator-field--ayah {
  width: 96px;
  flex-shrink: 0;
}

.navigator-selector {
  width: 100%;
  min-height: 56px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  padding: 10px 14px;
  border-radius: 16px;
  border: 1.5px solid rgba(0,0,0,0.07);
  background: #fff;
  cursor: pointer;
  text-align: left;
  transition: border-color 0.2s, box-shadow 0.2s;
  color: #1F2937;
}

.navigator-selector:hover {
  border-color: rgba(8, 125, 89, 0.35);
  box-shadow: 0 2px 8px rgba(8, 125, 89, 0.06);
}

.navigator-selector > span {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
  overflow: hidden;
}

.navigator-selector strong {
  font-size: 0.88rem;
  font-weight: 700;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.navigator-field--ayah .navigator-selector strong {
  overflow: visible;
  text-overflow: clip;
  font-size: 1rem;
  font-weight: 800;
}

.navigator-selector small {
  font-size: 0.7rem;
  color: #6B7280;
  font-weight: 400;
}

.navigator-selector svg {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
  color: #9CA3AF;
}

.navigator-primary {
  width: 100%;
  height: 52px;
  border-radius: 16px;
  border: none;
  background: linear-gradient(135deg, #087d59, #065c41);
  color: #fff;
  font-size: 0.95rem;
  font-weight: 800;
  letter-spacing: 0.01em;
  cursor: pointer;
  transition: opacity 0.2s, transform 0.15s;
  box-shadow: 0 4px 12px rgba(8, 125, 89, 0.3);
}

.navigator-primary:active {
  transform: scale(0.98);
}

.navigator-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
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
  flex: 1 1 auto;
  min-height: 0;
  display: flex;
  flex-direction: column;
  gap: 7px;
  overflow-y: auto;
  overscroll-behavior: contain;
}

.surah-picker__item {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 10px 10px;
  border-radius: 12px;
  border: none;
  background: transparent;
  cursor: pointer;
  text-align: left;
  transition: background 0.15s;
  color: #1F2937;
}

.surah-picker__item:active,
.surah-picker__item--active {
  background: #E9F6F0;
}

.surah-picker__item--active {
  color: #087d59;
}

.surah-picker__number {
  width: 32px;
  height: 32px;
  flex: 0 0 32px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: #F4F3ED;
  font-size: 0.72rem;
  font-weight: 800;
  color: #6B7280;
}

.surah-picker__item--active .surah-picker__number {
  background: #D1FAE5;
  color: #065F46;
}

.surah-picker__names {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 1px;
}

.surah-picker__names strong {
  overflow: hidden;
  font-size: 0.82rem;
  font-weight: 700;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.surah-picker__names small {
  font-size: 0.68rem;
  color: #6B7280;
}

.surah-picker__arabic {
  font-family: var(--font-arabic);
  color: #087d59;
  font-size: 1.02rem;
  direction: rtl;
}

.surah-picker__item > svg {
  width: 18px;
  height: 18px;
  flex-shrink: 0;
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
.sheet-leave-active .navigator-sheet,
.sheet-enter-active .qari-sheet,
.sheet-leave-active .qari-sheet {
  transition: transform .24s ease;
}

.sheet-enter-from,
.sheet-leave-to {
  opacity: 0;
}

.sheet-enter-from .navigator-sheet,
.sheet-leave-to .navigator-sheet,
.sheet-enter-from .qari-sheet,
.sheet-leave-to .qari-sheet {
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
  width: 100%;
  max-width: 100%;
  padding: 9px 16px calc(9px + var(--safe-bottom));
  border-top: 1px solid rgba(31, 54, 45, .1);
  background: rgba(255, 255, 253, .97);
  box-shadow: 0 -8px 26px rgba(28, 46, 39, .08);
  backdrop-filter: blur(18px);
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.mushaf-player--hidden {
  pointer-events: none;
  transform: translateY(110%);
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

.mushaf-player__actions {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-shrink: 0;
}

.mushaf-player__btn {
  width: 38px;
  height: 38px;
  display: grid;
  place-items: center;
  border: 0;
  border-radius: 50%;
  color: #7b8680;
  background: transparent;
  cursor: pointer;
  transition: all var(--transition-fast);
  position: relative;
}

.mushaf-player__btn:hover,
.mushaf-player__btn:active {
  color: #0c8a69;
  background: rgba(21, 145, 116, 0.08);
}

.mushaf-player__btn--active {
  color: #0c8a69;
  background: rgba(21, 145, 116, 0.06);
}

.mushaf-player__repeat-badge {
  position: absolute;
  top: -2px;
  right: -2px;
  background: #159174;
  color: white;
  font-size: 0.55rem;
  font-weight: 700;
  padding: 1px 4px;
  border-radius: 10px;
  line-height: 1;
}

.audio-settings-body {
  display: flex;
  flex-direction: column;
  gap: 16px;
  padding: 10px 0;
}

.audio-settings-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.audio-settings-col {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.audio-settings-col label,
.audio-settings-field label {
  color: #314039;
  font-size: 0.75rem;
  font-weight: 700;
}

.audio-settings-field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

/* Segmented control for theme switching */
.theme-segmented-control {
  display: flex;
  background: #f1f4f2;
  padding: 3px;
  border-radius: 12px;
  border: 1px solid rgba(0, 0, 0, 0.03);
}

.theme-segment-btn {
  flex: 1;
  border: none;
  background: transparent;
  padding: 8px 12px;
  font-size: 0.8125rem;
  font-weight: 700;
  color: #556c60;
  border-radius: 9px;
  cursor: pointer;
  transition: all 0.15s ease;
}

.theme-segment-btn--active {
  background: #fff;
  color: var(--color-primary-dark);
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
}

.audio-settings-actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin-top: 10px;
}

.settings-action-btn {
  height: 48px;
  border: 1px solid #dce3df;
  border-radius: 12px;
  font-size: 0.85rem;
  font-weight: 750;
  cursor: pointer;
  transition: all var(--transition-fast);
}

.settings-action-btn--cancel {
  background: #fff;
  color: #7b8680;
}

.settings-action-btn--cancel:active {
  background: #f4f6f5;
}

.settings-action-btn--play {
  background: #159174;
  color: white;
  border-color: #159174;
}

.settings-action-btn--play:active {
  background: #0f735b;
  border-color: #0f735b;
}
.mushaf-page--dark .custom-select-trigger {
    background: #15261f !important;
    border-color: rgba(255, 255, 255, 0.12) !important;
    color: #f4f7f5 !important;
}
.custom-select-trigger {
  width: 100%;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border: 1px solid #dce3df;
  border-radius: 13px;
  padding: 0 16px;
  color: #314039;
  background: #fbfcfa;
  font-size: .8rem;
  font-weight: 750;
  cursor: pointer;
  user-select: none;
  transition: all var(--transition-fast);
}

.custom-select-trigger:active {
  background: #f0f2f1;
  border-color: #cdd6d2;
}

.custom-select-trigger svg {
  width: 16px;
  height: 16px;
  color: #159174;
}

.picker-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
  border-bottom: 1px solid #dce3df;
  padding-bottom: 12px;
}

.picker-back {
  width: 36px;
  height: 36px;
  display: grid;
  place-items: center;
  border: 0;
  border-radius: 50%;
  color: #314039;
  background: #f4f6f5;
  cursor: pointer;
}

.picker-back svg {
  width: 20px;
  height: 20px;
}

.picker-search-bar {
  margin-bottom: 12px;
}

.picker-search-input {
  width: 100%;
  height: 40px;
  border: 1px solid #dce3df;
  border-radius: 10px;
  padding: 0 12px;
  font-size: 0.8rem;
  outline: none;
  background: #fbfcfa;
}

.picker-options-list {
  max-height: 50vh;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 4px;
  overscroll-behavior: contain;
}

.picker-option-item {
  width: 100%;
  min-height: 48px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 16px;
  border: 1px solid #f4f6f5;
  border-radius: 10px;
  background: transparent;
  color: #314039;
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.15s;
  text-align: left;
}

.picker-option-item:active {
  background: #f4f6f5;
}

.picker-option-item--active {
  background: #e9f6f0 !important;
  color: #087d59;
  border-color: #ccece2;
}

.picker-option-item--disabled {
  opacity: 0.35;
  cursor: not-allowed;
  background: transparent !important;
  border-color: transparent !important;
}

.picker-option-item__left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.picker-option-item__number {
  min-width: 24px;
  height: 24px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: #f4f6f5;
  color: #7b8680;
  font-size: 0.7rem;
  font-weight: 700;
}

.picker-option-item--active .picker-option-item__number {
  background: #ccece2;
  color: #087d59;
}

.picker-option-item__arabic {
  font-family: 'me_quran', 'Scheherazade', sans-serif;
  font-size: 1.1rem;
}

.ayah-picker-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 8px;
  padding: 10px 0;
}

.ayah-picker-cell {
  height: 42px;
  display: grid;
  place-items: center;
  border: 1px solid #dce3df;
  border-radius: 10px;
  background: #fff;
  color: #314039;
  font-size: 0.85rem;
  font-weight: 700;
  cursor: pointer;
}

.ayah-picker-cell--active {
  background: #159174;
  color: white;
  border-color: #159174;
}

.ayah-picker-cell--disabled {
  opacity: 0.3;
  cursor: not-allowed;
  background: #f4f6f5;
  border-color: #e8eae7;
}

.navigator-field--wide { width: 100%; }
.navigator-field--ayah {
  width: 96px;
  flex-shrink: 0;
}
.navigator-field label span { margin-left: 4px; color: #9aa29e; font-size: .62rem; font-weight: 600; }
.navigator-select-wrap { position: relative; }
.navigator-select-wrap select {
  width: 100%;
  height: 48px;
  appearance: none;
  border: 1.5px solid rgba(0,0,0,0.07);
  border-radius: 16px;
  outline: none;
  padding: 0 42px 0 14px;
  color: #1F2937;
  background: #fff;
  font-size: .88rem;
  font-weight: 700;
}
.navigator-select-wrap select:focus { border-color: rgba(8, 125, 89, 0.35); box-shadow: 0 2px 8px rgba(8, 125, 89, 0.06); }
.navigator-select-wrap svg { position: absolute; top: 50%; right: 14px; width: 16px; height: 16px; color: #9CA3AF; pointer-events: none; transform: translateY(-50%); }

@media (min-width: 621px) {
  /* removed max-width limits to support landscape full width */
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
  gap: 4px;
  background: rgba(251, 191, 36, 0.16); /* Warm amber/gold tint */
  color: #FBBF24 !important;
  border: 1px solid rgba(251, 191, 36, 0.28);
  font-size: .6rem;
  font-weight: 850;
  letter-spacing: .08em;
  text-transform: uppercase;
  padding: 4.5px 10px;
  border-radius: 99px;
  margin-bottom: 5px;
  transition: transform 0.2s, background 0.22s;
  cursor: pointer;
}

.mushaf-header__mode:active {
  transform: scale(0.95);
  background: rgba(251, 191, 36, 0.28);
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
  z-index: 1300;
  display: flex;
  align-items: flex-end;
  justify-content: center;
  background: rgba(13, 29, 23, .42);
  backdrop-filter: blur(5px);
}

.qari-sheet {
  width: 100%;
  max-width: 620px;
  max-height: 90dvh;
  padding: 10px 20px calc(16px + var(--safe-bottom));
  border-radius: 26px 26px 0 0;
  background: #fffdfa;
  box-shadow: 0 -18px 50px rgba(17, 35, 28, .2);
  display: flex;
  flex-direction: column;
  user-select: none;
  -webkit-user-select: none;
}

.qari-sheet__handle {
  width: 100%;
  padding: 4px 0 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: grab;
  touch-action: none;
  user-select: none;
}
.qari-sheet__handle::before {
  content: '';
  display: block;
  width: 42px;
  height: 4px;
  border-radius: 999px;
  background: #d5d9d6;
}
.qari-sheet__handle:active { cursor: grabbing; }

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
}
/* Unified reader chrome */
.mushaf-header {
  border-bottom-color: rgba(255,255,255,.1);
  color: #fff;
  background: linear-gradient(135deg, #0b654f 0%, #07513f 58%, #063f35 100%);
  box-shadow: 0 4px 16px rgba(4,55,43,.2);
}
.mushaf-header__back {
  color: #fff;
  background: rgba(255,255,255,.1);
}
.mushaf-header__back:active { background: rgba(255,255,255,.2); }
.mushaf-header__mode { color: #FBBF24; }
.mushaf-header__title strong { color: #fff; }
.mushaf-header__title small { color: rgba(255,255,255,.72); }
.mushaf-header__title small span { color: rgba(243,217,148,.8); }
.mushaf-header__browse {
  color: #fff;
  background: rgba(255,255,255,.12);
  box-shadow: inset 0 0 0 1px rgba(255,255,255,.08);
}
.mushaf-content { cursor: pointer; }
.mushaf-player {
  gap: 10px;
  padding: 8px 12px calc(8px + var(--safe-bottom));
}
.mushaf-player__actions {
  gap: 2px;
  padding: 3px;
  border: 1px solid #e2ece7;
  border-radius: 999px;
  background: #f1f7f4;
}
.mushaf-player__play,
.mushaf-player__btn {
  width: 40px;
  height: 40px;
  flex: 0 0 40px;
}
.mushaf-player__play {
  border: 0;
  color: #fff;
  background: #12866a;
  box-shadow: 0 4px 10px rgba(18,134,106,.2);
}
.mushaf-player__play svg,
.mushaf-player__btn svg { width: 20px; height: 20px; }
.mushaf-player__btn { color: #527067; }
.mushaf-player__btn:hover,
.mushaf-player__btn:active,
.mushaf-player__btn--active { color: #087d59; background: #e2f0e9; }
.mushaf-player__qari-select { gap: 8px; }
.mushaf-player__qari-select > img { width: 34px; height: 34px; flex-basis: 34px; }
.mushaf-player__qari-select > svg { width: 16px; height: 16px; flex-basis: 16px; }
/* Header optical alignment */
.mushaf-header { gap: 12px; padding-right: 12px; padding-left: 12px; }
.mushaf-header__main { padding-left: 1px; }

/* Landscape orientation optimization for modal pickers and sheets */
@media (max-height: 520px) {
  .navigator-sheet,
  .qari-sheet {
    min-height: 0 !important;
    max-height: 95dvh !important;
  }
  .navigator-sheet__content,
  .audio-settings-body {
    flex: 1 !important;
    max-height: none !important;
    overflow-y: auto !important;
  }
  .surah-picker__list,
  .picker-options-list,
  .qari-list {
    max-height: 50vh !important;
    overflow-y: auto !important;
  }
}

.mushaf-page--nabawiyyah,
.mushaf-page--nabawiyyah .mushaf-content,
.mushaf-page--nabawiyyah .mushaf-viewport,
.mushaf-page--nabawiyyah .mushaf-slide {
  background:
    radial-gradient(circle at 50% 12%, rgba(224, 190, 102, .13), transparent 34%),
    #fbf3db;
}

.mushaf-viewport {
  max-width: 520px;
  border-radius: 3px;
  box-shadow:
    0 18px 48px rgba(25, 56, 50, .12),
    0 2px 8px rgba(25, 56, 50, .08);
}

.mushaf-page-box {
  isolation: isolate;
  padding: 4px 7px 6px;
  border: 1px solid rgba(13, 103, 107, .16);
  border-radius: 3px;
  background:
    radial-gradient(circle at 50% 0, rgba(231, 196, 106, .13), transparent 24%),
    #fbf3db;
}

/* The outer illuminated border sits behind the text frame. */
.mushaf-frame {
  position: absolute;
  z-index: 0;
  inset: 36px 7px 6px;
  pointer-events: none;
}

.mushaf-frame__border {
  position: absolute;
  inset: 0;
  border: 2px solid #0a6c73;
  border-radius: 2px;
  box-shadow:
    0 0 0 2px #f8edbd,
    0 0 0 4px #d7a83d,
    0 0 0 6px #0b7880,
    inset 0 0 0 2px #f8edbd,
    inset 0 0 0 4px rgba(11, 120, 128, .72);
}

.mushaf-frame__border::before,
.mushaf-frame__border::after {
  content: '';
  position: absolute;
  pointer-events: none;
}

.mushaf-frame__border::before {
  inset: 7px;
  border: 1px solid rgba(199, 143, 41, .72);
}

.mushaf-frame__border::after {
  inset: -4px;
  opacity: .76;
  background:
    repeating-linear-gradient(90deg, transparent 0 17px, #d04f3e 17px 20px, transparent 20px 36px) top / 100% 3px no-repeat,
    repeating-linear-gradient(90deg, transparent 0 12px, #dfb23d 12px 16px, transparent 16px 29px) bottom / 100% 3px no-repeat,
    repeating-linear-gradient(0deg, transparent 0 17px, #d04f3e 17px 20px, transparent 20px 36px) left / 3px 100% no-repeat,
    repeating-linear-gradient(0deg, transparent 0 12px, #dfb23d 12px 16px, transparent 16px 29px) right / 3px 100% no-repeat;
}

.mushaf-frame__corner {
  position: absolute;
  z-index: 2;
  width: 18px;
  height: 18px;
  border: 2px solid #e1b644;
  background:
    radial-gradient(circle, #d74f3d 0 18%, #f4d467 19% 34%, #087985 35% 58%, #fff4c9 59% 100%);
  box-shadow: 0 0 0 2px #087985;
  transform: rotate(45deg);
}

.mushaf-frame__corner--tl { top: -8px; left: -8px; }
.mushaf-frame__corner--tr { top: -8px; right: -8px; }
.mushaf-frame__corner--bl { bottom: -8px; left: -8px; }
.mushaf-frame__corner--br { right: -8px; bottom: -8px; }

/* Page metadata follows the three cartouches used in printed Madinah pages. */
.mushaf-meta {
  z-index: 3;
  gap: 8px;
  min-height: 28px;
  padding: 3px 8px 7px;
  color: #143e3e !important;
  letter-spacing: .015em;
}

.mushaf-meta > span {
  min-height: 20px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 1px solid #176a6d;
  border-radius: 999px;
  padding: 0px 10px;
  background: rgba(255, 253, 235, .97);
  /* box-shadow:
    0 0 0 2px #fff8d8,
    0 0 0 3px rgba(23, 106, 109, .58); */
  line-height: 1;
  white-space: nowrap;
  font-size: .6rem;
}

.mushaf-meta__juz,
.mushaf-meta__surah {
  width: 34%;
  overflow: hidden;
  text-overflow: ellipsis;
}

.mushaf-meta__page {
  width: 48px;
  flex: 0 0 48px;
  font-size: .65rem;
}

.mushaf-qcf-content {
  z-index: 1;
  padding: 0 8px 8px;
}

.mushaf-text-frame {
  margin: 0 3px;
  border: 2px solid #087780 !important;
  border-radius: 2px;
  padding: 12px;
  background:
    radial-gradient(circle at 50% 50%, #d94e3d 0 10%, #efc451 11% 22%, #fff6cb 23% 31%, transparent 32%) 0 0 / 24px 24px,
    radial-gradient(ellipse at 50% 0, #f3d776 0 17%, transparent 18%) 12px 12px / 24px 24px,
    conic-gradient(from 45deg, #087984, #e9c254 22%, #f7edc3 23% 31%, #0a6872 32% 50%, #e9c254 51% 72%, #087984 73%) 0 0 / 24px 24px;
  box-shadow:
    0 0 0 2px #f8efbf,
    0 0 0 4px #c7962f,
    0 0 0 6px #087780,
    0 0 0 7px rgba(237, 205, 112, .72),
    inset 0 0 0 2px rgba(255, 248, 209, .96),
    inset 0 0 0 4px rgba(5, 89, 98, .5);
}

.mushaf-text-frame__inner {
  position: relative;
  z-index: 1;
  min-height: 0;
  border: 1px solid rgba(176, 127, 33, .74);
  padding: 7px 8px;
  background: #fbf3db;
  box-shadow: inset 0 0 18px rgba(204, 157, 56, .06);
}

.mushaf-line {
  position: relative;
  z-index: 2;
  max-width: 100%;
  font-size: 4.95cqw;
  line-height: 1.44;
}

.mushaf-qcf-content--short .mushaf-text-frame__inner {
  gap: 1.15cqw;
  justify-content: center;
  padding-top: 3cqw;
  padding-bottom: 3cqw;
}

.mushaf-qcf-content--short .mushaf-line {
  font-size: 6.05cqw;
  line-height: 1.52;
}

.mushaf-line--bismillah {
  min-height: 7cqw;
  margin: 0 0 1cqw;
  color: #17120b;
}

/* A proper illuminated surah plaque for Al-Fatihah, Al-Baqarah, and every
   surah opening supplied by the page API. */
.mushaf-surah-banner {
  position: relative;
  z-index: 2;
  margin: 1.2cqw 0 2.2cqw;
  padding: 1.25cqw 0;
  background:
    linear-gradient(90deg, transparent, rgba(239, 199, 91, .86) 17%, rgba(239, 199, 91, .86) 83%, transparent),
    repeating-linear-gradient(90deg, #086f79 0 10px, #0b8890 10px 18px, #e5b848 18px 21px);
  box-shadow:
    inset 0 1px #fff2b7,
    inset 0 -1px #fff2b7;
}

.mushaf-surah-banner::before,
.mushaf-surah-banner::after {
  position: absolute;
  top: 50%;
  color: #f9dc7a;
  font-size: 3.4cqw;
  text-shadow: 0 0 0 #a66f18, 0 1px 1px rgba(0, 0, 0, .2);
  transform: translateY(-50%);
}

.mushaf-surah-banner::before { left: 3.5cqw; }
.mushaf-surah-banner::after { right: 3.5cqw; }

.mushaf-surah-banner__inner,
.mushaf-theme--nabawiyyah .mushaf-surah-banner__inner,
.mushaf-theme--classic .mushaf-surah-banner__inner {
  width: 64%;
  min-height: 9cqw;
  justify-content: center;
  border: 2px solid #d6a83b;
  border-radius: 999px;
  padding: 1.1cqw 5cqw;
  color: #17120b;
  background: #fff8ca;
  box-shadow:
    0 0 0 2px #087681,
    0 0 0 4px #f4d776,
    0 3px 8px rgba(4, 67, 72, .22);
}

.mushaf-surah-banner__inner::before {
  inset: 3px;
  border: 1px solid rgba(8, 118, 129, .48);
  border-radius: 999px;
}

.mushaf-surah-banner__name,
.mushaf-theme--nabawiyyah .mushaf-surah-banner__name,
.mushaf-theme--classic .mushaf-surah-banner__name {
  color: #17120b;
  font-size: 4.25cqw;
  line-height: 1.05;
}



.mushaf-surah-banner__sub,
.mushaf-theme--nabawiyyah .mushaf-surah-banner__sub,
.mushaf-theme--classic .mushaf-surah-banner__sub {
  margin-top: .7cqw;
  color: #356267;
  font-size: 1.85cqw;
}

/* Opening pages: fill the large white areas with illuminated top and bottom
   panels, while keeping every Quran glyph supplied by the QCF API. */
.mushaf-page-box--opening .mushaf-text-frame__inner {
  justify-content: flex-start !important;
  gap: 1.7cqw;
  overflow: hidden;
  padding-top: 0;
  padding-bottom: 0;
  background:
    linear-gradient(rgba(251, 243, 219, .94), rgba(251, 243, 219, .94)),
    #fbf3db;
}

.mushaf-page-box--opening .mushaf-text-frame__inner::before,
.mushaf-page-box--opening .mushaf-text-frame__inner::after {
  content: '';
  position: absolute;
  right: 0;
  left: 0;
  z-index: 0;
  pointer-events: none;
}

.mushaf-page-box--opening .mushaf-text-frame__inner::before {
  top: 0;
  height: 25cqw;
  border-bottom: 2px solid #b98227;
  background:
    radial-gradient(circle at 50% 50%, #d64f3d 0 8%, #f2c95f 9% 18%, #fff1ba 19% 25%, transparent 26%) 0 1cqw / 6.4cqw 6.4cqw,
    radial-gradient(ellipse at 50% 100%, #f7e5a1 0 27%, transparent 28%) 3.2cqw 0 / 6.4cqw 6.4cqw,
    linear-gradient(90deg, #075e6b, #0d8690 20%, #e2af3e 50%, #0d8690 80%, #075e6b);
  box-shadow:
    inset 0 -1cqw 0 rgba(6, 105, 114, .92),
    inset 0 -1.45cqw 0 #f2d173,
    inset 0 -1.75cqw 0 #8f651b;
}

.mushaf-page-box--opening .mushaf-text-frame__inner::after {
  bottom: 0;
  height: 38cqw;
  border-top: 2px solid #b98227;
  background:
    radial-gradient(circle at 50% 50%, #d64f3d 0 8%, #f2c95f 9% 18%, #fff1ba 19% 25%, transparent 26%) 0 2.8cqw / 6.4cqw 6.4cqw,
    radial-gradient(ellipse at 50% 0, #f7e5a1 0 27%, transparent 28%) 3.2cqw 0 / 6.4cqw 6.4cqw,
    linear-gradient(90deg, #075e6b, #0d8690 20%, #e2af3e 50%, #0d8690 80%, #075e6b);
  box-shadow:
    inset 0 1cqw 0 rgba(6, 105, 114, .92),
    inset 0 1.45cqw 0 #f2d173,
    inset 0 1.75cqw 0 #8f651b;
}

.mushaf-page-box--baqarah .mushaf-text-frame__inner::after {
  height: 48cqw;
}

.mushaf-page-box--opening .mushaf-surah-banner {
  z-index: 3;
  flex: 0 0 auto;
  margin: 14.5cqw 0 2.4cqw;
  padding: 1.8cqw 0;
  border-top: 1px solid rgba(255, 241, 179, .82);
  border-bottom: 1px solid rgba(255, 241, 179, .82);
  background:
    radial-gradient(circle at 50% 50%, #efcc66 0 13%, #0b7580 14% 26%, transparent 27%) 0 0 / 5.2cqw 5.2cqw,
    linear-gradient(90deg, #075e69, #11919a 22%, #dbad3e 50%, #11919a 78%, #075e69);
}

.mushaf-page-box--opening .mushaf-surah-banner__inner {
  width: 68%;
  min-height: 12cqw;
  border-width: 2px;
  padding: 1.8cqw 5cqw;
  background:
    radial-gradient(ellipse at center, #fffde5 0 58%, #f8e7a2 100%);
  box-shadow:
    0 0 0 2px #f4d674,
    0 0 0 4px #087681,
    0 0 0 6px #e4b548,
    0 5px 14px rgba(3, 58, 64, .28);
}

.mushaf-page-box--opening .mushaf-surah-banner__name {
  font-size: 4.8cqw;
  font-weight: 800;
}

.mushaf-page-box--opening .mushaf-surah-banner__sub {
  font-size: 1.7cqw;
  font-weight: 800;
}

.mushaf-page-box--opening .mushaf-line {
  width: calc(100% - 3cqw);
  align-self: center;
  border-bottom: 1px solid rgba(113, 79, 25, .34);
  padding: .45cqw 0;
}

.mushaf-page-box--opening .mushaf-line:last-child {
  border-bottom: 0;
}

/* Printed-page number cartouche. */
.mushaf-page-footer {
  position: relative;
  z-index: 5;
  width: 68px;
  min-height: 25px;
  flex: 0 0 25px;
  align-items: center;
  margin: -19px auto 2px;
  border: 1px solid #0a6f78;
  border-radius: 5px 5px 18px 18px;
  padding: 1px 12px 3px;
  color: #194b4d !important;
  background: linear-gradient(180deg, #fffde8, #f5dfa0);
  box-shadow:
    0 0 0 2px #f7ebba,
    0 0 0 4px #bd8a27,
    0 0 0 5px #087680,
    0 4px 8px rgba(7, 78, 83, .2);
  font-size: .68rem;
  font-weight: 900;
}

.mushaf-page-footer::before,
.mushaf-page-footer::after {
  position: absolute;
  top: 3px;
  color: #b7791f;
  font-size: .5rem;
}

.mushaf-page-footer::before { left: 7px; }
.mushaf-page-footer::after { right: 7px; }

@media (max-width: 620px) and (min-height: 521px) {
  .mushaf-content {
    overflow: hidden;
    align-items: stretch;
  }

  .mushaf-viewport {
    width: 100%;
    max-width: none;
    height: calc(100dvh - 146px - var(--safe-top) - var(--safe-bottom));
    min-height: 0;
    aspect-ratio: auto;
    border-radius: 0;
    box-shadow: none;
  }

  .mushaf-page-box {
    max-width: none;
  }
}

@media (min-width: 621px) and (min-height: 521px) {
  .mushaf-content {
    overflow: hidden;
    align-items: stretch;
  }

  .mushaf-viewport {
    width: min(520px, calc((100dvh - 146px - var(--safe-top) - var(--safe-bottom)) * .7));
    height: calc(100dvh - 146px - var(--safe-top) - var(--safe-bottom));
    aspect-ratio: auto;
  }
}

@media (max-width: 360px) {
  .mushaf-meta { padding-right: 6px; padding-left: 6px; }
  .mushaf-meta > span { padding-right: 6px; padding-left: 6px; }
  .mushaf-text-frame { padding: 10px; }
  .mushaf-text-frame__inner { padding-right: 6px; padding-left: 6px; }
  .mushaf-line { font-size: 4.75cqw; }
}



.mushaf-page-box {
  width: min(calc(100cqw - 20px), calc((100cqh - 16px) * 941 / 1672));
  height: calc(100cqh - 16px);
  flex: 0 0 auto;
  margin: auto;
  padding: 8px 11px 11px;
  border-color: rgba(10, 92, 98, .28);
  box-shadow: 0 4px 14px rgba(33, 41, 32, .1);
}

.mushaf-frame {
  position: absolute;
  inset: 42px 11px 10px;
}

.mushaf-meta {
  padding-right: 9px;
  padding-left: 9px;
}

.mushaf-qcf-content {
  min-width: 0;
  padding: 7px 14px 17px;
}

.mushaf-text-frame {
  min-width: 0;
  margin: 0 7px;
  padding: 13px;
}

.mushaf-text-frame__inner {
  min-width: 0;
  overflow: hidden;
  padding-right: 17px;
  padding-left: 17px;
}

.mushaf-line {
  width: 100%;
  max-width: 100%;
  overflow: hidden;
  font-size: clamp(11px, 4.45cqw, 25px);
  line-height: 1.48;
}

.mushaf-qcf-content--short .mushaf-line {
  font-size: clamp(11px, 4.45cqw, 25px);
}

.mushaf-page-box:not(.mushaf-page-box--opening) .mushaf-qcf-content--short .mushaf-text-frame__inner {
  justify-content: space-evenly;
  gap: 0;
  padding-top: 12px;
  padding-bottom: 12px;
}

.mushaf-line--unicode {
  display: flex;
  flex-direction: row;
  justify-content: center;
  gap: 1.4cqw;
  overflow: visible;
  direction: rtl;
  font-family: 'Uthmanic Hafs', 'Amiri Quran', 'Amiri', serif !important;
  font-size: clamp(11px, 4.25cqw, 24px);
  line-height: 1.65;
  white-space: nowrap;
}

.mushaf-unicode-verse {
  display: inline-flex;
  align-items: center;
  gap: .7cqw;
}

.mushaf-unicode-ayah-number {
  min-width: 1.8em;
  height: 1.8em;
  display: inline-grid;
  place-items: center;
  border: 1px solid #9a702a;
  border-radius: 50%;
  color: #694b16;
  background: #fff9d9;
  font-family: 'Amiri', serif;
  font-size: .52em;
  line-height: 1;
}

.mushaf-page-box:not(.mushaf-page-box--opening) .mushaf-surah-banner {
  flex-basis: 9%;
  margin-bottom: 1%;
}

.mushaf-page-box:not(.mushaf-page-box--opening) .mushaf-surah-banner__inner {
  width: 52%;
  border: 1px solid #ad7d2c;
  border-radius: 999px;
  padding: .7% 4%;
  background: rgba(255, 250, 225, .97);
  box-shadow:
    0 0 0 2px #f1d786,
    0 0 0 3px #16777a;
}

.mushaf-page-box:not(.mushaf-page-box--opening) .mushaf-surah-banner__name {
  font-size: clamp(14px, 6cqw, 38px);
}

/* The plaque is decorative only; the Quran glyph line remains untouched. */
.mushaf-surah-banner {
  position: relative;
  margin: 2.2cqw 0 3.4cqw;
  padding: 2.8cqw 0;
  border-top: 2px solid #d9aa3d;
  border-bottom: 2px solid #d9aa3d;
  background:
    radial-gradient(circle, #d94f3d 0 9%, #f3ca61 10% 20%, #fff0b7 21% 28%, transparent 29%) 0 50% / 6.2cqw 6.2cqw,
    linear-gradient(90deg, #075f6b, #0c8992 25%, #0a7580 50%, #0c8992 75%, #075f6b);
  box-shadow:
    inset 0 3px 0 #f3d273,
    inset 0 -3px 0 #f3d273,
    0 2px 0 rgba(5, 86, 94, .35);
}

.mushaf-surah-banner__inner,
.mushaf-theme--nabawiyyah .mushaf-surah-banner__inner,
.mushaf-theme--classic .mushaf-surah-banner__inner,
.mushaf-page-box--opening .mushaf-surah-banner__inner {
  width: 76%;
  min-height: 14.5cqw;
  padding: 1.8cqw 7cqw 2.2cqw;
  border: 2px solid #b77c19;
  border-radius: 48% / 34%;
  background:
    radial-gradient(ellipse at center, #fffde7 0 60%, #f6e09a 100%);
  box-shadow:
    0 0 0 2px #f6dc7e,
    0 0 0 4px #087681,
    0 0 0 6px #e1ae3b,
    inset 0 0 0 3px rgba(8, 118, 129, .18),
    inset 0 -7px 14px rgba(199, 143, 27, .13),
    0 5px 12px rgba(4, 61, 66, .22);
}

.mushaf-surah-banner__inner::before {
  inset: 6px;
  border: 1px solid rgba(7, 104, 113, .48);
  border-radius: 48% / 34%;
}

.mushaf-surah-banner__name,
.mushaf-theme--nabawiyyah .mushaf-surah-banner__name,
.mushaf-theme--classic .mushaf-surah-banner__name,
.mushaf-page-box--opening .mushaf-surah-banner__name {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 1.7cqw;
  color: #14100a;
  font-family: 'Amiri Quran', 'Uthmanic Hafs', 'Amiri', serif;
  font-size: clamp(15px, 6.15cqw, 39px);
  font-weight: 400;
  font-kerning: normal;
  font-feature-settings: 'kern' 1, 'liga' 1, 'calt' 1;
  line-height: 1.05;
  transform: scaleX(1.08);
  transform-origin: center;
  text-shadow: 0 1px 0 rgba(181, 125, 24, .24);
}

.mushaf-surah-banner__name::before,
.mushaf-surah-banner__name::after {
  content: '';
  width: .28em;
  height: .28em;
  flex: 0 0 auto;
  border: 1px solid #916117;
  background: #d3a139;
  box-shadow: inset 0 0 0 2px #fff0ad;
  transform: rotate(45deg);
}

.mushaf-surah-banner::before,
.mushaf-surah-banner::after {
  width: 2.4cqw;
  height: 2.4cqw;
  top: 50%;
  z-index: 2;
  border: 2px solid #f3d273;
  background: #087681;
  box-shadow: 0 0 0 2px #bd801b;
  transform: translateY(-50%) rotate(45deg);
}

.mushaf-surah-banner::before { left: 5.8cqw; }
.mushaf-surah-banner::after { right: 5.8cqw; }

.mushaf-surah-banner__sub {
  display: none;
}

.mushaf-page-box--opening .mushaf-surah-banner {
  margin-top: 15.5cqw;
  margin-bottom: 3.5cqw;
}

.mushaf-page-box--opening .mushaf-line {
  width: calc(100% - 6cqw);
  padding-right: 1cqw;
  padding-left: 1cqw;
}

.mushaf-page-footer {
  margin-top: -22px;
}

@media (max-width: 415px) {
  .mushaf-page-box {
    width: min(calc(100cqw - 14px), calc((100cqh - 14px) * 941 / 1672));
    margin: auto;
    padding-right: 8px;
    padding-left: 8px;
  }

  .mushaf-qcf-content { padding-right: 10px; padding-left: 10px; }
  .mushaf-text-frame { margin-right: 4px; margin-left: 4px; padding: 10px; }
  .mushaf-text-frame__inner { padding-right: 10px; padding-left: 10px; }
  .mushaf-line { font-size: clamp(17px, 4.35cqw, 19px); }
}

@font-face {
  font-family: 'QCF Surah Name V2';
  src: url('https://static-cdn.tarteel.ai/qul/fonts/surah-names/v2/surah-name-v2.woff2') format('woff2');
  font-display: block;
}

.mushaf-page-box {
  padding: 9px 13px 13px;
  background: #fbf3db;
}

.mushaf-frame {
  inset: 43px 13px 11px;
}

.mushaf-frame__border {
  border: 1px solid #166d70;
  box-shadow:
    0 0 0 3px #fff7cf,
    0 0 0 5px #bd8c30,
    0 0 0 7px #f8e9a8,
    0 0 0 8px #166d70,
    inset 0 0 0 3px #fff8d8;
}

.mushaf-frame__border::before {
  inset: 7px;
  border-color: rgba(21, 107, 111, .6);
}

.mushaf-frame__border::after {
  inset: -7px;
  opacity: .9;
  background:
    repeating-linear-gradient(90deg, transparent 0 22px, #d6952d 22px 25px, transparent 25px 44px) top / 100% 2px no-repeat,
    repeating-linear-gradient(90deg, transparent 0 22px, #d6952d 22px 25px, transparent 25px 44px) bottom / 100% 2px no-repeat,
    repeating-linear-gradient(0deg, transparent 0 22px, #d6952d 22px 25px, transparent 25px 44px) left / 2px 100% no-repeat,
    repeating-linear-gradient(0deg, transparent 0 22px, #d6952d 22px 25px, transparent 25px 44px) right / 2px 100% no-repeat;
}

.mushaf-frame__corner {
  width: 13px;
  height: 13px;
  border: 1px solid #b57b20;
  background: radial-gradient(circle, #d34f3b 0 22%, #f2c860 23% 43%, #08747a 44% 100%);
  box-shadow: 0 0 0 2px #fff0b5, 0 0 0 3px #08747a;
}

.mushaf-frame__corner--tl { top: -6px; left: -6px; }
.mushaf-frame__corner--tr { top: -6px; right: -6px; }
.mushaf-frame__corner--bl { bottom: -6px; left: -6px; }
.mushaf-frame__corner--br { right: -6px; bottom: -6px; }

.mushaf-qcf-content {
  padding: 9px 15px 18px;
}

.mushaf-text-frame,
.mushaf-theme--nabawiyyah .mushaf-text-frame,
.mushaf-theme--classic .mushaf-text-frame {
  margin: 0 6px;
  border: 1px solid #a97624 !important;
  padding: 10px;
  background:
    repeating-conic-gradient(from 45deg, #08747a 0 12.5%, #e4b84e 0 25%, #fff2b2 0 37.5%, #c94d3b 0 50%) 0 0 / 18px 18px;
  box-shadow:
    0 0 0 2px #fff5c8,
    0 0 0 4px #08747a,
    0 0 0 6px #e0b34b,
    0 0 0 7px #fff2bd;
}

.mushaf-text-frame__inner {
  border: 1px solid rgba(26, 101, 103, .58);
  padding: 12px 18px;
  background: #fbf3db;
  box-shadow: none;
}

.mushaf-surah-banner,
.mushaf-page-box--opening .mushaf-surah-banner {
  margin: 1.5cqw 0 3cqw;
  padding: 1.2cqw 0;
  border: 0;
  background: transparent;
  box-shadow: none;
}

.mushaf-surah-banner::before,
.mushaf-surah-banner::after {
  content: '';
  width: 19%;
  height: 1px;
  top: 50%;
  border: 0;
  background: linear-gradient(90deg, transparent, #b9862b);
  box-shadow: none;
  transform: none;
}

.mushaf-surah-banner::before { left: 2%; }
.mushaf-surah-banner::after { right: 2%; transform: rotate(180deg); }

.mushaf-surah-banner__inner,
.mushaf-theme--nabawiyyah .mushaf-surah-banner__inner,
.mushaf-theme--classic .mushaf-surah-banner__inner,
.mushaf-page-box--opening .mushaf-surah-banner__inner {
  width: 56%;
  min-height: 10cqw;
  border: 1px solid #a87521;
  border-radius: 999px;
  padding: .8cqw 3.5cqw;
  background: #fff9d8;
  box-shadow:
    0 0 0 2px #f2cf6a,
    0 0 0 4px #0b7478,
    0 0 0 5px #f6dfa0,
    inset 0 0 12px rgba(190, 137, 39, .12);
}

.mushaf-surah-banner__inner::before { display: none; }

.mushaf-surah-banner__name,
.mushaf-theme--nabawiyyah .mushaf-surah-banner__name,
.mushaf-theme--classic .mushaf-surah-banner__name,
.mushaf-page-box--opening .mushaf-surah-banner__name {
  display: block;
  color: #15110b;
  font-family: 'QCF Surah Name V2', serif;
  font-size: clamp(36px, 8.2cqw, 52px);
  font-weight: 400;
  font-feature-settings: 'liga' 1;
  line-height: .8;
  transform: none;
  text-shadow: none;
}

.mushaf-surah-banner__name::before,
.mushaf-surah-banner__name::after {
  display: none;
}

.mushaf-page-box--opening .mushaf-text-frame__inner {
  gap: 1.2cqw;
  padding-top: 18px;
  padding-bottom: 28px;
  background: #fbf3db;
}

.mushaf-page-box--opening .mushaf-text-frame__inner::before {
  display: none;
}

.mushaf-page-box--opening .mushaf-text-frame__inner::after {
  right: 18%;
  bottom: 9px;
  left: 18%;
  height: 9px;
  border: 0;
  border-top: 1px solid #c49334;
  border-bottom: 1px solid #c49334;
  background: radial-gradient(circle, #0b7478 0 2px, transparent 2.5px) center / 14px 7px repeat-x;
  box-shadow: none;
}

.mushaf-page-box--opening .mushaf-line {
  width: calc(100% - 4cqw);
  padding: .6cqw 0;
}



.mushaf-page-box,
.mushaf-page-box--opening,
.mushaf-theme--nabawiyyah,
.mushaf-theme--classic,
.mushaf-page-box--baqarah {
  padding: 16px !important; /* Single uniform padding for the whole screen */
  margin: 0 !important;
  border: 0 !important;
  box-shadow: none !important;
  display: flex !important;
  flex-direction: column !important;
  justify-content: flex-start !important;
  height: 100% !important;
  min-height: 100% !important;
  width: 100% !important;
  max-width: 100% !important;
}

.mushaf-page-box--opening {
  justify-content: flex-start !important;
}

/* The first two printed pages use a compact composition in the middle of the
   page: surah heading + the complete opening passage. */
.mushaf-page-box--opening .mushaf-text-frame__inner {
  justify-content: center !important;
}

.mushaf-qcf-content {
  flex: 1 0 auto !important;
  display: flex !important;
  flex-direction: column !important;
  justify-content: center !important;
  margin: auto 0 !important;
}

/* Hide all ornate frames and borders on plain layout */
.mushaf-page-box:not(.mushaf-page-box--with-frame) .mushaf-frame,
.mushaf-page-box:not(.mushaf-page-box--with-frame) .mushaf-frame__border,
.mushaf-page-box:not(.mushaf-page-box--with-frame) .mushaf-frame__corner,
.mushaf-page-box:not(.mushaf-page-box--with-frame) .mushaf-meta,
.mushaf-page-box:not(.mushaf-page-box--with-frame) .mushaf-text-frame__inner::before,
.mushaf-page-box:not(.mushaf-page-box--with-frame) .mushaf-text-frame__inner::after {
  display: none !important;
}

/* Restyle text frame to be completely plain on plain layout */
.mushaf-page-box:not(.mushaf-page-box--with-frame) .mushaf-text-frame,
.mushaf-page-box:not(.mushaf-page-box--with-frame) .mushaf-theme--nabawiyyah .mushaf-text-frame,
.mushaf-page-box:not(.mushaf-page-box--with-frame) .mushaf-theme--classic .mushaf-text-frame {
  margin: 0 !important;
  border: 0 !important;
  padding: 0 !important;
  background: transparent !important;
  box-shadow: none !important;
}

.mushaf-page-box:not(.mushaf-page-box--with-frame) .mushaf-text-frame__inner {
  border: 0 !important;
  padding: 0 !important;
  background: transparent !important;
}

.mushaf-page-box:not(.mushaf-page-box--with-frame) .mushaf-qcf-content {
  flex: 1 0 auto !important;
  display: flex !important;
  flex-direction: column !important;
  justify-content: center !important;
  padding: 0 4.5cqw !important;
  margin: 0 !important;
}

.mushaf-page-box--with-frame {
  /* Maintain book page aspect ratio and center vertically in viewport */
  align-self: center !important;
  flex: 0 0 auto !important; /* Prevent vertical stretching in flex layout */
  display: flex !important; /* Flex container to distribute space for children */
  flex-direction: column !important;
  padding: 8px 5px 14px !important; /* Increased left/right padding to prevent corner rosette clipping */
  width: 100% !important; /* Take full width of the slide column */
  height: auto !important;
  min-height: auto !important; /* Override base class min-height: 100% to prevent vertical stretching */
  max-height: none !important; /* Never shrink vertically to fit screen height; scroll parent slide instead */
  max-width: 100% !important;
  min-width: 0 !important; /* Prevent expanding horizontally */
  overflow: visible !important; /* Allow corner rosettes to overflow borders without clipping */
  aspect-ratio: 1 / 1.68 !important; /* Match full Madinah page height */
  box-sizing: border-box !important;
  position: relative !important;
}
.mushaf-page-box--with-frame .mushaf-frame {
  display: block !important;
  inset: 11cqw 2.1cqw 2.1cqw !important;
}
.mushaf-page-box--with-frame .mushaf-frame__border {
  display: block !important;
  border: none !important;
  box-shadow: none !important;
}
/* Multi-layer gold frame lines via ::before */
.mushaf-page-box--with-frame .mushaf-frame__border::before {
  content: '' !important;
  display: block !important;
  position: absolute !important;
  inset: -7px !important;
  border: none !important; /* Override default 1px gold border */
  pointer-events: none !important;
  box-shadow:
    0 0 0 1px rgb(22, 109, 112),
    0 0 0 3px rgb(255, 247, 207),
    0 0 0 5px rgb(189, 140, 48),
    0 0 0 7px rgb(248, 233, 168),
    0 0 0 8px rgb(22, 109, 112) !important;
}
.mushaf-page-box--with-frame .mushaf-frame__border::after {
  content: '' !important;
  display: block !important;
  position: absolute !important;
  inset: -6px !important;
  border: none !important; /* Override default borders */
  pointer-events: none !important;
  opacity: 1 !important;
  background:
    /* TOP arabesque vine */
    url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='40' height='11' viewBox='0 0 40 11'%3E%3Crect width='40' height='11' fill='rgb(251,243,208)'/%3E%3Crect y='3.5' width='40' height='4' fill='rgb(22,109,112)'/%3E%3Cpath d='M0 5.5 C5 1,10 1,13 5.5 C16 10,21 10,20 5.5 C19 1,24 1,27 5.5 C30 10,35 10,40 5.5' fill='none' stroke='rgb(189,140,48)' stroke-width='1.3'/%3E%3Cpath d='M0 5.5 C5 10,10 10,13 5.5 C16 1,21 1,20 5.5 C19 10,24 10,27 5.5 C30 1,35 1,40 5.5' fill='none' stroke='rgb(189,140,48)' stroke-width='0.7' opacity='0.6'/%3E%3Ccircle cx='0' cy='5.5' r='1.8' fill='rgb(211,79,59)'/%3E%3Ccircle cx='20' cy='5.5' r='1.8' fill='rgb(211,79,59)'/%3E%3Ccircle cx='40' cy='5.5' r='1.8' fill='rgb(211,79,59)'/%3E%3Ccircle cx='10' cy='2' r='1.2' fill='rgb(242,200,96)'/%3E%3Ccircle cx='30' cy='9' r='1.2' fill='rgb(242,200,96)'/%3E%3Cline x1='0' y1='0.6' x2='40' y2='0.6' stroke='rgb(189,140,48)' stroke-width='0.9'/%3E%3Cline x1='0' y1='10.4' x2='40' y2='10.4' stroke='rgb(189,140,48)' stroke-width='0.9'/%3E%3C/svg%3E") top / 40px 11px repeat-x,
    /* BOTTOM arabesque vine */
    url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='40' height='11' viewBox='0 0 40 11'%3E%3Crect width='40' height='11' fill='rgb(251,243,208)'/%3E%3Crect y='3.5' width='40' height='4' fill='rgb(22,109,112)'/%3E%3Cpath d='M0 5.5 C5 1,10 1,13 5.5 C16 10,21 10,20 5.5 C19 1,24 1,27 5.5 C30 10,35 10,40 5.5' fill='none' stroke='rgb(189,140,48)' stroke-width='1.3'/%3E%3Cpath d='M0 5.5 C5 10,10 10,13 5.5 C16 1,21 1,20 5.5 C19 10,24 10,27 5.5 C30 1,35 1,40 5.5' fill='none' stroke='rgb(189,140,48)' stroke-width='0.7' opacity='0.6'/%3E%3Ccircle cx='0' cy='5.5' r='1.8' fill='rgb(211,79,59)'/%3E%3Ccircle cx='20' cy='5.5' r='1.8' fill='rgb(211,79,59)'/%3E%3Ccircle cx='40' cy='5.5' r='1.8' fill='rgb(211,79,59)'/%3E%3Ccircle cx='10' cy='2' r='1.2' fill='rgb(242,200,96)'/%3E%3Ccircle cx='30' cy='9' r='1.2' fill='rgb(242,200,96)'/%3E%3Cline x1='0' y1='0.6' x2='40' y2='0.6' stroke='rgb(189,140,48)' stroke-width='0.9'/%3E%3Cline x1='0' y1='10.4' x2='40' y2='10.4' stroke='rgb(189,140,48)' stroke-width='0.9'/%3E%3C/svg%3E") bottom / 40px 11px repeat-x,
    /* LEFT arabesque vine */
    url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='40' viewBox='0 0 11 40'%3E%3Crect width='11' height='40' fill='rgb(251,243,208)'/%3E%3Crect x='3.5' width='4' height='40' fill='rgb(22,109,112)'/%3E%3Cpath d='M5.5 0 C1 5,1 10,5.5 13 C10 16,10 21,5.5 20 C1 19,1 24,5.5 27 C10 30,10 35,5.5 40' fill='none' stroke='rgb(189,140,48)' stroke-width='1.3'/%3E%3Cpath d='M5.5 0 C10 5,10 10,5.5 13 C1 16,1 21,5.5 20 C10 19,10 24,5.5 27 C1 30,1 35,5.5 40' fill='none' stroke='rgb(189,140,48)' stroke-width='0.7' opacity='0.6'/%3E%3Ccircle cx='5.5' cy='0' r='1.8' fill='rgb(211,79,59)'/%3E%3Ccircle cx='5.5' cy='20' r='1.8' fill='rgb(211,79,59)'/%3E%3Ccircle cx='5.5' cy='40' r='1.8' fill='rgb(211,79,59)'/%3E%3Ccircle cx='2' cy='10' r='1.2' fill='rgb(242,200,96)'/%3E%3Ccircle cx='9' cy='30' r='1.2' fill='rgb(242,200,96)'/%3E%3Cline x1='0.6' y1='0' x2='0.6' y2='40' stroke='rgb(189,140,48)' stroke-width='0.9'/%3E%3Cline x1='10.4' y1='0' x2='10.4' y2='40' stroke='rgb(189,140,48)' stroke-width='0.9'/%3E%3C/svg%3E") left / 11px 40px repeat-y,
    /* RIGHT arabesque vine */
    url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='40' viewBox='0 0 11 40'%3E%3Crect width='11' height='40' fill='rgb(251,243,208)'/%3E%3Crect x='3.5' width='4' height='40' fill='rgb(22,109,112)'/%3E%3Cpath d='M5.5 0 C1 5,1 10,5.5 13 C10 16,10 21,5.5 20 C1 19,1 24,5.5 27 C10 30,10 35,5.5 40' fill='none' stroke='rgb(189,140,48)' stroke-width='1.3'/%3E%3Cpath d='M5.5 0 C10 5,10 10,5.5 13 C1 16,1 21,5.5 20 C10 19,10 24,5.5 27 C1 30,1 35,5.5 40' fill='none' stroke='rgb(189,140,48)' stroke-width='0.7' opacity='0.6'/%3E%3Ccircle cx='5.5' cy='0' r='1.8' fill='rgb(211,79,59)'/%3E%3Ccircle cx='5.5' cy='20' r='1.8' fill='rgb(211,79,59)'/%3E%3Ccircle cx='5.5' cy='40' r='1.8' fill='rgb(211,79,59)'/%3E%3Ccircle cx='2' cy='10' r='1.2' fill='rgb(242,200,96)'/%3E%3Ccircle cx='9' cy='30' r='1.2' fill='rgb(242,200,96)'/%3E%3Cline x1='0.6' y1='0' x2='0.6' y2='40' stroke='rgb(189,140,48)' stroke-width='0.9'/%3E%3Cline x1='10.4' y1='0' x2='10.4' y2='40' stroke='rgb(189,140,48)' stroke-width='0.9'/%3E%3C/svg%3E") right / 11px 40px repeat-y !important;
}
.mushaf-page-box--with-frame .mushaf-frame__corner {
  display: block !important;
  width: 5.8cqw !important; /* Proportional size */
  height: 5.8cqw !important;
  border: none !important;
  box-shadow: none !important;
  /* Ottoman Rosette Corner Medallion */
  background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'%3E%3Ccircle cx='12' cy='12' r='11' fill='rgb(22,109,112)' stroke='rgb(189,140,48)' stroke-width='1.5'/%3E%3Ccircle cx='12' cy='6.5' r='1.8' fill='rgb(242,200,96)'/%3E%3Ccircle cx='12' cy='17.5' r='1.8' fill='rgb(242,200,96)'/%3E%3Ccircle cx='6.5' cy='12' r='1.8' fill='rgb(242,200,96)'/%3E%3Ccircle cx='17.5' cy='12' r='1.8' fill='rgb(242,200,96)'/%3E%3Ccircle cx='8.1' cy='8.1' r='1.8' fill='rgb(242,200,96)'/%3E%3Ccircle cx='15.9' cy='15.9' r='1.8' fill='rgb(242,200,96)'/%3E%3Ccircle cx='8.1' cy='15.9' r='1.8' fill='rgb(242,200,96)'/%3E%3Ccircle cx='15.9' cy='8.1' r='1.8' fill='rgb(242,200,96)'/%3E%3Ccircle cx='12' cy='12' r='3.8' fill='rgb(211,79,59)' stroke='rgb(189,140,48)' stroke-width='0.8'/%3E%3Ccircle cx='12' cy='12' r='1.4' fill='rgb(242,200,96)'/%3E%3C/svg%3E") center / contain no-repeat !important;
}
.mushaf-page-box--with-frame .mushaf-frame__corner--tl { top: -3.2cqw !important; left: -3.2cqw !important; }
.mushaf-page-box--with-frame .mushaf-frame__corner--tr { top: -3.2cqw !important; right: -3.2cqw !important; }
.mushaf-page-box--with-frame .mushaf-frame__corner--bl { bottom: -3.2cqw !important; left: -3.2cqw !important; }
.mushaf-page-box--with-frame .mushaf-frame__corner--br { right: -3.2cqw !important; bottom: -3.2cqw !important; }

/* -- Book Spine: Remove ENTIRE inner frame border on facing pages --
   Even Page (left side of book) ? no right border/vines, no right corners
   Odd Page (right side of book) ? no left border/vines, no left corners */
.mushaf-page-box--left-page .mushaf-frame__corner--tr,
.mushaf-page-box--left-page .mushaf-frame__corner--br {
  display: none !important;
}
.mushaf-page-box--left-page .mushaf-frame__border {
  border: none !important;
}
.mushaf-page-box--left-page .mushaf-frame__border::before {
  inset: -1.9cqw 0px -1.9cqw -1.9cqw !important; /* Align exactly to 0px on right */
  border-right: 1px solid rgb(22, 109, 112) !important; /* Draw vertical line all the way to top frame */
  clip-path: inset(0px 0px 0px 0px) !important;
}
.mushaf-page-box--left-page .mushaf-frame__border::after {
  inset: -1.6cqw 0px -1.6cqw -1.6cqw !important; /* Align exactly to 0px on right */
  clip-path: inset(0px 0px 0px 0px) !important;
}
.mushaf-page-box--left-page .mushaf-frame__border::after {
  background:
    url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='40' height='11' viewBox='0 0 40 11'%3E%3Crect width='40' height='11' fill='rgb(251,243,208)'/%3E%3Crect y='3.5' width='40' height='4' fill='rgb(22,109,112)'/%3E%3Cpath d='M0 5.5 C5 1,10 1,13 5.5 C16 10,21 10,20 5.5 C19 1,24 1,27 5.5 C30 10,35 10,40 5.5' fill='none' stroke='rgb(189,140,48)' stroke-width='1.3'/%3E%3Cpath d='M0 5.5 C5 10,10 10,13 5.5 C16 1,21 1,20 5.5 C19 10,24 10,27 5.5 C30 1,35 1,40 5.5' fill='none' stroke='rgb(189,140,48)' stroke-width='0.7' opacity='0.6'/%3E%3Ccircle cx='0' cy='5.5' r='1.8' fill='rgb(211,79,59)'/%3E%3Ccircle cx='20' cy='5.5' r='1.8' fill='rgb(211,79,59)'/%3E%3Ccircle cx='40' cy='5.5' r='1.8' fill='rgb(211,79,59)'/%3E%3Ccircle cx='10' cy='2' r='1.2' fill='rgb(242,200,96)'/%3E%3Ccircle cx='30' cy='9' r='1.2' fill='rgb(242,200,96)'/%3E%3Cline x1='0' y1='0.6' x2='40' y2='0.6' stroke='rgb(189,140,48)' stroke-width='0.9'/%3E%3Cline x1='0' y1='10.4' x2='40' y2='10.4' stroke='rgb(189,140,48)' stroke-width='0.9'/%3E%3C/svg%3E") top / 40px 11px repeat-x,
    url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='40' height='11' viewBox='0 0 40 11'%3E%3Crect width='40' height='11' fill='rgb(251,243,208)'/%3E%3Crect y='3.5' width='40' height='4' fill='rgb(22,109,112)'/%3E%3Cpath d='M0 5.5 C5 1,10 1,13 5.5 C16 10,21 10,20 5.5 C19 1,24 1,27 5.5 C30 10,35 10,40 5.5' fill='none' stroke='rgb(189,140,48)' stroke-width='1.3'/%3E%3Cpath d='M0 5.5 C5 10,10 10,13 5.5 C16 1,21 1,20 5.5 C19 10,24 10,27 5.5 C30 1,35 1,40 5.5' fill='none' stroke='rgb(189,140,48)' stroke-width='0.7' opacity='0.6'/%3E%3Ccircle cx='0' cy='5.5' r='1.8' fill='rgb(211,79,59)'/%3E%3Ccircle cx='20' cy='5.5' r='1.8' fill='rgb(211,79,59)'/%3E%3Ccircle cx='40' cy='5.5' r='1.8' fill='rgb(211,79,59)'/%3E%3Ccircle cx='10' cy='2' r='1.2' fill='rgb(242,200,96)'/%3E%3Ccircle cx='30' cy='9' r='1.2' fill='rgb(242,200,96)'/%3E%3Cline x1='0' y1='0.6' x2='40' y2='0.6' stroke='rgb(189,140,48)' stroke-width='0.9'/%3E%3Cline x1='0' y1='10.4' x2='40' y2='10.4' stroke='rgb(189,140,48)' stroke-width='0.9'/%3E%3C/svg%3E") bottom / 40px 11px repeat-x,
    url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='40' viewBox='0 0 11 40'%3E%3Crect width='11' height='40' fill='rgb(251,243,208)'/%3E%3Crect x='3.5' width='4' height='40' fill='rgb(22,109,112)'/%3E%3Cpath d='M5.5 0 C1 5,1 10,5.5 13 C10 16,10 21,5.5 20 C1 19,1 24,5.5 27 C10 30,10 35,5.5 40' fill='none' stroke='rgb(189,140,48)' stroke-width='1.3'/%3E%3Cpath d='M5.5 0 C10 5,10 10,5.5 13 C1 16,1 21,5.5 20 C10 19,10 24,5.5 27 C1 30,1 35,5.5 40' fill='none' stroke='rgb(189,140,48)' stroke-width='0.7' opacity='0.6'/%3E%3Ccircle cx='5.5' cy='0' r='1.8' fill='rgb(211,79,59)'/%3E%3Ccircle cx='5.5' cy='20' r='1.8' fill='rgb(211,79,59)'/%3E%3Ccircle cx='5.5' cy='40' r='1.8' fill='rgb(211,79,59)'/%3E%3Ccircle cx='2' cy='10' r='1.2' fill='rgb(242,200,96)'/%3E%3Ccircle cx='9' cy='30' r='1.2' fill='rgb(242,200,96)'/%3E%3Cline x1='0.6' y1='0' x2='0.6' y2='40' stroke='rgb(189,140,48)' stroke-width='0.9'/%3E%3Cline x1='10.4' y1='0' x2='10.4' y2='40' stroke='rgb(189,140,48)' stroke-width='0.9'/%3E%3C/svg%3E") left / 11px 40px repeat-y !important;
}

.mushaf-page-box--right-page .mushaf-frame__corner--tl,
.mushaf-page-box--right-page .mushaf-frame__corner--bl {
  display: none !important;
}
.mushaf-page-box--right-page .mushaf-frame__border {
  border: none !important;
}
.mushaf-page-box--right-page .mushaf-frame__border::before {
  inset: -1.9cqw -1.9cqw -1.9cqw 0px !important; /* Align exactly to 0px on left */
  border-left: 1px solid rgb(22, 109, 112) !important; /* Draw vertical line all the way to top frame */
  clip-path: inset(0px 0px 0px 0px) !important;
}
.mushaf-page-box--right-page .mushaf-frame__border::after {
  inset: -1.6cqw -1.6cqw -1.6cqw 0px !important; /* Align exactly to 0px on left */
  clip-path: inset(0px 0px 0px 0px) !important;
}
.mushaf-page-box--right-page .mushaf-frame__border::after {
  background:
    url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='40' height='11' viewBox='0 0 40 11'%3E%3Crect width='40' height='11' fill='rgb(251,243,208)'/%3E%3Crect y='3.5' width='40' height='4' fill='rgb(22,109,112)'/%3E%3Cpath d='M0 5.5 C5 1,10 1,13 5.5 C16 10,21 10,20 5.5 C19 1,24 1,27 5.5 C30 10,35 10,40 5.5' fill='none' stroke='rgb(189,140,48)' stroke-width='1.3'/%3E%3Cpath d='M0 5.5 C5 10,10 10,13 5.5 C16 1,21 1,20 5.5 C19 10,24 10,27 5.5 C30 1,35 1,40 5.5' fill='none' stroke='rgb(189,140,48)' stroke-width='0.7' opacity='0.6'/%3E%3Ccircle cx='0' cy='5.5' r='1.8' fill='rgb(211,79,59)'/%3E%3Ccircle cx='20' cy='5.5' r='1.8' fill='rgb(211,79,59)'/%3E%3Ccircle cx='40' cy='5.5' r='1.8' fill='rgb(211,79,59)'/%3E%3Ccircle cx='10' cy='2' r='1.2' fill='rgb(242,200,96)'/%3E%3Ccircle cx='30' cy='9' r='1.2' fill='rgb(242,200,96)'/%3E%3Cline x1='0' y1='0.6' x2='40' y2='0.6' stroke='rgb(189,140,48)' stroke-width='0.9'/%3E%3Cline x1='0' y1='10.4' x2='40' y2='10.4' stroke='rgb(189,140,48)' stroke-width='0.9'/%3E%3C/svg%3E") top / 40px 11px repeat-x,
    url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='40' height='11' viewBox='0 0 40 11'%3E%3Crect width='40' height='11' fill='rgb(251,243,208)'/%3E%3Crect y='3.5' width='40' height='4' fill='rgb(22,109,112)'/%3E%3Cpath d='M0 5.5 C5 1,10 1,13 5.5 C16 10,21 10,20 5.5 C19 1,24 1,27 5.5 C30 10,35 10,40 5.5' fill='none' stroke='rgb(189,140,48)' stroke-width='1.3'/%3E%3Cpath d='M0 5.5 C5 10,10 10,13 5.5 C16 1,21 1,20 5.5 C19 10,24 10,27 5.5 C30 1,35 1,40 5.5' fill='none' stroke='rgb(189,140,48)' stroke-width='0.7' opacity='0.6'/%3E%3Ccircle cx='0' cy='5.5' r='1.8' fill='rgb(211,79,59)'/%3E%3Ccircle cx='20' cy='5.5' r='1.8' fill='rgb(211,79,59)'/%3E%3Ccircle cx='40' cy='5.5' r='1.8' fill='rgb(211,79,59)'/%3E%3Ccircle cx='10' cy='2' r='1.2' fill='rgb(242,200,96)'/%3E%3Ccircle cx='30' cy='9' r='1.2' fill='rgb(242,200,96)'/%3E%3Cline x1='0' y1='0.6' x2='40' y2='0.6' stroke='rgb(189,140,48)' stroke-width='0.9'/%3E%3Cline x1='0' y1='10.4' x2='40' y2='10.4' stroke='rgb(189,140,48)' stroke-width='0.9'/%3E%3C/svg%3E") bottom / 40px 11px repeat-x,
    url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='40' viewBox='0 0 11 40'%3E%3Crect width='11' height='40' fill='rgb(251,243,208)'/%3E%3Crect x='3.5' width='4' height='40' fill='rgb(22,109,112)'/%3E%3Cpath d='M5.5 0 C1 5,1 10,5.5 13 C10 16,10 21,5.5 20 C1 19,1 24,5.5 27 C10 30,10 35,5.5 40' fill='none' stroke='rgb(189,140,48)' stroke-width='1.3'/%3E%3Cpath d='M5.5 0 C10 5,10 10,5.5 13 C1 16,1 21,5.5 20 C10 19,10 24,5.5 27 C1 30,1 35,5.5 40' fill='none' stroke='rgb(189,140,48)' stroke-width='0.7' opacity='0.6'/%3E%3Ccircle cx='5.5' cy='0' r='1.8' fill='rgb(211,79,59)'/%3E%3Ccircle cx='5.5' cy='20' r='1.8' fill='rgb(211,79,59)'/%3E%3Ccircle cx='5.5' cy='40' r='1.8' fill='rgb(211,79,59)'/%3E%3Ccircle cx='2' cy='10' r='1.2' fill='rgb(242,200,96)'/%3E%3Ccircle cx='9' cy='30' r='1.2' fill='rgb(242,200,96)'/%3E%3Cline x1='0.6' y1='0' x2='0.6' y2='40' stroke='rgb(189,140,48)' stroke-width='0.9'/%3E%3Cline x1='10.4' y1='0' x2='10.4' y2='40' stroke='rgb(189,140,48)' stroke-width='0.9'/%3E%3C/svg%3E") right / 11px 40px repeat-y !important;
}

/* Shift meta inward so pills never overlap the corner medallions */
.mushaf-page-box--with-frame .mushaf-meta {
  display: flex !important;
  position: absolute !important;
  top: 3.8cqw !important; /* Proportional top offset */
  left: 9.0cqw !important; /* Proportional left offset */
  right: 9.0cqw !important; /* Proportional right offset */
  height: 8.5cqw !important; /* Proportional height */
  margin: 0 !important;
  padding: 0 !important;
  align-items: flex-end !important; /* Resting exactly on the border line */
  z-index: 10 !important;
}
.mushaf-page-box--with-frame .mushaf-meta > span {
  min-height: 5.5cqw !important;
  font-size: 2.6cqw !important;
  padding: 0 2.5cqw !important;
  border-width: 1px !important;
  line-height: 1 !important;
}
.mushaf-page-box--with-frame .mushaf-meta__page {
  width: 12cqw !important;
  flex: 0 0 12cqw !important;
  font-size: 2.8cqw !important;
}
.mushaf-page-box--with-frame .mushaf-qcf-content {
  flex: 1 1 auto !important;
  display: flex !important;
  flex-direction: column !important;
  justify-content: space-between !important; /* Distribute lines evenly to fill frame */
  gap: 0 !important;
  padding: 11.5cqw 4.5cqw 0cqw !important; /* 4.5cqw left/right gives ~2.4cqw breathing room inside frame border */
  margin: 0 !important;
  box-sizing: border-box !important;
  min-height: 0 !important;
  min-width: 0 !important;
  width: 100% !important;
}
/* Hide redundant page footer at the bottom */
.mushaf-page-box--with-frame .mushaf-page-footer {
  display: none !important;
}
.mushaf-page-box--with-frame .mushaf-surah-header-group {
  display: flex !important;
  flex-direction: column !important;
  align-items: center !important;
  justify-content: center !important;
  width: 100% !important;
  margin-top: 2.0cqw !important;     /* Safe spacing from previous text */
  margin-bottom: 2.0cqw !important;  /* Safe spacing to next text */
  gap: 1.5cqw !important;            /* Safe gap between banner and bismillah */
  flex-shrink: 0 !important;
}
.mushaf-page-box--with-frame .mushaf-bismillah-calligraphy {
  margin: 0 !important;
  margin-bottom: 0.5cqw !important;
  line-height: 1 !important;
  font-size: clamp(20px, 4cqw, 32px) !important;
}

.mushaf-page-box--with-frame .mushaf-surah-banner {
  margin: 0 !important;
  padding: 0 !important;
  border: 0 !important;
  background: transparent !important;
  box-shadow: none !important;
  display: flex !important;
  justify-content: center !important;
  align-items: center !important;
  width: 100% !important;
  flex: 1 1 auto !important; /* Override layout flex properties */
  flex-basis: auto !important; /* Prevent 9% flex-basis squish along the row axis */
  flex-shrink: 0 !important;
}
.mushaf-page-box--with-frame .mushaf-surah-banner::before,
.mushaf-page-box--with-frame .mushaf-surah-banner::after {
  display: none !important;
}
.mushaf-page-box--with-frame .mushaf-surah-banner__inner,
.mushaf-page-box--with-frame.mushaf-theme--nabawiyyah .mushaf-surah-banner__inner,
.mushaf-page-box--with-frame.mushaf-theme--classic .mushaf-surah-banner__inner,
.mushaf-page-box--with-frame.mushaf-theme--dark .mushaf-surah-banner__inner {
  width: 100% !important; /* Stretch fully across the text column */
  box-sizing: border-box !important;
  min-height: 8.5cqw !important; /* Proportional sizing */
  max-height: 9.5cqw !important;
  display: flex !important;
  flex-direction: row !important;
  justify-content: space-between !important;
  align-items: center !important;
  gap: 0.5cqw !important;
  padding: 1.0cqw 1.5cqw !important;
  border-radius: 0px !important;
  position: relative !important;
  overflow: hidden !important;
  /* Premium Traditional Illuminated Arabesque/Floral Vignette Background */
  background:
    url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='80' height='34' viewBox='0 0 80 34'%3E%3Cdefs%3E%3ClinearGradient id='bg' x1='0' y1='0' x2='0' y2='1'%3E%3Cstop offset='0%25' stop-color='%23fffcf3'/%3E%3Cstop offset='50%25' stop-color='%23fbf5e2'/%3E%3Cstop offset='100%25' stop-color='%23f5ebc8'/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='80' height='34' fill='url(%23bg)'/%3E%3Cpath d='M0 17 C15 5, 25 5, 40 17 C55 29, 65 29, 80 17' fill='none' stroke='%23d4af37' stroke-width='1.2' opacity='0.7'/%3E%3Cpath d='M0 17 C15 29, 25 29, 40 17 C55 5, 65 5, 80 17' fill='none' stroke='%23d4af37' stroke-width='1.2' opacity='0.7'/%3E%3Ccircle cx='40' cy='17' r='3.5' fill='%23166d70' stroke='%23bd8c30' stroke-width='0.8'/%3E%3Ccircle cx='40' cy='17' r='1.5' fill='%23d34f3b'/%3E%3Ccircle cx='0' cy='17' r='3.5' fill='%23166d70' stroke='%23bd8c30' stroke-width='0.8'/%3E%3Ccircle cx='0' cy='17' r='1.5' fill='%23d34f3b'/%3E%3Ccircle cx='80' cy='17' r='3.5' fill='%23166d70' stroke='%23bd8c30' stroke-width='0.8'/%3E%3Ccircle cx='80' cy='17' r='1.5' fill='%23d34f3b'/%3E%3Cpath d='M12 11 Q20 7 28 13' fill='none' stroke='%23bd8c30' stroke-width='1'/%3E%3Cpath d='M52 23 Q60 27 68 21' fill='none' stroke='%23bd8c30' stroke-width='1'/%3E%3Ccircle cx='20' cy='9' r='1.8' fill='%23d34f3b'/%3E%3Ccircle cx='60' cy='25' r='1.8' fill='%23d34f3b'/%3E%3Ccircle cx='28' cy='22' r='1.5' fill='%23eec256'/%3E%3Ccircle cx='52' cy='12' r='1.5' fill='%23eec256'/%3E%3Cpath d='M15 15 C13 12, 9 14, 11 17 Z' fill='%233c763d' opacity='0.85'/%3E%3Cpath d='M65 19 C67 22, 71 20, 69 17 Z' fill='%233c763d' opacity='0.85'/%3E%3C/svg%3E") center / 80px 34px repeat !important;
  /* 3-layer gold frame around banner */
  box-shadow:
    0 0 0 1px rgb(22, 109, 112),
    0 0 0 3px rgb(255, 247, 207),
    0 0 0 4.5px rgb(189, 140, 48) !important;
}
.mushaf-page-box--with-frame .mushaf-surah-banner__inner::before,
.mushaf-page-box--with-frame .mushaf-surah-banner__inner::after {
  display: none !important;
}
/* Calligraphic cartouche in the center for the Surah name */
.mushaf-page-box--with-frame .mushaf-surah-banner__name,
.mushaf-page-box--with-frame.mushaf-theme--nabawiyyah .mushaf-surah-banner__name,
.mushaf-page-box--with-frame.mushaf-theme--classic .mushaf-surah-banner__name,
.mushaf-page-box--with-frame.mushaf-theme--dark .mushaf-surah-banner__name {
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  height: 6.8cqw !important; /* Proportional height to prevent parent stretching */
  min-height: 6.8cqw !important;
  background: rgb(255, 255, 250) !important;
  border: 1px solid rgb(189, 140, 48) !important;
  border-radius: 999px !important;
  padding: 0 4.0cqw !important; /* Proportional padding */
  /* Clean unified border-ring */
  box-shadow: 0 0 0 1.5px rgb(22, 109, 112) !important;
  color: #000000 !important;
  font-family: 'QCF Surah Name V2', sans-serif !important;
  font-size: 6.2cqw !important; /* Proportional font-size */
  z-index: 3 !important;
  margin: 0 auto !important;
  transform: none !important;
  text-shadow: none !important;
  flex-shrink: 0 !important;
  line-height: 1 !important;
}
/* Gold-bordered pills for Revelation Place and Ayah count */
.mushaf-page-box--with-frame .mushaf-surah-banner__sub,
.mushaf-page-box--with-frame.mushaf-theme--nabawiyyah .mushaf-surah-banner__sub,
.mushaf-page-box--with-frame.mushaf-theme--classic .mushaf-surah-banner__sub,
.mushaf-page-box--with-frame.mushaf-theme--dark .mushaf-surah-banner__sub {
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  height: 6.8cqw !important; /* Match name pill height proportionally */
  min-height: 6.8cqw !important;
  background: rgb(255, 255, 250) !important;
  border: 1px solid rgb(189, 140, 48) !important;
  border-radius: 999px !important;
  padding: 0px 2.0cqw !important; /* Proportional padding */
  box-shadow: 0 0 0 1.5px rgb(22, 109, 112) !important;
  font-family: 'Amiri', 'Uthmanic Hafs', serif !important;
  font-size: 2.8cqw !important; /* Proportional font-size */
  font-weight: 400 !important;
  color: #000000 !important;
  z-index: 3 !important;
  min-width: 14cqw !important; /* Proportional min-width */
  max-width: 18cqw !important; /* Proportional max-width */
  text-align: center !important;
  text-transform: none !important;
  direction: rtl !important;
  flex-shrink: 0 !important;
  line-height: 6.8cqw !important;
}

.mushaf-page-box--with-frame .mushaf-line,
.mushaf-page-box--with-frame .mushaf-qcf-content--short .mushaf-line,
.mushaf-page-box--with-frame .mushaf-qcf-content--short .mushaf-line--qcf {
  font-size: 6cqw !important; /* Uniform font size matching both pages to prevent bottom cutoff */
  line-height: 1.25 !important; /* Compact to fit 15 lines perfectly inside the frame */
  border-bottom: 1px solid rgb(71 68 64 / 32%) !important;
  padding-top: 0.3cqw !important;
  padding-bottom: 0.5cqw !important;
  margin-bottom: 0 !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
}
/* Unicode fallback has matching font-size */
.mushaf-page-box--with-frame .mushaf-line--unicode,
.mushaf-page-box--with-frame .mushaf-qcf-content--short .mushaf-line--unicode {
  font-size: 5.2cqw !important;
}
.mushaf-page-box--with-frame .mushaf-line:last-child,
.mushaf-page-box--with-frame .mushaf-line--no-border {
  border-bottom: 0 !important;
}
.mushaf-page-box--with-frame .mushaf-line--banner-row {
  padding: 0 !important;
  border-bottom: 0 !important;
  height: auto !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
}
/* Bismillah gets a separator line below it so the first verse starts cleanly */
.mushaf-page-box--with-frame .mushaf-line--bismillah-row {
  padding: 0 !important;
  border-bottom: 1px solid rgb(71 68 64 / 32%) !important;
  height: auto !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
}

/* Restyle Surah Banner to match plain green box */
.mushaf-surah-banner {
  margin: 2cqw 0 3cqw !important;
  padding: 0 !important;
  border: 0 !important;
  background: transparent !important;
  box-shadow: none !important;
}

.mushaf-surah-banner::before,
.mushaf-surah-banner::after {
  display: none !important;
}

.mushaf-surah-banner__inner,
.mushaf-theme--nabawiyyah .mushaf-surah-banner__inner,
.mushaf-theme--classic .mushaf-surah-banner__inner,
.mushaf-page-box--opening .mushaf-surah-banner__inner {
  width: 100% !important;
  min-height: 0 !important;
  border: 1px solid #c2e2cc !important;
  border-radius: 4px !important;
  padding: 8px 16px !important;
  background: #e6f7ec !important; /* Light green background */
  box-shadow: none !important;
  display: flex !important;
  flex-direction: row !important; /* Force horizontal layout */
  align-items: center !important;
  justify-content: space-between !important;
  direction: ltr !important;
}

.mushaf-surah-banner__inner::before {
  display: none !important;
}

.mushaf-surah-banner__name {
  color: #1a4f32 !important; /* Darker green text */
  font-size: clamp(14px, 6.7cqw, 40px) !important;
  text-shadow: none !important;
  background: white !important;
  padding: 0px 22px !important;
  border-radius: 999px !important;
  border: 1px solid #c2e2cc !important;
  margin: 0 10px !important;
}

.mushaf-surah-banner__name::before,
.mushaf-surah-banner__name::after {
  display: none !important;
}

.mushaf-surah-banner__sub {
  display: block !important;
  flex: 1 !important;
  font-family: 'Inter', sans-serif !important;
  font-size: 14px !important;
  color: #1a4f32 !important;
  white-space: nowrap !important;
  letter-spacing: 0.01em !important;
  text-transform: none !important;
}

.mushaf-surah-banner__sub--left {
  text-align: left !important;
}

.mushaf-surah-banner__sub--right {
  text-align: right !important;
}

/* Remove bottom banner */
.mushaf-bottom-banner {
  display: none !important;
}

/* Simplify footer page number */
.mushaf-page-footer {
  width: 100% !important;
  min-height: 30px !important;
  margin-top: 20px !important;
  border: 0 !important;
  border-radius: 0 !important;
  background: transparent !important;
  box-shadow: none !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  color: #666 !important;
  font-family: 'Inter', sans-serif !important;
  font-size: 14px !important;
}

.mushaf-page-footer::before,
.mushaf-page-footer::after { display: none; }

/* Force remove line borders on plain layout */
.mushaf-page-box:not(.mushaf-page-box--with-frame) .mushaf-line {
  border-bottom: 0 !important;
}

.mushaf-line--qcf {
  gap: 0;
  overflow: visible !important;
  justify-content: center !important;
  font-size: clamp(12px, 5.5cqw, 31.5px) !important;
  line-height: 1.56 !important;
  white-space: nowrap;
  text-rendering: optimizeLegibility;
  -webkit-font-smoothing: antialiased;
}

/* Keep QCF glyphs touching naturally. The inner run is scaled as one piece by
   fitQcfLines(), so it reaches both guides without injecting word gaps. */
.mushaf-line__qcf-content {
  width: max-content;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0;
  white-space: nowrap;
  transform: scaleX(var(--qcf-line-scale, 1));
  transform-origin: center;
}

.mushaf-page-box:not(.mushaf-page-box--opening) .mushaf-line--qcf {
  width: 100% !important;
  padding-right: 0 !important;
  padding-left: 0 !important;
  justify-content: center !important;
}

.mushaf-page-box--opening .mushaf-line--qcf {
  font-size: clamp(13px, 6.55cqw, 34.5px) !important;
  line-height: 1.6 !important;
}
/* Page 604 follows the printed closing-page composition: three compact,
   centered surahs, each with its own banner and basmalah. */
.mushaf-page-box--closing .mushaf-text-frame__inner {
  justify-content: center !important;
  gap: .55cqw !important;
}

.mushaf-page-box--closing .mushaf-surah-banner {
  margin: .8cqw 0 1cqw !important;
}

.mushaf-page-box--closing .mushaf-line--qcf {
  justify-content: center !important;
  line-height: 1.16 !important;
}

.mushaf-bismillah-calligraphy {
  width: 100%;
  min-height: 1.65em;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: .5cqw 0 4.5cqw;
  color: #15110b;
  font-family: 'Amiri', 'Amiri Quran', serif;
  font-size: clamp(12px, 6cqw, 30px);
  font-weight: 400;
  line-height: 1.65;
  text-align: center;
  direction: rtl;
  text-rendering: optimizeLegibility;
}

.mushaf-page-box--multi-surah .mushaf-text-frame__inner {
  justify-content: center !important;
  gap: .45cqw !important;
}

.mushaf-page-box--multi-surah .mushaf-surah-banner {
  padding: 1.8cqw 0 !important;
  margin: .65cqw 0 .8cqw !important;
}

.mushaf-page-box--multi-surah .mushaf-bismillah-calligraphy {
  font-size: clamp(11px, 5cqw, 24px) !important;
  margin-top: .4cqw;
  margin-bottom: 2.5cqw !important;
}

.mushaf-page-box--multi-surah .mushaf-line--qcf {
  font-size: clamp(11px, 5.8cqw, 31px) !important;
  line-height: 1.42 !important;
}

.mushaf-ayah-ornament {
  position: relative;
  width: 1.85em !important; /* Expanded for 3-digit support and breathing room */
  height: 1.85em !important;
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  color: #bd8c30 !important;
  font-family: 'Outfit', 'Inter', sans-serif !important;
  font-size: .88em !important; /* Slightly larger text */
  font-weight: 800 !important;
  line-height: 1 !important;
  vertical-align: middle !important;
  margin-left: 0px !important;
  margin-right: 0px !important;
}

.mushaf-ayah-ornament svg {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
}

/* Premium Traditional Mushaf Rosette Coloring */
.mushaf-ayah-ornament svg rect {
  stroke: #bd8c30 !important; /* Gold/Bronze outer frames */
  stroke-width: 5 !important;
}
.mushaf-ayah-ornament svg circle:nth-of-type(1) {
  stroke: #166d70 !important; /* Teal outline */
  fill: #fffcf5 !important;   /* Cream fill */
  stroke-width: 3.5 !important;
}
.mushaf-ayah-ornament svg circle:nth-of-type(2) {
  stroke: #d34f3b !important; /* Red inner dashed dot ring */
  stroke-width: 1.5 !important;
}

.mushaf-ayah-ornament > span {
  position: relative;
  z-index: 2;
  /* Force number color to dark brown regardless of parent tajweed coloring */
  color: #261607 !important;
  font-size: 0.55em !important; /* Proportional font size to leave elegant padding around numbers */
  font-weight: 800 !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  transform: translateY(0.6px) !important; /* Micro-adjust for perfect vertical alignment inside larger circle */
  line-height: .8 !important;
  width: 100% !important;
  height: 100% !important;
}
.mushaf-ayah-ornament--unicode {
  flex: 0 0 auto !important;
}

/* Extra specificity to prevent any tajweed span color from bleeding into ornament numbers */
.mushaf-word--end .mushaf-ayah-ornament > span,
.mushaf-viewport:not(.mushaf-viewport--monochrome) .mushaf-word--end .mushaf-ayah-ornament > span {
  color: #261607 !important;
}

/* Tajweed Class Color Overrides (Applied when showTajweedColors is ON) */
.mushaf-viewport:not(.mushaf-viewport--monochrome) :deep(.tajweed-q) { color: #20bfe8 !important; } /* Qalqalah: Cyan/Blue */
.mushaf-viewport:not(.mushaf-viewport--monochrome) :deep(.tajweed-g) { color: #169777 !important; } /* Ghunnah: Green */
.mushaf-viewport:not(.mushaf-viewport--monochrome) :deep(.tajweed-i) { color: #c218b6 !important; } /* Ikhfa: Pink/Magenta */
.mushaf-viewport:not(.mushaf-viewport--monochrome) :deep(.tajweed-n) { color: #537fff !important; } /* Idgham: Blue */
.mushaf-viewport:not(.mushaf-viewport--monochrome) :deep(.tajweed-m),
.mushaf-viewport:not(.mushaf-viewport--monochrome) :deep(.tajweed-o),
.mushaf-viewport:not(.mushaf-viewport--monochrome) :deep(.tajweed-p) { color: #dd0008 !important; } /* Madd: Red */
.mushaf-viewport:not(.mushaf-viewport--monochrome) :deep(.tajweed-u),
.mushaf-viewport:not(.mushaf-viewport--monochrome) :deep(.tajweed-a),
.mushaf-viewport:not(.mushaf-viewport--monochrome) :deep(.tajweed-w) { color: #159447 !important; } /* Others */
.mushaf-viewport:not(.mushaf-viewport--monochrome) :deep(.tajweed-h),
.mushaf-viewport:not(.mushaf-viewport--monochrome) :deep(.tajweed-s),
.mushaf-viewport:not(.mushaf-viewport--monochrome) :deep(.tajweed-l) { color: #9a9a9a !important; } /* Silent/wasl */

/* Keep ayah-number ornaments identical even when the attached word/number carries tajweed classes. */
.mushaf-viewport:not(.mushaf-viewport--monochrome) .mushaf-ayah-ornament,
.mushaf-viewport:not(.mushaf-viewport--monochrome) .mushaf-ayah-ornament *,
.mushaf-viewport:not(.mushaf-viewport--monochrome) :deep(.mushaf-ayah-ornament),
.mushaf-viewport:not(.mushaf-viewport--monochrome) :deep(.mushaf-ayah-ornament *) {
  color: #261607 !important;
  -webkit-text-fill-color: #261607 !important;
}

.mushaf-viewport:not(.mushaf-viewport--monochrome) .mushaf-ayah-ornament svg rect,
.mushaf-viewport:not(.mushaf-viewport--monochrome) :deep(.mushaf-ayah-ornament svg rect) {
  stroke: #bd8c30 !important;
}

.mushaf-viewport:not(.mushaf-viewport--monochrome) .mushaf-ayah-ornament svg circle:nth-of-type(1),
.mushaf-viewport:not(.mushaf-viewport--monochrome) :deep(.mushaf-ayah-ornament svg circle:nth-of-type(1)) {
  fill: #fffcf5 !important;
  stroke: #166d70 !important;
}

.mushaf-viewport:not(.mushaf-viewport--monochrome) .mushaf-ayah-ornament svg circle:nth-of-type(2),
.mushaf-viewport:not(.mushaf-viewport--monochrome) :deep(.mushaf-ayah-ornament svg circle:nth-of-type(2)) {
  stroke: #d34f3b !important;
}

/* Monochrome rendering for QCF v2 when Tajweed is toggled off */
.mushaf-viewport--monochrome .mushaf-word:not(.mushaf-word--end),
.mushaf-viewport--monochrome .mushaf-word:not(.mushaf-word--end) * {
  font-palette: none !important;
  color: inherit !important;
}

/* Dark theme style overrides */
.mushaf-theme--dark {
  background: #151d1a !important; /* Deep warm black/green tint */
  color: #edf2f0;
}

.mushaf-theme--dark .mushaf-word--active {
  color: #ffffff !important; /* High contrast white text for selected word in dark mode */
  background: rgba(52, 211, 153, 0.22) !important; /* Semi-transparent mint highlight */
}

.mushaf-theme--dark .mushaf-word--active.mushaf-word--end {
  background: rgba(52, 211, 153, 0.32) !important;
}

.mushaf-word--last-read:not(.mushaf-word--active) {
  background: rgba(212, 175, 55, 0.18);
  box-shadow: 0 0 0 1px rgba(180, 125, 26, 0.16);
  border-radius: 6px;
}

.mushaf-word--last-read.mushaf-word--end .mushaf-ayah-ornament {
  position: relative;
}

.mushaf-word--last-read.mushaf-word--end .mushaf-ayah-ornament::after {
  content: "";
  position: absolute;
  top: -0.3em;
  right: -0.25em;
  width: 0.36em;
  height: 0.52em;
  background: linear-gradient(180deg, #f6d974, #b98520);
  clip-path: polygon(0 0, 100% 0, 100% 100%, 50% 74%, 0 100%);
  filter: drop-shadow(0 1px 2px rgba(91, 56, 10, 0.28));
}

.mushaf-word--bookmarked:not(.mushaf-word--active) {
  background: rgba(10, 107, 79, 0.11);
  box-shadow: 0 0 0 1px rgba(10, 107, 79, 0.12);
  border-radius: 6px;
}

.mushaf-word--bookmarked.mushaf-word--end .mushaf-ayah-ornament {
  position: relative;
}

.mushaf-word--bookmarked.mushaf-word--end .mushaf-ayah-ornament::before {
  content: "";
  position: absolute;
  right: -0.24em;
  bottom: -0.16em;
  width: 0.42em;
  height: 0.42em;
  border-radius: 999px;
  background: #0a6b4f;
  box-shadow: 0 0 0 0.1em #f8f3df;
}

/* Soft pastel Tajweed colors for Dark Mode to avoid eye strain (Anti-Glare) */
.mushaf-theme--dark :deep(.tajweed-q) { color: #5ce6e6 !important; } /* Qalqalah: Soft ice-cyan */
.mushaf-theme--dark :deep(.tajweed-g) { color: #6ee7b7 !important; } /* Ghunnah: Soft mint-green */
.mushaf-theme--dark :deep(.tajweed-i) { color: #f472b6 !important; } /* Ikhfa: Soft pink */
.mushaf-theme--dark :deep(.tajweed-n) { color: #93c5fd !important; } /* Idgham: Soft sky-blue */
.mushaf-theme--dark :deep(.tajweed-m),
.mushaf-theme--dark :deep(.tajweed-o),
.mushaf-theme--dark :deep(.tajweed-p) { color: #f87171 !important; } /* Madd: Soft coral-red */
.mushaf-theme--dark :deep(.tajweed-u),
.mushaf-theme--dark :deep(.tajweed-a),
.mushaf-theme--dark :deep(.tajweed-w) { color: #86efac !important; } /* Others: Soft green */
.mushaf-theme--dark :deep(.tajweed-h),
.mushaf-theme--dark :deep(.tajweed-s),
.mushaf-theme--dark :deep(.tajweed-l) { color: #5a7066 !important; } /* Muted wasl/silent */

.mushaf-theme--dark .mushaf-text-frame {
  border: 4px double #9c7b41 !important;
  box-shadow:
    inset 0 0 0 3px #151d1a,
    inset 0 0 0 4px #9c7b41,
    0 0 0 3px #151d1a,
    0 0 0 4px #9c7b41 !important;
}

.mushaf-theme--dark .mushaf-surah-banner__inner {
  background: #0b2d23 !important; /* Deep dark green background */
  border-color: #0b2d23 !important;
  box-shadow: none !important;
}

.mushaf-theme--dark .mushaf-surah-banner__inner::before {
  border-color: #174f40 !important; /* Lighter dark green internal outline */
}

.mushaf-theme--dark .mushaf-surah-banner__name {
  color: #ffffff !important; /* Pure white surah name calligraphy */
  background-color: #151d1a !important; /* Match the banner background */
}

.mushaf-theme--dark .mushaf-surah-banner__sub {
  color: #ffffff !important; /* Pure white sub-text */
  opacity: 0.95;
}

.mushaf-theme--dark .mushaf-bismillah-calligraphy {
  color: #ffffff !important; /* Pure white Bismillah calligraphy */
}

.mushaf-theme--dark .mushaf-ayah-ornament circle {
  fill: #151d1a !important;
  stroke: #9c7b41 !important;
}

.mushaf-theme--dark .mushaf-ayah-ornament svg rect {
  stroke: #9c7b41 !important;
}

.mushaf-theme--dark .mushaf-ayah-ornament > span {
  color: #decba5 !important;
}

.mushaf-theme--dark .mushaf-meta {
  color: #decba5 !important;
}

.mushaf-theme--dark .mushaf-page-footer {
  color: #8fa099 !important;
}

/* Compact Settings Panel inside Translation Sheet (Fixed at Bottom) */
.translation-sheet-settings {
  flex: 0 0 auto;
  border-top: 1px solid #edf2f0;
  background: #fff;
  padding: 10px 16px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.02);
  position: relative;
  z-index: 10;
}

.mushaf-theme-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  width: 100%;
}

.theme-label-small {
  font-size: 0.76rem;
  font-weight: 700;
  color: #4a5d55;
}

.theme-segmented-control-new {
  display: flex;
  background: #f1f6f4;
  padding: 2px;
  border-radius: 6px;
  border: 1px solid #e5edea;
}

.theme-segment-btn-new {
  flex: 1;
  text-align: center;
  white-space: nowrap;
  border: none;
  background: none;
  padding: 6px 12px;
  font-size: 0.78rem;
  font-weight: 700;
  color: #4a5d55;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.2s ease;
  outline: none;
}

.theme-segment-btn-new--active {
  background: #fff;
  color: #064e3b;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08), 0 1px 2px rgba(0, 0, 0, 0.04);
}

.tss-toggles-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  width: 100%;
}

.tss-toggle {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

.tss-toggle-label {
  font-size: 0.78rem;
  font-weight: 700;
  color: #1e2e28;
}

.tss-info-link-btn {
  background: none;
  border: none;
  padding: 0 0 0 4px;
  font-size: 0.72rem;
  font-weight: 700;
  color: #0a6b4f;
  text-decoration: underline;
  cursor: pointer;
  outline: none;
}

.tss-info-link-btn:hover {
  color: #08553e;
}

/* Header Action Buttons */
.translation-sheet-header__right-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.translation-sheet-settings-toggle {
  background: rgba(0, 0, 0, 0.03);
  border: none;
  padding: 6px;
  color: #718096;
  cursor: pointer;
  border-radius: 50%;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
}

.translation-sheet-settings-toggle:hover {
  background: rgba(0, 0, 0, 0.06);
  color: #0a6b4f;
}

.translation-sheet-settings-toggle--active {
  color: #ffffff !important;
  background: #0a6b4f !important;
}

.settings-cog-icon {
  width: 20px;
  height: 20px;
}

/* Slide Up Tajweed Legend Styles */
.tajweed-legend-row-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  text-align: left;
}

.tajweed-legend-badge {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  margin-top: 5px;
  flex-shrink: 0;
}

.tajweed-legend-info-box {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.tajweed-legend-info-box strong {
  font-size: 0.82rem;
  color: #1e2e28;
}
.translation-bottom-sheet--dark .tajweed-legend-info-box strong {
  color: #edf2f0;
}

.tajweed-legend-info-box p {
  font-size: 0.74rem;
  color: #657b72;
  margin: 0;
  line-height: 1.35;
}

/* Standard Tajweed Color Map */
.tss-color-dot--madd { background: #dd0008; }     /* Red */
.tss-color-dot--ghunnah { background: #169777; }  /* Green */
.tss-color-dot--ikhfa { background: #c218b6; }    /* Pink/Magenta */
.tss-color-dot--idgham { background: #537fff; }   /* Blue */
.tss-color-dot--qalqalah { background: #20bfe8; } /* Cyan/Teal */

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}


@media (max-width: 415px) {
  .mushaf-qcf-content { padding-right: 10px !important; padding-left: 10px !important; }
  .mushaf-text-frame { margin-right: 2px; margin-left: 2px; padding: 4px; }
  .mushaf-text-frame__inner { padding-right: 18px !important; padding-left: 18px !important; }
  .mushaf-line,
  .mushaf-qcf-content--short .mushaf-line {
    width: 100%;
  }

  .mushaf-line--qcf,
  .mushaf-qcf-content--short .mushaf-line--qcf {
    font-size: 6.4cqw !important;
    line-height: 1.52 !important;
    margin: 1.2cqw 0 !important;
  }

  .mushaf-page-box--opening .mushaf-line--qcf {
    font-size: 6.6cqw !important;
  }

  /* Multi-surah pages on narrow mobile: slightly smaller but still fills the frame */
  .mushaf-page-box--multi-surah .mushaf-line--qcf {
    font-size: 5.7cqw !important;
    line-height: 1.42 !important;
    margin: 0 0 1cqw !important;
  }
}

@media (max-width: 620px) {
  .mushaf-qcf-content {
    padding-left: 0 !important;
    padding-right: 0 !important;
  }
  .mushaf-text-frame {
    margin-left: 2px !important;
    margin-right: 2px !important;
    padding-left: 2px !important;
    padding-right: 2px !important;
  }
  .mushaf-text-frame__inner {
    padding-left: 10px !important;
    padding-right: 10px !important;
  }
}

/* End of CSS */
.ayah-options-sheet {
  height: auto;
  min-height: 120px;
  max-height: 80vh;
  background: #fdfbf7 !important; /* Soft warm cream */
  border-top-left-radius: 28px !important;
  border-top-right-radius: 28px !important;
  border-top: 1px solid rgba(156, 122, 60, 0.12) !important;
  box-shadow: 0 -8px 24px rgba(96, 85, 63, 0.08) !important;
  z-index: 1350 !important;
}

.ayah-options-sheet .translation-sheet-header {
  padding: 14px 20px 4px;
}

.ayah-drawer-title {
  font-size: 1.3rem;
  font-weight: 800;
  color: #0f4c46;
  margin: 0;
  line-height: 1.2;
}

.ayah-drawer-subtitle {
  font-size: 0.82rem;
  font-weight: 600;
  color: #9c7a3c;
  margin: 2px 0 0;
}

.ayah-drawer-divider {
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 2px 20px;
}
.ayah-drawer-divider::before,
.ayah-drawer-divider::after {
  content: '';
  flex: 1;
  height: 1px;
  background: linear-gradient(to right, transparent, #ebdcb9, transparent);
}
.ayah-drawer-divider__ornament {
  margin: 0 12px;
  color: #9c7a3c;
  opacity: 0.7;
  display: flex;
  align-items: center;
}

.ayah-options-content {
  padding: 8px 20px 24px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

/* Primary Play Card */
.ayah-primary-card {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  width: 100%;
  padding: 12px 18px;
  border-radius: 14px;
  background: linear-gradient(135deg, #0d5c56 0%, #083c38 100%) !important;
  border: none !important;
  color: #ffffff !important;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(13, 92, 86, 0.18) !important;
  transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
}

.ayah-primary-card:hover {
  transform: translateY(-1.5px);
  box-shadow: 0 6px 16px rgba(13, 92, 86, 0.25) !important;
}

.ayah-primary-card:active {
  transform: translateY(0) scale(0.98);
}

.play-icon-simple {
  width: 14px;
  height: 14px;
  color: #ffffff;
  flex-shrink: 0;
}

.ayah-primary-card__text {
  font-size: 1.02rem;
  font-weight: 700;
}

/* Secondary list actions */
.ayah-list-actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.ayah-list-item {
  display: flex;
  align-items: center;
  width: 100%;
  padding: 10px 14px;
  border-radius: 14px;
  background: #ffffff !important;
  border: 1px solid #f0f0ee !important;
  color: #2b3935;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.01);
  text-align: left;
}

.ayah-list-item:hover {
  border-color: #ebdcb9 !important;
  background: #fffefb !important;
  transform: translateY(-1px);
  box-shadow: 0 3px 8px rgba(96, 85, 63, 0.03);
}

.ayah-list-item:active {
  transform: translateY(0) scale(0.98);
}

.ayah-list-item__icon-wrapper {
  width: 32px;
  height: 32px;
  border-radius: 10px;
  background: #eaf2ed;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 12px;
  flex-shrink: 0;
  color: #0d5c56;
}

.ayah-list-item__icon-wrapper svg {
  width: 16px;
  height: 16px;
  color: #0d5c56 !important;
}

.ayah-list-item__icon-wrapper.Arabic-symbol {
  font-family: 'Amiri', serif;
  font-size: 1.05rem;
  font-weight: 700;
}

.ayah-list-item__label {
  flex: 1;
  font-size: 0.95rem;
  font-weight: 600;
  color: #2b3935;
}

.ayah-list-item__chevron {
  width: 16px;
  height: 16px;
  color: #bdc5c2 !important;
  transition: transform 0.2s ease;
}

.ayah-list-item:hover .ayah-list-item__chevron {
  transform: translateX(1.5px);
  color: #9c7a3c !important;
}

.ayah-options-sheet .translation-sheet-close {
  background: rgba(96, 85, 63, 0.06) !important;
  color: #60553f !important;
  width: 32px;
  height: 32px;
  border-radius: 50% !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  border: none !important;
  cursor: pointer !important;
  transition: all 0.2s ease !important;
}

.ayah-options-sheet .translation-sheet-close:hover {
  background: rgba(96, 85, 63, 0.12) !important;
  color: #0f4c46 !important;
  transform: rotate(90deg) !important;
}

.navigator-overlay {
  position: fixed;
  inset: 0;
  z-index: 1300;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  background: rgba(4, 39, 25, 0.5);
  backdrop-filter: blur(6px);
}

.navigator-sheet {
  position: relative;
  width: 100%;
  max-width: 500px;
  margin: 0 auto;
  background: #FFFDF7;
  border-radius: 24px 24px 0 0;
  overflow: hidden;
  max-height: 92dvh;
  display: flex;
  flex-direction: column;
  user-select: none;
  -webkit-user-select: none;
}

.navigator-sheet--picker-open {
  height: 96dvh;
  max-height: 96dvh;
}

.navigator-sheet__content {
  padding: 16px 20px calc(24px + env(safe-area-inset-bottom));
  display: flex;
  flex-direction: column;
  gap: 20px;
  flex: 1;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
}

.navigator-sheet__handle {
  flex: 0 0 auto;
  display: flex;
  justify-content: center;
  padding: 12px 0;
  cursor: grab;
  touch-action: none;
  user-select: none;
}

.navigator-sheet__handle::before {
  content: '';
  width: 42px;
  height: 4px;
  border-radius: 999px;
  background: rgba(0,0,0,0.15);
}

.navigator-sheet__handle:active { cursor: grabbing; }

.navigator-sheet__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.navigator-sheet__header > div > span {
  display: block;
  font-size: 0.65rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: #087d59;
  margin-bottom: 4px;
}

.navigator-sheet__header h2 {
  font-size: 1.35rem;
  font-weight: 800;
  color: #042719;
  letter-spacing: -0.02em;
  margin: 0 0 3px;
}

.navigator-sheet__header p {
  font-size: 0.78rem;
  color: #6B7280;
  margin: 0;
}

.navigator-sheet__header > button {
  width: 34px;
  height: 34px;
  flex: 0 0 34px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: #F3F4F6;
  color: #4B5563;
  border: none;
  cursor: pointer;
  transition: background 0.2s;
}

.navigator-sheet__header > button:hover { background: #E5E7EB; }

.navigator-sheet__header > button svg { width: 16px; height: 16px; }

.navigator-field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.navigator-field label {
  font-size: 0.72rem;
  font-weight: 700;
  color: #374151;
  letter-spacing: 0.02em;
}

.navigator-field label > span {
  margin-left: 4px;
  color: #9aa29e;
  font-weight: 600;
}

.navigator-field--wide { width: 100%; }

.navigator-field--surah { flex: 1; min-width: 0; }

.navigator-field--ayah { width: 96px; flex-shrink: 0; }

.navigator-selector {
  width: 100%;
  min-height: 56px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  padding: 10px 14px;
  border-radius: 16px;
  border: 1.5px solid rgba(0,0,0,0.07);
  background: #fff;
  cursor: pointer;
  text-align: left;
  transition: border-color 0.2s, box-shadow 0.2s;
  color: #1F2937;
}

.navigator-selector:hover {
  border-color: rgba(8, 125, 89, 0.35);
  box-shadow: 0 2px 8px rgba(8, 125, 89, 0.06);
}

.navigator-selector > span {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
  overflow: hidden;
}

.navigator-selector strong {
  font-size: 0.88rem;
  font-weight: 700;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.navigator-field--ayah .navigator-selector strong {
  overflow: visible;
  text-overflow: clip;
  font-size: 1rem;
  font-weight: 800;
}

.navigator-selector small {
  font-size: 0.7rem;
  color: #6B7280;
  font-weight: 400;
}

.navigator-selector svg {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
  color: #9CA3AF;
}

.navigator-primary {
  width: 100%;
  height: 52px;
  border-radius: 16px;
  border: none;
  background: linear-gradient(135deg, #087d59, #065c41);
  color: #fff;
  font-size: 0.95rem;
  font-weight: 800;
  letter-spacing: 0.01em;
  cursor: pointer;
  transition: opacity 0.2s, transform 0.15s;
  box-shadow: 0 4px 12px rgba(8, 125, 89, 0.3);
}

.navigator-primary:active { transform: scale(0.98); }

.navigator-primary:disabled { opacity: 0.5; cursor: not-allowed; }

.navigator-dynamic { display: flex; flex-direction: column; gap: 14px; }

.navigator-fields-row {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 12px;
  align-items: start;
}

.navigator-select-wrap { position: relative; }

.navigator-select-wrap select {
  width: 100%;
  height: 48px;
  appearance: none;
  border: 1.5px solid rgba(0,0,0,0.07);
  border-radius: 16px;
  outline: none;
  padding: 0 42px 0 14px;
  color: #314039;
  background: #fff;
  font-size: .8rem;
  font-weight: 750;
}

.navigator-select-wrap select:focus { border-color: #159174; box-shadow: 0 0 0 3px rgba(21, 145, 116, .09); }

.navigator-select-wrap svg {
  position: absolute;
  top: 50%;
  right: 14px;
  width: 17px;
  height: 17px;
  color: #168d70;
  pointer-events: none;
  transform: translateY(-50%);
}

.navigator-error { margin: 12px 0 0; color: #c2414d; font-size: .72rem; text-align: center; }

.navigator-sheet .surah-picker {
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
  flex-shrink: 0;
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

.surah-picker__header svg { width: 19px; height: 19px; }

.surah-picker__header > div { display: flex; flex-direction: column; gap: 1px; }

.surah-picker__header span {
  color: #087d59;
  font-size: .61rem;
  font-weight: 800;
  letter-spacing: .08em;
  text-transform: uppercase;
}

.surah-picker__header strong { color: #23332c; font-size: 1.05rem; }

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

.surah-picker__search svg { width: 18px; height: 18px; flex: 0 0 auto; color: #8a9590; }

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

.surah-picker__item--active { border-color: #5aac91; background: #eff8f4; }

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

.surah-picker__item--active .surah-picker__number { color: #087d59; background: #dcefe7; }

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

.surah-picker__names small { color: #8a948f; font-size: .61rem; }

.surah-picker__arabic {
  font-family: var(--font-arabic);
  color: #087d59;
  font-size: 1.02rem;
  direction: rtl;
}

.surah-picker__item > svg { width: 17px; height: 17px; color: #087d59; }

.ayah-picker__summary { margin: 0 0 13px; color: #7e8983; font-size: .68rem; }

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

.ayah-picker__grid button:active { transform: scale(.96); }

.ayah-picker__grid .ayah-picker__number--active {
  border-color: #087d59;
  color: #fff;
  background: #087d59;
  box-shadow: 0 5px 13px rgba(8, 125, 89, .2);
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
.sheet-leave-active .navigator-sheet,
.sheet-enter-active .qari-sheet,
.sheet-leave-active .qari-sheet {
  transition: transform .24s ease;
}

.sheet-enter-from,
.sheet-leave-to { opacity: 0; }

.sheet-enter-from .navigator-sheet,
.sheet-leave-to .navigator-sheet,
.sheet-enter-from .qari-sheet,
.sheet-leave-to .qari-sheet { transform: translateY(100%); }

@media (max-height: 520px) {
  .navigator-sheet,
  .qari-sheet {
    min-height: 0 !important;
    max-height: 95dvh !important;
  }
  .navigator-sheet__content,
  .audio-settings-body {
    flex: 1 !important;
    max-height: none !important;
    overflow-y: auto !important;
  }
  .surah-picker__list,
  .picker-options-list,
  .qari-list {
    max-height: 50vh !important;
    overflow-y: auto !important;
  }
}

@media (orientation: landscape) and (max-height: 600px) {
  .mushaf-content {
    overflow: hidden !important;
  }
  .mushaf-viewport {
    max-width: none !important;
    width: 100% !important;
    height: calc(100dvh - 146px - var(--safe-top) - var(--safe-bottom)) !important;
    box-shadow: none !important;
    border-radius: 0 !important;
    aspect-ratio: auto !important;
  }
  .mushaf-slide {
    display: block !important;
    overflow-y: auto !important;
    overscroll-behavior: contain !important;
    -webkit-overflow-scrolling: touch;
  }
  .mushaf-page-box {
    display: block !important;
    max-width: none !important;
    width: 100% !important;
    height: auto !important;
    min-height: 100% !important;
    border: none !important;
    box-shadow: none !important;
    border-radius: 0 !important;
    aspect-ratio: auto !important;
    container-type: normal !important;
    padding-left: 0 !important;
    padding-right: 0 !important;
  }
  .mushaf-surah-banner__inner {
    width: 100% !important;
  }
  .mushaf-surah-banner__name {
    font-size: clamp(40px, 7vw, 64px) !important;
  }
  .mushaf-surah-banner__sub {
    font-size: clamp(16px, 2.5vw, 24px) !important;
  }
  .mushaf-qcf-content {
    display: block !important;
    height: auto !important;
    overflow: visible !important;
    padding-left: calc(18px + env(safe-area-inset-left)) !important;
    padding-right: calc(18px + env(safe-area-inset-right)) !important;
  }
  .mushaf-text-frame {
    display: block !important;
    height: auto !important;
    overflow: visible !important;
    margin-left: 4px !important;
    margin-right: 4px !important;
    padding-left: 4px !important;
    padding-right: 4px !important;
  }
  .mushaf-text-frame__inner {
    display: block !important;
    height: auto !important;
    overflow: visible !important;
    padding-left: calc(10px + env(safe-area-inset-left)) !important;
    padding-right: calc(10px + env(safe-area-inset-right)) !important;
  }
  /* Force typography to scale fully with screen width in landscape.
     Using vw instead of cqw for Safari iOS compatibility */
  .mushaf-line,
  .mushaf-line--qcf,
  .mushaf-qcf-content--short .mushaf-line,
  .mushaf-qcf-content--short .mushaf-line--qcf {
    font-size: 5.6vw !important;
    width: 100% !important;
  }

  /* Scale up digital frame padding and margins in landscape */
  .mushaf-page-box--with-frame {
    padding: 24px 30px 24px !important;
  }
  .mushaf-page-box--with-frame .mushaf-frame {
    inset: 64px 18px 18px !important;
  }
  /* Scale up corner medallions */
  .mushaf-page-box--with-frame .mushaf-frame__corner {
    width: 32px !important;
    height: 32px !important;
  }
  .mushaf-page-box--with-frame .mushaf-frame__corner--tl { top: -17px !important; left: -17px !important; }
  .mushaf-page-box--with-frame .mushaf-frame__corner--tr { top: -17px !important; right: -17px !important; }
  .mushaf-page-box--with-frame .mushaf-frame__corner--bl { bottom: -17px !important; left: -17px !important; }
  .mushaf-page-box--with-frame .mushaf-frame__corner--br { right: -17px !important; bottom: -17px !important; }

  /* Scale up frame border lines thickness */
  .mushaf-page-box--with-frame .mushaf-frame__border::before {
    border-width: 2px !important;
    box-shadow: 
      0 0 0 2px rgb(22, 109, 112), 
      0 0 0 5px rgb(255, 247, 207), 
      0 0 0 8px rgb(189, 140, 48), 
      0 0 0 11px rgb(248, 233, 168), 
      0 0 0 13px rgb(22, 109, 112) !important;
  }
  .mushaf-page-box--with-frame .mushaf-frame__border::after {
    background-size: 58px 16px, 58px 16px, 16px 58px, 16px 58px !important;
  }
  .mushaf-page-box--left-page .mushaf-frame__border::before {
    inset: -12px 0px -12px -12px !important;
  }
  .mushaf-page-box--left-page .mushaf-frame__border::after {
    inset: -11px 0px -11px -11px !important;
  }
  .mushaf-page-box--right-page .mushaf-frame__border::before {
    inset: -12px -12px -12px 0px !important;
  }
  .mushaf-page-box--right-page .mushaf-frame__border::after {
    inset: -11px -11px -11px 0px !important;
  }

  /* Scale up meta header */
  .mushaf-page-box--with-frame .mushaf-meta {
    top: 29px !important;
    left: 48px !important;
    right: 48px !important;
    height: 40px !important;
  }
  .mushaf-page-box--with-frame .mushaf-meta > span {
    min-height: 28px !important;
    font-size: 0.85rem !important;
    padding: 0 16px !important;
    border-width: 1.5px !important;
  }
  .mushaf-page-box--with-frame .mushaf-meta__page {
    width: 60px !important;
    flex: 0 0 60px !important;
  }

  /* Scale up Surah Banner */
  .mushaf-page-box--with-frame .mushaf-surah-banner__inner,
  .mushaf-page-box--with-frame.mushaf-theme--nabawiyyah .mushaf-surah-banner__inner,
  .mushaf-page-box--with-frame.mushaf-theme--classic .mushaf-surah-banner__inner,
  .mushaf-page-box--with-frame.mushaf-theme--dark .mushaf-surah-banner__inner {
    min-height: 48px !important;
    max-height: 54px !important;
    padding: 6px 12px !important;
    gap: 4px !important;
  }
  .mushaf-page-box--with-frame .mushaf-surah-banner__name,
  .mushaf-page-box--with-frame.mushaf-theme--nabawiyyah .mushaf-surah-banner__name,
  .mushaf-page-box--with-frame.mushaf-theme--classic .mushaf-surah-banner__name,
  .mushaf-page-box--with-frame.mushaf-theme--dark .mushaf-surah-banner__name {
    height: 38px !important;
    min-height: 38px !important;
    font-size: 50px !important;
    padding: 0 80px 0px !important; /* Bottom padding to shift Arabic text up */
    box-shadow: 0 0 0 2px rgb(22, 109, 112) !important;
  }
  .mushaf-page-box--with-frame .mushaf-surah-banner__sub,
  .mushaf-page-box--with-frame.mushaf-theme--nabawiyyah .mushaf-surah-banner__sub,
  .mushaf-page-box--with-frame.mushaf-theme--classic .mushaf-surah-banner__sub,
  .mushaf-page-box--with-frame.mushaf-theme--dark .mushaf-surah-banner__sub {
    height: 38px !important;
    min-height: 38px !important;
    font-size: 18px !important;
    min-width: 72px !important;
    line-height: 38px !important;
    box-shadow: 0 0 0 2px rgb(22, 109, 112) !important;
  }

  /* Scale up Bismillah */
  .mushaf-page-box--with-frame .mushaf-bismillah-calligraphy {
    font-size: clamp(22px, 4.8vw, 36px) !important;
  }

  /* Adjust content padding to fit expanded frame.
     Use fixed symmetric padding so text is aligned equally on both sides
     inside the frame borders, regardless of notch/safe-area position.
     Safe-area insets are applied at the viewport level, not inside the page frame. */
  .mushaf-page-box--with-frame .mushaf-qcf-content {
    padding-top: 52px !important;
    padding-bottom: 0px !important;
    padding-left: 20px !important;
    padding-right: 20px !important;
  }
  /* Banner and Bismillah rows: ensure they stretch to fill the frame width */
  .mushaf-page-box--with-frame .mushaf-line--banner-row,
  .mushaf-page-box--with-frame .mushaf-line--bismillah-row {
    width: 100% !important;
    font-size: 5.6vw !important;
  }
  /* Ensure ALL lines (text, banner, bismillah) use the same landscape font-size */
  .mushaf-page-box--with-frame .mushaf-line {
    font-size: 5.6vw !important;
    width: 100% !important;
  }
}

/* Madinah frame polish: consistent spread height, bismillah baseline, banner typography */
.mushaf-page-box--with-frame {
  aspect-ratio: 1 / 1.9 !important;
  height: auto !important;
  min-height: calc((100cqw - 20px) * 1.9) !important;
  max-height: none !important;
}

.mushaf-page-box--with-frame .mushaf-qcf-content {
  flex: 1 1 auto !important;
  height: auto !important;
  min-height: 0 !important;
  padding-top: 13.2cqw !important;
  padding-bottom: 3.6cqw !important;
}

.mushaf-page-box--with-frame .mushaf-line--bismillah-row {
  min-height: 7.9cqw !important;
  padding-top: 12px !important;
  padding-bottom: 0.55cqw !important;
  align-items: center !important;
}

.mushaf-page-box--with-frame .mushaf-bismillah-calligraphy {
  min-height: 0 !important;
  margin: 0 !important;
  font-size: clamp(15px, 4.35cqw, 31px) !important;
  line-height: 1.05 !important;
  transform: translateY(-3.85cqw) !important;
}

.mushaf-page-box--with-frame .mushaf-surah-banner__inner,
.mushaf-page-box--with-frame.mushaf-theme--nabawiyyah .mushaf-surah-banner__inner,
.mushaf-page-box--with-frame.mushaf-theme--classic .mushaf-surah-banner__inner,
.mushaf-page-box--with-frame.mushaf-theme--dark .mushaf-surah-banner__inner {
  min-height: 7.9cqw !important;
  max-height: 8.6cqw !important;
  padding: 0.85cqw 1.45cqw !important;
  gap: 0.75cqw !important;
}

.mushaf-page-box--with-frame .mushaf-surah-banner__name,
.mushaf-page-box--with-frame.mushaf-theme--nabawiyyah .mushaf-surah-banner__name,
.mushaf-page-box--with-frame.mushaf-theme--classic .mushaf-surah-banner__name,
.mushaf-page-box--with-frame.mushaf-theme--dark .mushaf-surah-banner__name {
  height: 5.8cqw !important;
  min-height: 5.8cqw !important;
  padding: 0 3.4cqw !important;
  font-size: clamp(24px, 5.35cqw, 42px) !important;
  line-height: 1 !important;
}

.mushaf-page-box--with-frame .mushaf-surah-banner__sub,
.mushaf-page-box--with-frame.mushaf-theme--nabawiyyah .mushaf-surah-banner__sub,
.mushaf-page-box--with-frame.mushaf-theme--classic .mushaf-surah-banner__sub,
.mushaf-page-box--with-frame.mushaf-theme--dark .mushaf-surah-banner__sub {
  height: 5.8cqw !important;
  min-height: 5.8cqw !important;
  min-width: 15.5cqw !important;
  max-width: 20cqw !important;
  padding: 0 2.1cqw !important;
  font-size: clamp(11px, 2.35cqw, 18px) !important;
  line-height: 5.8cqw !important;
}

@media (orientation: landscape) and (max-height: 600px) {
  .mushaf-page-box--with-frame {
    aspect-ratio: 1 / 1.64 !important;
    height: auto !important;
    min-height: calc((100cqw - 20px) * 1.64) !important;
    max-height: none !important;
  }

  .mushaf-page-box--with-frame .mushaf-qcf-content {
    display: flex !important;
    height: auto !important;
    min-height: 0 !important;
    padding-top: 58px !important;
    padding-bottom: 32px !important;
    padding-left: 20px !important;
    padding-right: 20px !important;
  }

  .mushaf-page-box--with-frame .mushaf-line--bismillah-row {
    min-height: 42px !important;
    padding-top: 2px !important;
    padding-bottom: 1px !important;
    align-items: flex-end !important;
  }

  .mushaf-page-box--with-frame .mushaf-bismillah-calligraphy {
    font-size: clamp(22px, 4vw, 34px) !important;
    line-height: 1.05 !important;
    transform: translateY(-25px) !important;
  }

  .mushaf-page-box--with-frame .mushaf-surah-banner__inner,
  .mushaf-page-box--with-frame.mushaf-theme--nabawiyyah .mushaf-surah-banner__inner,
  .mushaf-page-box--with-frame.mushaf-theme--classic .mushaf-surah-banner__inner,
  .mushaf-page-box--with-frame.mushaf-theme--dark .mushaf-surah-banner__inner {
    min-height: 42px !important;
    max-height: 46px !important;
    padding: 5px 10px !important;
    gap: 8px !important;
  }

  .mushaf-page-box--with-frame .mushaf-surah-banner__name,
  .mushaf-page-box--with-frame.mushaf-theme--nabawiyyah .mushaf-surah-banner__name,
  .mushaf-page-box--with-frame.mushaf-theme--classic .mushaf-surah-banner__name,
  .mushaf-page-box--with-frame.mushaf-theme--dark .mushaf-surah-banner__name {
    height: 32px !important;
    min-height: 32px !important;
    padding: 0 clamp(28px, 7vw, 76px) !important;
    font-size: clamp(30px, 5.1vw, 44px) !important;
    line-height: 1 !important;
  }

  .mushaf-page-box--with-frame .mushaf-surah-banner__sub,
  .mushaf-page-box--with-frame.mushaf-theme--nabawiyyah .mushaf-surah-banner__sub,
  .mushaf-page-box--with-frame.mushaf-theme--classic .mushaf-surah-banner__sub,
  .mushaf-page-box--with-frame.mushaf-theme--dark .mushaf-surah-banner__sub {
    height: 32px !important;
    min-height: 32px !important;
    min-width: clamp(64px, 13vw, 112px) !important;
    max-width: clamp(82px, 13vw, 150px) !important;
    padding: 0 12px !important;
    font-size: clamp(13px, 2vw, 22px) !important;
    line-height: 32px !important;
  }
}

/* Bottom surah banner spacing: keep every page the same height,
   only trim the white gap below the banner. */
.mushaf-page-box--with-frame.mushaf-page-box--bottom-surah-banner .mushaf-qcf-content {
  padding-bottom: 2.2cqw !important;
}

@media (orientation: landscape) and (max-height: 600px) {
  .mushaf-page-box--with-frame.mushaf-page-box--bottom-surah-banner .mushaf-qcf-content {
    padding-bottom: 26px !important;
  }
}

/* Opening spread (Al-Fatihah + first 5 ayat Al-Baqarah): dedicated Madinah-style frame. */
.mushaf-page-box--with-frame.mushaf-page-box--opening {
  aspect-ratio: 1 / 1.76 !important;
  min-height: calc((100cqw - 20px) * 1.76) !important;
  padding: 8px 6px 12px !important;
  background:
    radial-gradient(circle at 50% 14%, rgba(242, 200, 96, .22), transparent 18%),
    linear-gradient(180deg, #fffdf4 0%, #fbf3db 100%) !important;
}

.mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-frame {
  inset: 11.6cqw 3.2cqw 3.2cqw !important;
}

.mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-frame__border::before {
  inset: -6px !important;
  box-shadow:
    0 0 0 1px rgb(22, 109, 112),
    0 0 0 3px rgb(255, 247, 207),
    0 0 0 5px rgb(189, 140, 48),
    0 0 0 7px rgb(248, 233, 168),
    0 0 0 8px rgb(22, 109, 112) !important;
}

.mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-frame__corner {
  width: 5.2cqw !important;
  height: 5.2cqw !important;
}

.mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-frame__corner--tl { top: -2.7cqw !important; left: -2.7cqw !important; }
.mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-frame__corner--tr { top: -2.7cqw !important; right: -2.7cqw !important; }
.mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-frame__corner--bl { bottom: -2.7cqw !important; left: -2.7cqw !important; }
.mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-frame__corner--br { right: -2.7cqw !important; bottom: -2.7cqw !important; }

.mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-qcf-content {
  flex: 1 1 auto !important;
  justify-content: center !important;
  gap: 0.55cqw !important;
  margin: 0 !important;
  padding: 18.2cqw 8.2cqw 9.2cqw !important;
  border: 3px solid #0d7881 !important;
  outline: 4px solid #f4d56e !important;
  outline-offset: -7px !important;
  box-shadow:
    inset 0 0 0 2px #bd8c30,
    inset 0 0 0 7px #fff6cf,
    inset 0 0 0 10px #0d7881,
    inset 0 0 0 13px #f5d676 !important;
  background:
    radial-gradient(circle at 13px 13px, #d34f3b 0 2px, transparent 2.6px) 0 0 / 26px 26px,
    radial-gradient(circle at 13px 13px, #f2c860 0 2px, transparent 2.7px) 13px 13px / 26px 26px,
    linear-gradient(#fffefa, #fffefa) center / calc(100% - 13.5cqw) calc(100% - 24cqw) no-repeat,
    linear-gradient(180deg, #0c7983 0%, #0f8790 24%, #f8e7a2 24%, #f8e7a2 76%, #0f8790 76%, #0c7983 100%) !important;
  overflow: hidden !important;
}

.mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-qcf-content::before,
.mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-qcf-content::after {
  content: '' !important;
  position: absolute !important;
  left: 9.5cqw !important;
  right: 9.5cqw !important;
  z-index: 1 !important;
  height: 5.8cqw !important;
  border: 2px solid #bd8c30 !important;
  border-radius: 999px !important;
  background:
    radial-gradient(circle, #d34f3b 0 2px, transparent 2.8px) 0 50% / 22px 12px repeat-x,
    linear-gradient(180deg, #fffbe6, #f4e0a1) !important;
  box-shadow: 0 0 0 2px #0d7881, 0 0 0 5px #fff7cf !important;
  pointer-events: none !important;
}

.mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-qcf-content::before { top: 14.2cqw !important; }
.mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-qcf-content::after { bottom: 4.6cqw !important; }

.mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-line,
.mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-opening-title,
.mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-surah-banner {
  position: relative !important;
  z-index: 3 !important;
}

.mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-opening-title,
.mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-surah-banner__name {
  display: inline-flex !important;
  align-self: center !important;
  justify-content: center !important;
  align-items: center !important;
  min-width: 43cqw !important;
  height: 7.2cqw !important;
  margin: 0 auto 3.2cqw !important;
  padding: 0 6cqw !important;
  border: 1px solid #bd8c30 !important;
  border-radius: 999px !important;
  background: linear-gradient(180deg, #fffef0, #f3dfa2) !important;
  box-shadow: 0 0 0 2px #0d7881, 0 0 0 6px #fff7cf !important;
  color: #111 !important;
  font-family: 'QCF Surah Name V2', sans-serif !important;
  font-size: clamp(24px, 5.4cqw, 44px) !important;
  line-height: 1 !important;
}

.mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-surah-banner {
  margin: 0 0 3.2cqw !important;
  width: 100% !important;
  background: transparent !important;
}

.mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-surah-banner__inner {
  width: 100% !important;
  min-height: 0 !important;
  max-height: none !important;
  padding: 0 !important;
  border: 0 !important;
  background: transparent !important;
  box-shadow: none !important;
  justify-content: center !important;
}

.mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-surah-banner__sub {
  display: none !important;
}

.mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-line--qcf,
.mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-line--unicode {
  width: 100% !important;
  min-height: 6.9cqw !important;
  padding: 0.25cqw 0 !important;
  border-bottom: 1px solid rgba(71, 68, 64, 0.38) !important;
  font-size: 5.55cqw !important;
  line-height: 1.18 !important;
}

.mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-line--bismillah-row {
  min-height: 7.8cqw !important;
  margin-bottom: 0.4cqw !important;
}

.mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-bismillah-calligraphy {
  transform: translateY(-0.8cqw) !important;
  font-size: clamp(20px, 4.6cqw, 34px) !important;
}

@media (orientation: landscape) and (max-height: 600px) {
  .mushaf-page-box--with-frame.mushaf-page-box--opening {
    aspect-ratio: 1 / 1.58 !important;
    min-height: calc((100cqw - 20px) * 1.58) !important;
  }

  .mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-qcf-content {
    padding: 82px 42px 42px !important;
  }

  .mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-qcf-content::before { top: 58px !important; height: 34px !important; }
  .mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-qcf-content::after { bottom: 18px !important; height: 34px !important; }

  .mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-opening-title,
  .mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-surah-banner__name {
    height: 38px !important;
    min-width: 220px !important;
    margin-bottom: 18px !important;
    font-size: clamp(30px, 5vw, 44px) !important;
  }
}

/* Opening spread refinement: keep Arabic area clean, ornament only in the frame bands. */
.mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-qcf-content {
  padding: 17.8cqw 8.8cqw 8.8cqw !important;
  border: 3px solid #08737c !important;
  outline: 3px solid #f3d36f !important;
  outline-offset: -6px !important;
  box-shadow:
    inset 0 0 0 2px #bd8c30,
    inset 0 0 0 6px #fff6cf,
    inset 0 0 0 9px #0d7881,
    inset 0 0 0 12px #f5d676 !important;
  background:
    linear-gradient(#fffefa, #fffefa) center / calc(100% - 13.8cqw) calc(100% - 24.5cqw) no-repeat,
    radial-gradient(circle at 50% 50%, #d34f3b 0 1.7px, transparent 2.4px) 0 0 / 28px 14px repeat-x,
    radial-gradient(circle at 50% 50%, #f2c860 0 1.7px, transparent 2.4px) 14px 0 / 28px 14px repeat-x,
    linear-gradient(180deg,
      #08737c 0 11%,
      #f8e7a2 11% 17%,
      #fffefa 17% 83%,
      #f8e7a2 83% 89%,
      #08737c 89% 100%) !important;
}

.mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-qcf-content::before,
.mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-qcf-content::after {
  left: 11cqw !important;
  right: 11cqw !important;
  height: 4.7cqw !important;
  border: 1px solid #bd8c30 !important;
  background:
    linear-gradient(90deg, transparent 0 8%, rgba(211, 79, 59, .95) 8% 9.2%, transparent 9.2% 18%) 0 50% / 38px 100% repeat-x,
    linear-gradient(180deg, #fffced, #f5e2a5) !important;
  box-shadow: 0 0 0 2px #0d7881, 0 0 0 4px #fff7cf !important;
}

.mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-qcf-content::before { top: 14.2cqw !important; }
.mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-qcf-content::after { bottom: 4.8cqw !important; }

.mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-opening-title,
.mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-surah-banner__name {
  height: 6.7cqw !important;
  min-width: 40cqw !important;
  margin-bottom: 3cqw !important;
  background: linear-gradient(180deg, #fffef2, #f4e1a6) !important;
  box-shadow: 0 0 0 2px #0d7881, 0 0 0 5px #fff7cf !important;
}

.mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-line--qcf,
.mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-line--unicode {
  background: transparent !important;
  min-height: 6.8cqw !important;
}

@media (orientation: landscape) and (max-height: 600px) {
  .mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-qcf-content {
    padding: 78px 44px 40px !important;
  }

  .mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-qcf-content::before { top: 56px !important; height: 28px !important; }
  .mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-qcf-content::after { bottom: 18px !important; height: 28px !important; }
}

/* Opening spread final polish: premium illumination, clean reading panel. */
.mushaf-page-box--with-frame.mushaf-page-box--opening {
  aspect-ratio: 1 / 1.76 !important;
  min-height: calc((100cqw - 20px) * 1.76) !important;
  padding: 7px 6px 12px !important;
  background:
    radial-gradient(ellipse at 50% 6%, rgba(250, 229, 143, .32), transparent 28%),
    linear-gradient(180deg, #fffdf2 0%, #fbf1d2 100%) !important;
}

.mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-frame {
  inset: 10.5cqw 2.9cqw 2.9cqw !important;
}

.mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-frame__border::before {
  inset: -7px !important;
  background:
    radial-gradient(circle at 50% 50%, #f8df78 0 2px, transparent 2.8px) 0 0 / 14px 14px,
    linear-gradient(90deg, #0a7881, #096d75) !important;
  box-shadow:
    0 0 0 1px #096d75,
    0 0 0 3px #fff8d6,
    0 0 0 5px #c39130,
    0 0 0 8px #f5db75,
    0 0 0 10px #0a7881 !important;
}

.mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-frame__corner {
  width: 5.4cqw !important;
  height: 5.4cqw !important;
}

.mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-qcf-content {
  justify-content: flex-start !important;
  gap: 0.36cqw !important;
  padding: 16.8cqw 8.2cqw 8.4cqw !important;
  border: 2px solid #076f78 !important;
  outline: 2px solid #f3d26b !important;
  outline-offset: -5px !important;
  box-shadow:
    inset 0 0 0 2px #c18e2f,
    inset 0 0 0 7px #fff6cf,
    inset 0 0 0 11px #08737c,
    inset 0 0 0 15px #f4d76f,
    inset 0 0 0 19px #08737c !important;
  background:
    linear-gradient(#fffefa, #fffefa) center / calc(100% - 15.6cqw) calc(100% - 27.2cqw) no-repeat,
    radial-gradient(ellipse at center, #f5d86d 0 19%, transparent 20%) 50% 4.25cqw / 4.6cqw 2.5cqw repeat-x,
    radial-gradient(ellipse at center, #e85c3d 0 14%, transparent 15%) 50% 5.55cqw / 4.6cqw 2.5cqw repeat-x,
    radial-gradient(circle at center, #fff7d2 0 1.4px, transparent 2px) 50% 3.25cqw / 2.8cqw 2.8cqw repeat-x,
    radial-gradient(ellipse at center, #f5d86d 0 19%, transparent 20%) 50% calc(100% - 4.25cqw) / 4.6cqw 2.5cqw repeat-x,
    radial-gradient(ellipse at center, #e85c3d 0 14%, transparent 15%) 50% calc(100% - 5.55cqw) / 4.6cqw 2.5cqw repeat-x,
    radial-gradient(circle at center, #fff7d2 0 1.4px, transparent 2px) 50% calc(100% - 3.25cqw) / 2.8cqw 2.8cqw repeat-x,
    radial-gradient(ellipse at center, #f5d86d 0 19%, transparent 20%) 4.25cqw 50% / 2.5cqw 4.6cqw repeat-y,
    radial-gradient(ellipse at center, #e85c3d 0 14%, transparent 15%) 5.55cqw 50% / 2.5cqw 4.6cqw repeat-y,
    radial-gradient(ellipse at center, #f5d86d 0 19%, transparent 20%) calc(100% - 4.25cqw) 50% / 2.5cqw 4.6cqw repeat-y,
    radial-gradient(ellipse at center, #e85c3d 0 14%, transparent 15%) calc(100% - 5.55cqw) 50% / 2.5cqw 4.6cqw repeat-y,
    linear-gradient(180deg,
      #08737c 0 13.5%,
      #f8e7a5 13.5% 18%,
      #fffefa 18% 82%,
      #f8e7a5 82% 86.5%,
      #08737c 86.5% 100%) !important;
  overflow: hidden !important;
}

.mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-qcf-content::before,
.mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-qcf-content::after {
  left: 9.3cqw !important;
  right: 9.3cqw !important;
  height: 5.5cqw !important;
  border: 2px solid #c18e2f !important;
  border-radius: 999px !important;
  background:
    radial-gradient(ellipse at 50% 50%, #08737c 0 16%, transparent 17%) 0 50% / 3.8cqw 2.1cqw repeat-x,
    radial-gradient(ellipse at 50% 50%, #e85c3d 0 13%, transparent 14%) 1.9cqw 50% / 3.8cqw 2.1cqw repeat-x,
    linear-gradient(180deg, #fffdf0, #f4df9e) !important;
  box-shadow:
    0 0 0 2px #08737c,
    0 0 0 5px #fff7cf,
    0 0 0 6px #c18e2f !important;
}

.mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-qcf-content::before {
  top: 12.15cqw !important;
}

.mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-qcf-content::after {
  bottom: 3.85cqw !important;
}

.mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-opening-title,
.mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-surah-banner__name {
  height: 6.9cqw !important;
  min-width: 42cqw !important;
  margin: 0 auto 3.3cqw !important;
  border: 2px solid #c18e2f !important;
  background:
    radial-gradient(ellipse at 50% -35%, rgba(255, 255, 255, .92), transparent 58%),
    linear-gradient(180deg, #fffdf1, #f0d996) !important;
  box-shadow:
    0 0 0 2px #08737c,
    0 0 0 5px #fff7cf,
    0 3px 0 rgba(117, 79, 20, .16) !important;
  font-size: clamp(27px, 5.9cqw, 46px) !important;
}

.mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-surah-banner {
  margin-bottom: 3.1cqw !important;
}

.mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-line--qcf,
.mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-line--unicode {
  min-height: 6.75cqw !important;
  padding: 0.2cqw 0 !important;
  border-bottom: 1px solid rgba(48, 45, 38, .34) !important;
}

.mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-line--bismillah-row {
  min-height: 7.5cqw !important;
}

@media (orientation: landscape) and (max-height: 600px) {
  .mushaf-page-box--with-frame.mushaf-page-box--opening {
    aspect-ratio: 1 / 1.58 !important;
    min-height: calc((100cqw - 20px) * 1.58) !important;
  }

  .mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-qcf-content {
    padding: 72px 40px 38px !important;
  }

  .mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-qcf-content::before {
    top: 48px !important;
    height: 30px !important;
  }

  .mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-qcf-content::after {
    bottom: 17px !important;
    height: 30px !important;
  }

  .mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-opening-title,
  .mushaf-page-box--with-frame.mushaf-page-box--opening .mushaf-surah-banner__name {
    height: 36px !important;
    min-width: 218px !important;
    margin-bottom: 16px !important;
    font-size: clamp(30px, 5vw, 44px) !important;
  }
}
.mushaf-page--fullscreen .mushaf-viewport {
  height: calc(100dvh - var(--safe-top) - var(--safe-bottom) - 24px) !important;
}

/* ===== Dark Mode Polish Overrides ===== */
.mushaf-viewport--monochrome .mushaf-theme--dark .mushaf-word:not(.mushaf-word--end),
.mushaf-viewport--monochrome .mushaf-theme--dark .mushaf-word:not(.mushaf-word--end) *,
.mushaf-viewport--monochrome .mushaf-theme--dark :deep([class*="tajweed-"]) {
  color: #f4f7f5 !important;
  -webkit-text-fill-color: #f4f7f5 !important;
}

.mushaf-viewport--monochrome .mushaf-theme--dark .mushaf-word--end,
.mushaf-viewport--monochrome .mushaf-theme--dark .mushaf-word--end * {
  color: #c9ad70 !important;
  -webkit-text-fill-color: #c9ad70 !important;
}

.mushaf-theme--dark .mushaf-line--qcf,
.mushaf-theme--dark .mushaf-line__qcf-content {
  color: #f4f7f5;
}

.mushaf-theme--dark .mushaf-word--fluent,
.mushaf-theme--dark .mushaf-word--doubtful,
.mushaf-theme--dark .mushaf-word--forgot {
  color: inherit;
}

.mushaf-theme--nabawiyyah .mushaf-surah-banner__inner,
.mushaf-page--nabawiyyah .mushaf-surah-banner__inner {
  background: linear-gradient(180deg, #7a4b1c 0%, #5c3514 100%) !important;
  border-color: #d1a454 !important;
  color: #fff4d7 !important;
  box-shadow:
    inset 0 0 0 1px rgba(255, 237, 179, 0.32),
    0 4px 10px rgba(87, 55, 20, 0.16) !important;
}

.mushaf-theme--nabawiyyah .mushaf-surah-banner__inner::before,
.mushaf-page--nabawiyyah .mushaf-surah-banner__inner::before {
  border-color: rgba(255, 232, 166, 0.48) !important;
}


.mushaf-page--nabawiyyah .mushaf-surah-banner__name,
.mushaf-theme--nabawiyyah .mushaf-surah-banner__sub,
.mushaf-page--nabawiyyah .mushaf-surah-banner__sub {
  color: #fff4d7 !important;
}

.mushaf-theme--nabawiyyah .mushaf-surah-banner__name{
color: #75471a !important;
}

.mushaf-page--dark .mushaf-player {
  background: rgba(13, 24, 20, 0.96) !important;
  border-top-color: rgba(209, 164, 84, 0.2) !important;
  box-shadow: 0 -12px 34px rgba(0, 0, 0, 0.42) !important;
  backdrop-filter: blur(18px);
}

.mushaf-page--dark .mushaf-player__btn {
  color: #a8b7b1 !important;
  background: rgba(255, 255, 255, 0.04) !important;
}

.mushaf-page--dark .mushaf-player__btn:hover,
.mushaf-page--dark .mushaf-player__btn:active,
.mushaf-page--dark .mushaf-player__btn--active {
  color: #f4d58a !important;
  background: rgba(244, 213, 138, 0.12) !important;
}

.mushaf-page--dark .mushaf-player__play {
  color: #06231a !important;
  background: linear-gradient(135deg, #f4d58a, #c79b42) !important;
  box-shadow: 0 8px 20px rgba(199, 155, 66, 0.22) !important;
}

.mushaf-page--dark .mushaf-player__qari-select,
.mushaf-page--dark .mushaf-player__qari-select .mushaf-player__info {
  color: #eef5f1 !important;
}

.mushaf-page--dark .mushaf-player__info strong {
  color: #f5f7f5 !important;
}

.mushaf-page--dark .mushaf-player__info small {
  color: #9fb0aa !important;
}

.mushaf-page--dark .mushaf-player__info > span {
  background: rgba(255, 255, 255, 0.12) !important;
}

.mushaf-page--dark .mushaf-player__info > span i {
  background: #f4d58a !important;
}

.mushaf-page--dark .translation-bottom-sheet,
.mushaf-page--dark .ayah-options-sheet,
.mushaf-page--dark .navigator-sheet,
.mushaf-page--dark .qari-sheet {
  background: #111c17 !important;
  border-color: rgba(209, 164, 84, 0.16) !important;
  color: #eff5f2 !important;
  box-shadow: 0 -14px 36px rgba(0, 0, 0, 0.48) !important;
}

.mushaf-page--dark .translation-sheet-header,
.mushaf-page--dark .qari-sheet__header,
.mushaf-page--dark .navigator-sheet__header {
  border-bottom-color: rgba(255, 255, 255, 0.08) !important;
}

.mushaf-page--dark .translation-sheet-header h2,
.mushaf-page--dark .qari-sheet__header h2,
.mushaf-page--dark .navigator-sheet__title,
.mushaf-page--dark .ayah-drawer-title,
.mushaf-page--dark .surah-picker__header strong {
  color: #f5f7f5 !important;
}

.mushaf-page--dark .translation-sheet-header p,
.mushaf-page--dark .qari-sheet__header p,
.mushaf-page--dark .ayah-drawer-subtitle,
.mushaf-page--dark .ayah-picker__summary,
.mushaf-page--dark .surah-picker__names small {
  color: #9fb0aa !important;
}

.mushaf-page--dark .translation-sheet-close,
.mushaf-page--dark .qari-sheet__header > button,
.mushaf-page--dark .surah-picker__header > button,
.mushaf-page--dark .ayah-options-sheet .translation-sheet-close {
  color: #d6c18b !important;
  background: rgba(255, 255, 255, 0.07) !important;
}

.mushaf-page--dark .translation-sheet-settings {
  background: #0d1713 !important;
  border-top-color: rgba(255, 255, 255, 0.08) !important;
  box-shadow: 0 -8px 22px rgba(0, 0, 0, 0.28) !important;
}

.mushaf-page--dark .theme-label-small,
.mushaf-page--dark .tss-toggle-label,
.mushaf-page--dark .tss-font-size-label,
.mushaf-page--dark .translation-text,
.mushaf-page--dark .translation-item,
.mushaf-page--dark .ayah-list-item__label,
.mushaf-page--dark .navigator-field label,
.mushaf-page--dark .surah-picker__names strong {
  color: #e8efeb !important;
}

.mushaf-page--dark .theme-segmented-control-new,
.mushaf-page--dark .surah-picker__search,
.mushaf-page--dark .navigator-select-wrap select {
  background: #16241e !important;
  border-color: rgba(255, 255, 255, 0.1) !important;
  color: #eff5f2 !important;
}

.mushaf-page--dark .theme-segment-btn-new {
  color: #9fb0aa !important;
}

.mushaf-page--dark .theme-segment-btn-new--active {
  background: #25382f !important;
  color: #f4d58a !important;
}

.mushaf-page--dark .tss-switch,
.mushaf-page--dark .ayah-list-item,
.mushaf-page--dark .qari-item,
.mushaf-page--dark .surah-picker__item,
.mushaf-page--dark .ayah-picker__grid button,
.mushaf-page--dark .section-picker__grid button {
  background: #16241e !important;
  border-color: rgba(255, 255, 255, 0.09) !important;
  color: #e8efeb !important;
}

.mushaf-page--dark .ayah-list-item:hover,
.mushaf-page--dark .qari-item--active,
.mushaf-page--dark .surah-picker__item--active,
.mushaf-page--dark .ayah-picker__grid .ayah-picker__number--active,
.mushaf-page--dark .section-picker__grid .section-picker__number--active {
  background: #20362c !important;
  border-color: rgba(244, 213, 138, 0.36) !important;
  color: #f4d58a !important;
}

.mushaf-page--dark .ayah-list-item__icon-wrapper,
.mushaf-page--dark .surah-picker__number {
  background: rgba(244, 213, 138, 0.12) !important;
  color: #f4d58a !important;
}

.mushaf-page--dark .surah-picker__arabic,
.mushaf-page--dark .qari-item > svg,
.mushaf-page--dark .surah-picker__item > svg,
.mushaf-page--dark .navigator-select-wrap svg {
  color: #d6c18b !important;
}

.mushaf-page--dark .surah-picker__search input {
  color: #eff5f2 !important;
}

.mushaf-page--dark .surah-picker__search input::placeholder {
  color: #7f918a !important;
}

/* Teleported surfaces need body theme selectors because they render outside .mushaf-page */
:global(body.theme-body-dark) .mushaf-player {
  background: rgba(13, 24, 20, 0.96) !important;
  border-top-color: rgba(209, 164, 84, 0.2) !important;
  box-shadow: 0 -12px 34px rgba(0, 0, 0, 0.42) !important;
  backdrop-filter: blur(18px);
}

:global(body.theme-body-dark) .mushaf-player__btn {
  color: #a8b7b1 !important;
  background: rgba(255, 255, 255, 0.04) !important;
}

:global(body.theme-body-dark) .mushaf-player__btn:hover,
:global(body.theme-body-dark) .mushaf-player__btn:active,
:global(body.theme-body-dark) .mushaf-player__btn--active {
  color: #f4d58a !important;
  background: rgba(244, 213, 138, 0.12) !important;
}

:global(body.theme-body-dark) .mushaf-player__play {
  color: #06231a !important;
  background: linear-gradient(135deg, #f4d58a, #c79b42) !important;
  box-shadow: 0 8px 20px rgba(199, 155, 66, 0.22) !important;
}

:global(body.theme-body-dark) .mushaf-player__info strong {
  color: #f5f7f5 !important;
}

:global(body.theme-body-dark) .mushaf-player__info small {
  color: #9fb0aa !important;
}

:global(body.theme-body-dark) .mushaf-player__info > span {
  background: rgba(255, 255, 255, 0.12) !important;
}

:global(body.theme-body-dark) .mushaf-player__info > span i {
  background: #f4d58a !important;
}

:global(body.theme-body-dark) .mushaf-player__qari-select > svg {
  color: #d6c18b !important;
}

:global(body.theme-body-dark) .translation-bottom-sheet,
:global(body.theme-body-dark) .ayah-options-sheet,
:global(body.theme-body-dark) .navigator-sheet,
:global(body.theme-body-dark) .qari-sheet {
  background: #111c17 !important;
  border-color: rgba(209, 164, 84, 0.16) !important;
  color: #eff5f2 !important;
  box-shadow: 0 -14px 36px rgba(0, 0, 0, 0.48) !important;
}

:global(body.theme-body-dark) .translation-sheet-settings {
  background: #0d1713 !important;
  border-top-color: rgba(255, 255, 255, 0.08) !important;
}

:global(body.theme-body-dark) .translation-sheet-header h2,
:global(body.theme-body-dark) .qari-sheet__header h2,
:global(body.theme-body-dark) .navigator-sheet__title,
:global(body.theme-body-dark) .ayah-drawer-title,
:global(body.theme-body-dark) .surah-picker__header strong,
:global(body.theme-body-dark) .theme-label-small,
:global(body.theme-body-dark) .tss-toggle-label,
:global(body.theme-body-dark) .tss-font-size-label,
:global(body.theme-body-dark) .translation-text,
:global(body.theme-body-dark) .translation-item,
:global(body.theme-body-dark) .ayah-list-item__label,
:global(body.theme-body-dark) .navigator-field label,
:global(body.theme-body-dark) .surah-picker__names strong,
:global(body.theme-body-dark) .qari-item strong {
  color: #e8efeb !important;
}

:global(body.theme-body-dark) .translation-sheet-header p,
:global(body.theme-body-dark) .qari-sheet__header p,
:global(body.theme-body-dark) .ayah-drawer-subtitle,
:global(body.theme-body-dark) .ayah-picker__summary,
:global(body.theme-body-dark) .surah-picker__names small,
:global(body.theme-body-dark) .qari-item small {
  color: #9fb0aa !important;
}

:global(body.theme-body-dark) .translation-sheet-close,
:global(body.theme-body-dark) .qari-sheet__header > button,
:global(body.theme-body-dark) .surah-picker__header > button,
:global(body.theme-body-dark) .ayah-options-sheet .translation-sheet-close {
  color: #d6c18b !important;
  background: rgba(255, 255, 255, 0.07) !important;
}

:global(body.theme-body-dark) .theme-segmented-control-new,
:global(body.theme-body-dark) .surah-picker__search,
:global(body.theme-body-dark) .navigator-select-wrap select,
:global(body.theme-body-dark) .tss-switch,
:global(body.theme-body-dark) .ayah-list-item,
:global(body.theme-body-dark) .qari-item,
:global(body.theme-body-dark) .surah-picker__item,
:global(body.theme-body-dark) .ayah-picker__grid button,
:global(body.theme-body-dark) .section-picker__grid button {
  background: #16241e !important;
  border-color: rgba(255, 255, 255, 0.09) !important;
  color: #e8efeb !important;
}

:global(body.theme-body-dark) .theme-segment-btn-new {
  color: #9fb0aa !important;
}

:global(body.theme-body-dark) .theme-segment-btn-new--active,
:global(body.theme-body-dark) .ayah-list-item:hover,
:global(body.theme-body-dark) .qari-item--active,
:global(body.theme-body-dark) .surah-picker__item--active,
:global(body.theme-body-dark) .ayah-picker__grid .ayah-picker__number--active,
:global(body.theme-body-dark) .section-picker__grid .section-picker__number--active {
  background: #20362c !important;
  border-color: rgba(244, 213, 138, 0.36) !important;
  color: #f4d58a !important;
}

:global(body.theme-body-dark) .ayah-list-item__icon-wrapper,
:global(body.theme-body-dark) .surah-picker__number {
  background: rgba(244, 213, 138, 0.12) !important;
  color: #f4d58a !important;
}

:global(body.theme-body-dark) .surah-picker__arabic,
:global(body.theme-body-dark) .qari-item > svg,
:global(body.theme-body-dark) .surah-picker__item > svg,
:global(body.theme-body-dark) .navigator-select-wrap svg {
  color: #d6c18b !important;
}

:global(body.theme-body-dark) .surah-picker__search input {
  color: #eff5f2 !important;
}

:global(body.theme-body-dark) .surah-picker__search input::placeholder {
  color: #7f918a !important;
}
/* ===== Direct dark modifiers for teleported mushaf surfaces ===== */
.mushaf-player--dark {
  background: rgba(13, 24, 20, 0.98) !important;
  border-top: 1px solid rgba(209, 164, 84, 0.22) !important;
  box-shadow: 0 -14px 34px rgba(0, 0, 0, 0.42) !important;
  color: #eff5f2 !important;
}

.mushaf-player--dark .mushaf-player__actions {
  background: #13231d !important;
  border-color: rgba(255, 255, 255, 0.1) !important;
}

.mushaf-player--dark .mushaf-player__btn {
  background: transparent !important;
  color: #a8b7b1 !important;
}

.mushaf-player--dark .mushaf-player__btn:hover,
.mushaf-player--dark .mushaf-player__btn:active,
.mushaf-player--dark .mushaf-player__btn--active {
  background: rgba(244, 213, 138, 0.12) !important;
  color: #f4d58a !important;
}

.mushaf-player--dark .mushaf-player__play {
  background: linear-gradient(135deg, #f4d58a 0%, #c99f46 100%) !important;
  color: #06231a !important;
  box-shadow: 0 8px 22px rgba(199, 155, 66, 0.28) !important;
}

.mushaf-player--dark .mushaf-player__qari-select {
  color: #eff5f2 !important;
}

.mushaf-player--dark .mushaf-player__info strong {
  color: #f7faf8 !important;
}

.mushaf-player--dark .mushaf-player__info small {
  color: #a8b7b1 !important;
}

.mushaf-player--dark .mushaf-player__info > span {
  background: rgba(255, 255, 255, 0.12) !important;
}

.mushaf-player--dark .mushaf-player__info > span i {
  background: #f4d58a !important;
}

.translation-bottom-sheet--dark {
  background: #101b16 !important;
  border-color: rgba(209, 164, 84, 0.18) !important;
  color: #eff5f2 !important;
  box-shadow: 0 -16px 38px rgba(0, 0, 0, 0.5) !important;
}

.translation-bottom-sheet--dark .translation-sheet-header,
.translation-bottom-sheet--dark .translation-sheet-settings,
.translation-bottom-sheet--dark .translation-sheet-player {
  background: #101b16 !important;
  border-color: rgba(255, 255, 255, 0.08) !important;
}

.translation-bottom-sheet--dark .translation-sheet-list,
.translation-bottom-sheet--dark .translation-list,
.translation-bottom-sheet--dark .translation-sheet-body {
  background: #101b16 !important;
}

.translation-bottom-sheet--dark .translation-sheet-item,
.translation-bottom-sheet--dark .translation-item {
  background: #101b16 !important;
  border-color: rgba(255, 255, 255, 0.08) !important;
  color: #eff5f2 !important;
}

.translation-bottom-sheet--dark .translation-sheet-item--active,
.translation-bottom-sheet--dark .translation-item--active {
  background: #173026 !important;
}

.translation-bottom-sheet--dark .translation-sheet-header h2,
.translation-bottom-sheet--dark .translation-sheet-title,
.translation-bottom-sheet--dark .ayah-drawer-title,
.translation-bottom-sheet--dark .translation-sheet-item__text,
.translation-bottom-sheet--dark .translation-text,
.translation-bottom-sheet--dark .translation-item__text,
.translation-bottom-sheet--dark .ayah-list-item__label,
.translation-bottom-sheet--dark .theme-label-small,
.translation-bottom-sheet--dark .tss-toggle-label,
.translation-bottom-sheet--dark .tss-font-size-label {
  color: #eef5f1 !important;
}

.translation-bottom-sheet--dark .translation-sheet-header p,
.translation-bottom-sheet--dark .ayah-drawer-subtitle,
.translation-bottom-sheet--dark .translation-sheet-item__latin,
.translation-bottom-sheet--dark .translation-latin,
.translation-bottom-sheet--dark .translation-item__latin,
.translation-bottom-sheet--dark .tss-helper,
.translation-bottom-sheet--dark .tss-font-size-value {
  color: #a8b7b1 !important;
}

.translation-bottom-sheet--dark .translation-sheet-close {
  background: rgba(255, 255, 255, 0.08) !important;
  color: #d8c486 !important;
}

.translation-bottom-sheet--dark .translation-sheet-item__number-wrap svg circle {
  fill: #101b16 !important;
}

.translation-bottom-sheet--dark .translation-sheet-item__number-wrap svg path,
.translation-bottom-sheet--dark .translation-sheet-item__number-wrap svg text {
  color: #d8aa4e !important;
  fill: currentColor !important;
}

.translation-bottom-sheet--dark .theme-segmented-control-new,
.translation-bottom-sheet--dark .tss-switch,
.translation-bottom-sheet--dark .ayah-list-item {
  background: #15261f !important;
  border-color: rgba(255, 255, 255, 0.1) !important;
  color: #eff5f2 !important;
}

.translation-bottom-sheet--dark .theme-segment-btn-new {
  color: #a8b7b1 !important;
}

.translation-bottom-sheet--dark .theme-segment-btn-new--active,
.translation-bottom-sheet--dark .ayah-list-item:hover {
  background: #20362c !important;
  border-color: rgba(244, 213, 138, 0.36) !important;
  color: #f4d58a !important;
}

.translation-bottom-sheet--dark input,
.translation-bottom-sheet--dark select,
.translation-bottom-sheet--dark textarea {
  background: #15261f !important;
  border-color: rgba(255, 255, 255, 0.12) !important;
  color: #f4f7f5 !important;
}

.translation-bottom-sheet--dark input::placeholder,
.translation-bottom-sheet--dark textarea::placeholder {
  color: #7f918a !important;
}

.translation-sheet-player--dark {
  background: #0d1814 !important;
  border-top-color: rgba(209, 164, 84, 0.2) !important;
}

.qari-sheet--dark {
  background: #101b16 !important;
  border-color: rgba(209, 164, 84, 0.18) !important;
  color: #eff5f2 !important;
  box-shadow: 0 -16px 38px rgba(0, 0, 0, 0.5) !important;
}

.qari-sheet--dark .qari-sheet__header,
.qari-sheet--dark .audio-settings-body,
.qari-sheet--dark .qari-list {
  background: #101b16 !important;
  color: #eff5f2 !important;
}

.qari-sheet--dark .qari-sheet__header h2,
.qari-sheet--dark .qari-item strong,
.qari-sheet--dark .audio-settings-col label,
.qari-sheet--dark .audio-settings-field label {
  color: #f4f7f5 !important;
}

.qari-sheet--dark .qari-sheet__header p,
.qari-sheet--dark .qari-item small {
  color: #a8b7b1 !important;
}

.qari-sheet--dark .qari-sheet__header > button {
  background: rgba(255, 255, 255, 0.08) !important;
  color: #d8c486 !important;
}

.qari-sheet--dark .qari-item,
.qari-sheet--dark .audio-settings-col select,
.qari-sheet--dark .audio-settings-field select,
.qari-sheet--dark input,
.qari-sheet--dark select,
.qari-sheet--dark textarea {
  background: #15261f !important;
  border-color: rgba(255, 255, 255, 0.12) !important;
  color: #f4f7f5 !important;
}

.qari-sheet--dark option {
  background: #15261f !important;
  color: #f4f7f5 !important;
}

.qari-sheet--dark .qari-item--active {
  background: #20362c !important;
  border-color: rgba(244, 213, 138, 0.5) !important;
}

.qari-sheet--dark .qari-item--active svg,
.qari-sheet--dark .qari-item > svg {
  color: #f4d58a !important;
}

.qari-sheet--dark .audio-settings-actions button:first-child {
  background: #f4f7f5 !important;
  color: #52615b !important;
  border-color: rgba(255, 255, 255, 0.1) !important;
}

.navigator-sheet--dark {
  background: #101b16 !important;
  border-color: rgba(209, 164, 84, 0.18) !important;
  color: #eff5f2 !important;
  box-shadow: 0 -16px 38px rgba(0, 0, 0, 0.5) !important;
}

.navigator-sheet--dark .navigator-sheet__content,
.navigator-sheet--dark .surah-picker,
.navigator-sheet--dark .ayah-picker,
.navigator-sheet--dark .section-picker {
  background: #101b16 !important;
  color: #eff5f2 !important;
}

.navigator-sheet--dark .navigator-sheet__header h2,
.navigator-sheet--dark .navigator-sheet__title,
.navigator-sheet--dark .navigator-field label,
.navigator-sheet--dark .navigator-selector strong,
.navigator-sheet--dark .surah-picker__header strong,
.navigator-sheet--dark .surah-picker__names strong,
.navigator-sheet--dark .ayah-picker__summary strong,
.navigator-sheet--dark .section-picker__summary strong {
  color: #f4f7f5 !important;
}

.navigator-sheet--dark .navigator-sheet__header span,
.navigator-sheet--dark .navigator-sheet__header p,
.navigator-sheet--dark .navigator-selector small,
.navigator-sheet--dark .surah-picker__names small,
.navigator-sheet--dark .ayah-picker__summary,
.navigator-sheet--dark .section-picker__summary {
  color: #a8b7b1 !important;
}

.navigator-sheet--dark .navigator-sheet__header > button,
.navigator-sheet--dark .surah-picker__header > button,
.navigator-sheet--dark .ayah-picker__back,
.navigator-sheet--dark .section-picker__back {
  background: rgba(255, 255, 255, 0.08) !important;
  color: #d8c486 !important;
}

.navigator-sheet--dark .navigator-selector,
.navigator-sheet--dark .navigator-select-wrap select,
.navigator-sheet--dark .surah-picker__search,
.navigator-sheet--dark .surah-picker__item,
.navigator-sheet--dark .ayah-picker__grid button,
.navigator-sheet--dark .section-picker__grid button {
  background: #15261f !important;
  border-color: rgba(255, 255, 255, 0.12) !important;
  color: #f4f7f5 !important;
}

.navigator-sheet--dark .navigator-select-wrap option {
  background: #15261f !important;
  color: #f4f7f5 !important;
}

.navigator-sheet--dark .surah-picker__search input {
  color: #f4f7f5 !important;
}

.navigator-sheet--dark .surah-picker__search input::placeholder {
  color: #7f918a !important;
}

.navigator-sheet--dark .surah-picker__number {
  background: rgba(244, 213, 138, 0.14) !important;
  color: #f4d58a !important;
}

.navigator-sheet--dark .surah-picker__arabic,
.navigator-sheet--dark .surah-picker__item > svg,
.navigator-sheet--dark .navigator-selector > svg,
.navigator-sheet--dark .navigator-select-wrap svg {
  color: #d8c486 !important;
}

.navigator-sheet--dark .surah-picker__item--active,
.navigator-sheet--dark .ayah-picker__number--active,
.navigator-sheet--dark .section-picker__number--active {
  background: #20362c !important;
  border-color: rgba(244, 213, 138, 0.5) !important;
  color: #f4d58a !important;
}

/* --- Premium Settings Card Layout --- */
.settings-sheet-body {
  padding: 16px 20px 30px 20px !important;
  display: flex;
  flex-direction: column;
  gap: 16px;
  background: #fafcfb !important;
}
.qari-sheet--dark .settings-sheet-body {
  background: #111a17 !important;
}

.settings-card {
  background: #ffffff;
  border: 1px solid #eef3f1;
  border-radius: 14px;
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 16px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.02);
}
.qari-sheet--dark .settings-card {
  background: #16221f !important;
  border-color: #23342f !important;
  box-shadow: none;
}

.settings-card-title {
  font-size: 0.68rem;
  font-weight: 800;
  color: #8c9d96;
  letter-spacing: 0.08em;
  margin-bottom: -4px;
}
.qari-sheet--dark .settings-card-title {
  color: #556c63;
}

.settings-row {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.settings-row--switch {
  flex-direction: row;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 4px 0;
}
.settings-row--slider {
  gap: 12px;
}

.settings-row-header {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #2c3e35;
}
.qari-sheet--dark .settings-row-header {
  color: #cbd5e1;
}

.setting-label-icon {
  width: 16px;
  height: 16px;
  color: #64748b;
  flex-shrink: 0;
}
.qari-sheet--dark .setting-label-icon {
  color: #94a3b8;
}

.settings-row-label {
  font-size: 0.8rem;
  font-weight: 700;
}

/* Switches iOS Theme */
.tss-switch {
  position: relative;
  display: inline-block;
  width: 44px;
  height: 24px;
}
.tss-switch input {
  opacity: 0;
  width: 0;
  height: 0;
}
.tss-slider-toggle {
  position: absolute;
  cursor: pointer;
  inset: 0;
  background-color: #e2e8f0;
  transition: .3s;
  border-radius: 99px;
}
.qari-sheet--dark .tss-slider-toggle {
  background-color: #2a3b35;
}
.tss-slider-toggle:before {
  position: absolute;
  content: "";
  height: 18px;
  width: 18px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  transition: .3s;
  border-radius: 50%;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.tss-switch input:checked + .tss-slider-toggle {
  background-color: #059669;
}
.tss-switch input:checked + .tss-slider-toggle:before {
  transform: translateX(20px);
}

/* Slider Sizing */
.tss-font-value-new {
  font-size: 0.78rem;
  font-weight: 800;
  color: #064e3b;
  margin-left: auto;
  background: #eef7f4;
  padding: 2px 8px;
  border-radius: 6px;
}
.qari-sheet--dark .tss-font-value-new {
  color: #34d399;
  background: #1c2e29;
}

.tss-slider-wrap-new {
  position: relative;
  width: 100%;
  display: flex;
  align-items: center;
}
.tss-slider-new {
  -webkit-appearance: none;
  width: 100%;
  height: 6px;
  border-radius: 99px;
  outline: none;
  background: #e2e8f0;
}
.tss-slider-new::-webkit-slider-thumb {
  -webkit-appearance: none;
  width: 18px;
  height: 18px;
  background: #064e3b;
  border: 2px solid #ffffff;
  border-radius: 50%;
  cursor: pointer;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
  transition: transform 0.1s ease;
}
.qari-sheet--dark .tss-slider-new::-webkit-slider-thumb {
  background: #34d399;
  border-color: #16221f;
}
.tss-slider-new::-webkit-slider-thumb:active {
  transform: scale(1.2);
}
/* --- Mushaf Classic Loading Spinner & Transitions --- */
.mushaf-classic-loading {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 14px;
  background: #fffefa;
  z-index: 5;
  pointer-events: none;
}
.mushaf-theme--dark .mushaf-classic-loading {
  background: #101b16;
}
.mushaf-theme--nabawiyyah .mushaf-classic-loading {
  background: #faf5eb;
}

.mushaf-spinner {
  width: 36px;
  height: 36px;
  border: 3px solid rgba(6, 78, 59, 0.08);
  border-radius: 50%;
  border-top-color: #064e3b;
  animation: mushaf-spin 0.75s linear infinite;
}
.mushaf-theme--dark .mushaf-spinner {
  border-color: rgba(52, 211, 153, 0.08);
  border-top-color: #34d399;
}
.mushaf-theme--nabawiyyah .mushaf-spinner {
  border-color: rgba(140, 120, 83, 0.08);
  border-top-color: #8c7853;
}

.mushaf-classic-loading span {
  font-size: 0.78rem;
  font-weight: 700;
  color: #52615b;
  letter-spacing: 0.04em;
}
.mushaf-theme--dark .mushaf-classic-loading span {
  color: #a8b7b1;
}
.mushaf-theme--nabawiyyah .mushaf-classic-loading span {
  color: #8c7853;
}

@keyframes mushaf-spin {
  to { transform: rotate(360deg); }
}

.mushaf-classic-img {
  opacity: 0;
  transition: opacity 0.24s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}
.mushaf-classic-img--loaded {
  opacity: 1;
}

/* ===== Dark Theme (Malam) Meta & Surah Banner overrides ===== */
.mushaf-theme--dark .mushaf-meta > span {
  background: rgba(22, 33, 29, 0.95) !important;
  border: 1px solid rgba(189, 140, 48, 0.7) !important;
  color: rgb(212, 175, 55) !important; /* Elegant gold text */
  box-shadow: 
    inset 0 1px 3px rgba(0,0,0,0.5),
    0 0 0 1.5px rgb(22, 109, 112) !important;
}

/* Dark mode Surah Banner Background using inline dark arabesque SVG */
.mushaf-page-box--with-frame.mushaf-theme--dark .mushaf-surah-banner__inner {
  background:
    url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='80' height='34' viewBox='0 0 80 34'%3E%3Cdefs%3E%3ClinearGradient id='bgDark' x1='0' y1='0' x2='0' y2='1'%3E%3Cstop offset='0%25' stop-color='%2316221f'/%3E%3Cstop offset='50%25' stop-color='%231b2d29'/%3E%3Cstop offset='100%25' stop-color='%23121d1b'/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='80' height='34' fill='url(%23bgDark)'/%3E%3Cpath d='M0 17 C15 5, 25 5, 40 17 C55 29, 65 29, 80 17' fill='none' stroke='%23bd8c30' stroke-width='1.2' opacity='0.45'/%3E%3Cpath d='M0 17 C15 29, 25 29, 40 17 C55 5, 65 5, 80 17' fill='none' stroke='%23bd8c30' stroke-width='1.2' opacity='0.45'/%3E%3Ccircle cx='40' cy='17' r='3.5' fill='%23166d70' stroke='%23bd8c30' stroke-width='0.8'/%3E%3Ccircle cx='40' cy='17' r='1.5' fill='%23d34f3b'/%3E%3Ccircle cx='0' cy='17' r='3.5' fill='%23166d70' stroke='%23bd8c30' stroke-width='0.8'/%3E%3Ccircle cx='0' cy='17' r='1.5' fill='%23d34f3b'/%3E%3Ccircle cx='80' cy='17' r='3.5' fill='%23166d70' stroke='%23bd8c30' stroke-width='0.8'/%3E%3Ccircle cx='80' cy='17' r='1.5' fill='%23d34f3b'/%3E%3Cpath d='M12 11 Q20 7 28 13' fill='none' stroke='%23bd8c30' stroke-width='1' opacity='0.7'/%3E%3Cpath d='M52 23 Q60 27 68 21' fill='none' stroke='%23bd8c30' stroke-width='1' opacity='0.7'/%3E%3Ccircle cx='20' cy='9' r='1.8' fill='%23d34f3b' opacity='0.8'/%3E%3Ccircle cx='60' cy='25' r='1.8' fill='%23d34f3b' opacity='0.8'/%3E%3Ccircle cx='28' cy='22' r='1.5' fill='%23eec256' opacity='0.8'/%3E%3Ccircle cx='52' cy='12' r='1.5' fill='%23eec256' opacity='0.8'/%3E%3Cpath d='M15 15 C13 12, 9 14, 11 17 Z' fill='%233c763d' opacity='0.65'/%3E%3Cpath d='M65 19 C67 22, 71 20, 69 17 Z' fill='%233c763d' opacity='0.65'/%3E%3C/svg%3E") center / 80px 34px repeat !important;
  box-shadow:
    0 0 0 1px rgb(22, 109, 112),
    0 0 0 3px rgb(22, 33, 29),
    0 0 0 4.5px rgb(189, 140, 48) !important;
}

/* Dark mode pills inside the banner */
.mushaf-page-box--with-frame.mushaf-theme--dark .mushaf-surah-banner__name {
  background: rgb(22, 33, 29) !important;
  border: 1px solid rgb(189, 140, 48) !important;
  box-shadow: 0 0 0 1.5px rgb(189, 140, 48) !important;
  color: rgb(212, 175, 55) !important; /* Gold text color */
}
.mushaf-page-box--with-frame.mushaf-theme--dark .mushaf-surah-banner__sub {
  background: rgb(22, 33, 29) !important;
  border: 1px solid rgb(189, 140, 48) !important;
  box-shadow: 0 0 0 1.5px rgb(22, 109, 112) !important;
  color: rgb(212, 175, 55) !important; /* Gold text color */
}

/* ==========================================================================
   PREMIUM DOUBLE PAGE SPREAD (AL-FATIHAH & AL-BAQARAH)
   ========================================================================== */
.mushaf-page-box--fatihah .mushaf-frame,
.mushaf-page-box--baqarah .mushaf-frame {
  inset: 4cqw 3cqw 3cqw !important;
  z-index: 1 !important;
  display: block !important;
}

/* Remove default simple border lines on these pages */
.mushaf-page-box--fatihah .mushaf-frame__border,
.mushaf-page-box--baqarah .mushaf-frame__border {
  display: block !important;
  border: none !important;
  box-shadow: none !important;
}
.mushaf-page-box--fatihah .mushaf-frame__border::before,
.mushaf-page-box--baqarah .mushaf-frame__border::before,
.mushaf-page-box--fatihah .mushaf-frame__border::after,
.mushaf-page-box--baqarah .mushaf-frame__border::after {
  display: none !important; /* Hide old vine patterns */
}

/* Base thick frame (Teal & Gold geometric pattern) */
.mushaf-page-box--fatihah .mushaf-frame::before,
.mushaf-page-box--baqarah .mushaf-frame::before {
  content: '' !important;
  display: block !important;
  position: absolute !important;
  inset: -1.5cqw !important;
  z-index: 1 !important;
  background-color: rgb(22, 109, 112) !important; /* Deep Teal base */
  background-image: 
    url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100'%3E%3Cg stroke='%23bd8c30' stroke-width='1.5' fill='none' opacity='0.7'%3E%3Cpath d='M25 0 L50 25 L75 0' /%3E%3Cpath d='M0 25 L25 50 L0 75' /%3E%3Cpath d='M100 25 L75 50 L100 75' /%3E%3Cpath d='M25 100 L50 75 L75 100' /%3E%3Ccircle cx='50' cy='50' r='20' stroke='%23bd8c30' stroke-width='2' fill='%23115456'/%3E%3Ccircle cx='50' cy='50' r='15' stroke='%23115456' stroke-width='1'/%3E%3Cpath d='M40 50 Q50 30 60 50 Q50 70 40 50' fill='%23bd8c30' opacity='0.3'/%3E%3Cpath d='M50 40 Q70 50 50 60 Q30 50 50 40' fill='%23115456' opacity='0.2'/%3E%3C/g%3E%3C/svg%3E") !important;
  background-size: 15cqw 15cqw !important;
  background-position: center !important;
  /* Thick Gold and Cream borders encapsulating the teal pattern */
  box-shadow: 
    0 0 0 1.5cqw rgb(251, 243, 208),
    0 0 0 1.8cqw rgb(189, 140, 48),
    0 0 0 2.2cqw rgb(22, 109, 112),
    0 0 15px rgba(0,0,0,0.15) !important;
  pointer-events: none !important;
}

/* Inner Text Cutout (Cream) */
.mushaf-page-box--fatihah .mushaf-frame::after,
.mushaf-page-box--baqarah .mushaf-frame::after {
  content: '' !important;
  display: block !important;
  position: absolute !important;
  inset: 12cqw 10cqw !important; /* Cut out the center for the text */
  z-index: 2 !important;
  background-color: #fffcf5 !important; /* Cream center for text readability */
  box-shadow: 
    0 0 0 0.8cqw rgb(251, 243, 208),
    0 0 0 1.2cqw rgb(189, 140, 48),
    inset 0 0 0 1px rgb(189, 140, 48) !important;
  pointer-events: none !important;
}

/* Special styling for corners on Fatihah/Baqarah */
.mushaf-page-box--fatihah .mushaf-frame__corner,
.mushaf-page-box--baqarah .mushaf-frame__corner {
  display: block !important;
  width: 16cqw !important;
  height: 16cqw !important;
  z-index: 3 !important;
  background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100'%3E%3Ccircle cx='50' cy='50' r='45' fill='%23166d70' stroke='%23bd8c30' stroke-width='3'/%3E%3Ccircle cx='50' cy='50' r='38' fill='none' stroke='%23fbf5e2' stroke-width='1.5' stroke-dasharray='4 2'/%3E%3Cpath d='M50 10 L60 40 L90 50 L60 60 L50 90 L40 60 L10 50 L40 40 Z' fill='%23fbf5e2' stroke='%23bd8c30' stroke-width='1.5'/%3E%3Ccircle cx='50' cy='50' r='18' fill='%23d34f3b' stroke='%23bd8c30' stroke-width='2'/%3E%3Ccircle cx='50' cy='50' r='8' fill='%23166d70'/%3E%3C/svg%3E") center / contain no-repeat !important;
  box-shadow: none !important;
}
.mushaf-page-box--fatihah .mushaf-frame__corner--tl, .mushaf-page-box--baqarah .mushaf-frame__corner--tl { top: -6.5cqw !important; left: -6.5cqw !important; }
.mushaf-page-box--fatihah .mushaf-frame__corner--tr, .mushaf-page-box--baqarah .mushaf-frame__corner--tr { top: -6.5cqw !important; right: -6.5cqw !important; }
.mushaf-page-box--fatihah .mushaf-frame__corner--bl, .mushaf-page-box--baqarah .mushaf-frame__corner--bl { bottom: -6.5cqw !important; left: -6.5cqw !important; }
.mushaf-page-box--fatihah .mushaf-frame__corner--br, .mushaf-page-box--baqarah .mushaf-frame__corner--br { bottom: -6.5cqw !important; right: -6.5cqw !important; }

/* Ensure full frame on all 4 sides regardless of spine logic */
.mushaf-page-box--fatihah .mushaf-frame::before,
.mushaf-page-box--baqarah .mushaf-frame::before {
  inset: -1.5cqw !important;
}
.mushaf-page-box--fatihah .mushaf-frame__corner,
.mushaf-page-box--baqarah .mushaf-frame__corner {
  display: block !important;
}
.mushaf-page-box--fatihah .mushaf-frame__border,
.mushaf-page-box--baqarah .mushaf-frame__border {
  border: none !important;
}

/* Ensure QCF Content sits beautifully within the new thick cutout */
.mushaf-page-box--fatihah .mushaf-qcf-content,
.mushaf-page-box--baqarah .mushaf-qcf-content {
  position: relative !important;
  z-index: 10 !important;
  padding: 24cqw 12cqw 16cqw !important; /* Ensure it is fully inside the cutout */
}
.mushaf-page-box--fatihah .mushaf-line,
.mushaf-page-box--baqarah .mushaf-line {
  font-size: 3.5cqw !important; /* Slightly smaller to fit gracefully inside smaller cutout */
}

/* Banner Styling for Fatihah & Baqarah (High Specificity) */
.mushaf-page-box--with-frame.mushaf-page-box--fatihah .mushaf-surah-banner,
.mushaf-page-box--with-frame.mushaf-page-box--baqarah .mushaf-surah-banner {
  margin-bottom: 2cqw !important;
}
.mushaf-page-box--with-frame.mushaf-page-box--fatihah .mushaf-surah-banner__inner,
.mushaf-page-box--with-frame.mushaf-page-box--baqarah .mushaf-surah-banner__inner,
.mushaf-page-box--with-frame.mushaf-theme--classic.mushaf-page-box--fatihah .mushaf-surah-banner__inner,
.mushaf-page-box--with-frame.mushaf-theme--classic.mushaf-page-box--baqarah .mushaf-surah-banner__inner {
  border-radius: 999px !important; /* Pill shape */
  background: linear-gradient(to right, #a27218, #f8df73, #f8df73, #a27218) !important;
  box-shadow: 
    0 0 0 2px #fffcf5,
    0 0 0 4px #166d70,
    0 5px 15px rgba(0,0,0,0.3) !important;
  border: 1px solid #166d70 !important;
  min-height: 8cqw !important;
  width: 100% !important;
}

/* Remove old inner capsule inside banner */
.mushaf-page-box--with-frame.mushaf-page-box--fatihah .mushaf-surah-banner__name,
.mushaf-page-box--with-frame.mushaf-page-box--baqarah .mushaf-surah-banner__name,
.mushaf-page-box--with-frame.mushaf-theme--classic.mushaf-page-box--fatihah .mushaf-surah-banner__name,
.mushaf-page-box--with-frame.mushaf-theme--classic.mushaf-page-box--baqarah .mushaf-surah-banner__name {
  background: transparent !important;
  box-shadow: none !important;
  border: none !important;
  color: #115456 !important;
  font-size: 6.5cqw !important;
  text-shadow: 0px 1px 1px rgba(255,255,255,0.8) !important;
}

/* Clean up Bismillah Calligraphy */
.mushaf-page-box--with-frame.mushaf-page-box--fatihah .mushaf-bismillah-calligraphy,
.mushaf-page-box--with-frame.mushaf-page-box--baqarah .mushaf-bismillah-calligraphy {
  margin-top: 2cqw !important;
  margin-bottom: 2cqw !important;
  font-size: clamp(24px, 4.5cqw, 36px) !important;
}

/* Ensure meta headers align nicely inside the cutout */
.mushaf-page-box--fatihah .mushaf-meta,
.mushaf-page-box--baqarah .mushaf-meta {
  top: 14cqw !important; /* Move down inside the cutout */
  left: 12cqw !important;
  right: 12cqw !important;
}
</style>
