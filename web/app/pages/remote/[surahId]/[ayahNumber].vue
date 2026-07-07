<template>
  <div class="remote-page" :class="{ 'remote-page--hidden': isHiddenState, 'remote-page--listening': isListeningMode }" @touchstart.passive="onTouchStart" @touchmove.passive="onTouchMove" @touchend.passive="onTouchEnd">
    <!-- Header -->
    <header class="remote-header">
      <div class="remote-header__left">
        <button class="remote-header__back" @click="goBack" aria-label="Kembali">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="15 18 9 12 15 6"/>
          </svg>
        </button>
        <div class="remote-header__surah-info">
          <button type="button" class="header-eyebrow" :class="'header-eyebrow--' + sessionMode" @click.stop="openModeDrawer">
            {{ sessionMode === 'listening' ? 'Mendengarkan' : 'Uji Hafalan' }}
            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" style="width: 12px; height: 12px; margin-left: 2px;"><path d="m6 8 4 4 4-4"/></svg>
          </button>
          <div class="remote-header__surah-trigger" @click="openNavigator">
            <h1 class="remote-header__title">{{ surahNumber }}. {{ surahName }}</h1>
            <div class="remote-header__subtitle">Ayat {{ currentAyahNumber }} dari {{ totalAyah }}</div>
          </div>
        </div>
      </div>
      <button type="button" class="remote-header__browse" aria-label="Pilih surat dan ayat" @click="openNavigator">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
          <path d="M4 6h16M4 12h16M4 18h10"/>
          <circle cx="18" cy="18" r="2.5"/>
        </svg>
      </button>
    </header>

    <!-- Qari Audio Control Bar -->
    <div class="audio-panel" v-if="!isListeningMode && displayAyah && (isRevealed || shouldAutoplayAudio)">
      <button class="audio-qari-selector" @click="openQariPicker">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
          <circle cx="12" cy="7" r="4"></circle>
        </svg>
        <span>{{ activeQariName }}</span>
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <polyline points="6 9 12 15 18 9"></polyline>
        </svg>
      </button>
      <button class="audio-play-btn" :class="{ 'audio-play-btn--playing': isPlaying }" @click="playAudio" :aria-label="isPlaying ? 'Jeda Audio' : 'Putar Audio'">
        <template v-if="isPlaying">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <rect x="6" y="4" width="4" height="16"></rect>
            <rect x="14" y="4" width="4" height="16"></rect>
          </svg>
        </template>
        <template v-else>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <polygon points="5 3 19 12 5 21 5 3"></polygon>
          </svg>
        </template>
      </button>
    </div>

    <!-- Content -->
    <main ref="remoteContentRef" class="remote-content" :class="{ 'remote-content--listening': isListeningMode }" @click="!isListeningMode && toggleReveal()">
      <template v-if="isListeningMode">
        <div v-if="listeningAyahsLoading" class="listening-splash" role="status" aria-live="polite">
          <div class="listening-splash__mark" aria-hidden="true"><span></span></div>
          <p class="listening-splash__title">Menyiapkan ayat</p>
          <p class="listening-splash__hint">Sebentar, bacaan sedang disusun.</p>
        </div>
        <div class="listening-ayah-list" v-else-if="listeningAyahs.length">
          <button
            v-for="ayah in listeningAyahs"
            :key="ayah.id"
            type="button"
            class="listening-ayah-card"
            :class="{ 'listening-ayah-card--active': ayah.ayah_number === currentAyahNumber }"
            :data-ayah="ayah.ayah_number"
            @click="changeAyah(ayah.ayah_number)"
          >

            <div class="listening-ayah-card__badge" aria-hidden="true" v-html="formatListeningAyahBadge(ayah.ayah_number)"></div>
            <div class="listening-ayah-card__body">
              <p class="listening-ayah-card__arabic" v-html="formatArabicText(ayah.text_arabic)"></p>
              <p class="listening-ayah-card__translation" v-if="ayah.translation_id">{{ ayah.translation_id }}</p>
            </div>
          </button>
        </div>
        <div v-else class="remote-hidden remote-hidden--loading">
          <p class="remote-hidden__text">Ayat belum tersedia.</p>
        </div>
      </template>

      <template v-else>
        <Transition name="ayah" mode="out-in">
          <!-- Hidden State -->
          <div v-if="!isRevealed" class="remote-hidden" key="hidden">
            <!-- Surah label -->
            <p class="rh-label">{{ surahName }}</p>

            <!-- Ayat number: two clean nested circles -->
            <div class="rh-circle-outer" :class="{ 'rh-circle-outer--recording': voiceState === 'recording' }">
              <!-- Concentric audio pulses — only when recording -->
              <span v-if="voiceState === 'recording'" class="rh-pulse-ring rh-pulse-ring--1" aria-hidden="true"></span>
              <span v-if="voiceState === 'recording'" class="rh-pulse-ring rh-pulse-ring--2" aria-hidden="true"></span>

              <div class="rh-circle-inner">
                <span class="rh-num">{{ currentAyahNumber }}</span>
              </div>
            </div>

            <!-- Hint zone: Intip Kata button or hint word card -->
            <div class="rh-hint-zone">
              <div v-if="showFirstWordHint" class="rh-hint-card" @click.stop>
                <span class="rh-hint-card__label">Kata Pertama</span>
                <p class="rh-hint-card__arabic text-arabic">{{ firstArabicWord }}</p>
                <button class="rh-hint-card__close" @click.stop="showFirstWordHint = false" aria-label="Tutup petunjuk">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
              </div>
              <button
                v-else-if="currentAyah"
                type="button"
                class="rh-action-btn rh-action-btn--hint"
                @click.stop="showFirstWordHint = true"
              >
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                  <circle cx="12" cy="12" r="3"/>
                </svg>
                <span>Intip Kata</span>
              </button>
            </div>

            <!-- Reveal instruction: only shown when there's already an audio recording -->
            <div v-if="voiceState === 'review'" class="rh-reveal-instruction" @click.stop="toggleReveal" role="button" tabindex="0" @keydown.enter="toggleReveal">
              <span class="rh-reveal-ring"></span>
              <span class="rh-reveal-inner">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="rh-reveal-eye-icon">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                  <circle cx="12" cy="12" r="3"/>
                </svg>
                <span class="rh-reveal-text">Ketuk layar untuk lihat ayat</span>
              </span>
            </div>

          </div>

          <!-- Revealed State -->
          <div v-else class="remote-revealed" key="revealed">
            <div class="remote-ayah-card">
              <!-- Ornament: top-left aligned in flex column -->
              <div class="remote-ayah-card__ornament" v-html="formatListeningAyahBadge(currentAyahNumber)"></div>
              <p :class="['text-arabic', dynamicFontSizeClass, 'remote-ayah-text']" v-if="displayAyah"
                 v-html="formatArabicText(displayAyah.text_arabic)">
              </p>
            </div>

            <p class="remote-translation" v-if="displayAyah?.translation_id">
              {{ displayAyah.translation_id }}
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
      </template>
    </main>

    <!-- Unified Bottom Action Bar -->
    <div class="remote-action-bar" :class="{ 'remote-action-bar--listening': isListeningMode }">
      <template v-if="isListeningMode">
        <div class="listening-player" role="status" aria-live="polite">
          <div class="listening-player__controls">
            <button class="listening-player__play" :class="{ 'listening-player__play--playing': isPlaying }" @click="playAudio" :aria-label="isPlaying ? 'Jeda Audio' : 'Putar Audio'">
              <svg v-if="isPlaying" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="6" y="5" width="4" height="14" rx="1"></rect><rect x="14" y="5" width="4" height="14" rx="1"></rect></svg>
              <svg v-else viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="m8 5 11 7-11 7V5Z"/></svg>
            </button>
            <button class="listening-player__control" :class="{ 'listening-player__control--active': isCustomRangeActive }" @click="toggleListeningRepeat" aria-label="Pengulangan Murotal">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
              <span v-if="isCustomRangeActive" class="listening-player__badge">Rentang</span>
            </button>
            <button class="listening-player__control" @click="openAudioSettings" aria-label="Pengaturan Murotal">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg>
            </button>
          </div>
          <button class="listening-player__qari" @click="openQariPicker">
            <span class="listening-player__qari-icon" aria-hidden="true"><img :src="activeQariImage" :alt="activeQariName" /></span>
            <span class="listening-player__qari-copy">
              <strong>{{ activeQariName }}</strong>
              <small>Ayat {{ currentAyahNumber }} · {{ playerCurrentTimeLabel }} / {{ playerDurationLabel }}</small>
            </span>
            <svg class="listening-player__qari-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"></polyline></svg>
          </button>
        </div>
      </template>
      <template v-else>
        <!-- Secondary: Back to prev ayah — only when revealed (in hidden mode, header back handles navigation) -->
        <button
          v-if="isRevealed"
          class="action-btn action-btn--back"
          @click="prevAyah"
          :disabled="currentAyahNumber <= 1"
          aria-label="Sebelumnya"
        >
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="15 18 9 12 15 6"/>
          </svg>
        </button>

        <!-- Rating buttons: only shown AFTER ayah is revealed -->
        <template v-if="isRevealed">
          <!-- Secondary: Forgot -->
          <button class="action-btn action-btn--forgot" @click="submitReview('forgot')" :disabled="submitting">
            <span class="action-btn__icon">&#10005;</span>
            <span class="action-btn__label">Lupa</span>
          </button>

          <!-- Secondary: Doubtful -->
          <button class="action-btn action-btn--doubtful" @click="submitReview('doubtful')" :disabled="submitting">
            <span class="action-btn__icon">~</span>
            <span class="action-btn__label">Ragu</span>
          </button>

          <!-- Primary: Fluent & Next -->
          <button class="action-btn action-btn--fluent" @click="skipAyah" :disabled="submitting">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
            <span class="action-btn__label">Lancar</span>
          </button>
        </template>

        <!-- Hidden mode: only mic bar, centered full-width -->
        <template v-else>
          <div class="wa-dock" @click.stop>
            <!-- ── IDLE: premium hold-to-record button ── -->
            <button
              v-if="voiceState === 'idle'"
              type="button"
              class="wa-idle-btn"
              @pointerdown="onRecordPointerDown"
              aria-label="Tahan untuk merekam"
            >
              <span class="wa-idle-btn__glow"></span>
              <span class="wa-idle-btn__icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                  <path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z"/>
                  <path d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/>
                </svg>
              </span>
              <span class="wa-idle-btn__label">Tahan untuk Rekam</span>
            </button>

            <!-- ── RECORDING (hold or locked) ── -->
            <template v-if="voiceState === 'recording' && (isHoldingRecord || isRecordingLocked)">
              <!-- Lock panel: floats above the mic button area (left side like WhatsApp) -->
              <div v-if="isHoldingRecord && !isRecordingLocked" class="wa-lock-panel">
                <div class="wa-lock-icon" :class="{ 'wa-lock-icon--ready': recordDragY < -80 }">
                  <svg v-if="recordDragY < -80" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                  </svg>
                  <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 17c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm6-9h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6-5c1.66 0 3 1.34 3 3v2H9V6c0-1.66 1.34-3 3-3zm6 15H6V10h12v10z"/>
                  </svg>
                </div>
                <span class="wa-lock-chevron wa-lock-chevron--1">▲</span>
                <span class="wa-lock-chevron wa-lock-chevron--2">▲</span>
              </div>

              <!-- Flex row: [pill bar ─────────────────] [mic button] -->
              <div class="wa-rec-row">
                <!-- LEFT: pill info bar -->
                <div class="wa-rec-bar">
                  <div class="wa-rec-left">
                    <span class="wa-rec-pulse"></span>
                    <span class="wa-rec-timer">{{ voiceDurationLabel }}</span>
                  </div>

                  <!-- Holding: slide-to-cancel hint -->
                  <template v-if="isHoldingRecord && !isRecordingLocked">
                    <div
                      class="wa-cancel-hint"
                      :class="{ 'wa-cancel-hint--active': recordDragX < -110 }"
                    >
                      <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor" class="wa-cancel-arrow-icon"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>
                      <span v-if="recordDragX < -110">Lepas untuk batal</span>
                      <span v-else>Geser untuk batal</span>
                    </div>
                  </template>

                  <!-- Locked: action buttons -->
                  <template v-else>
                    <button type="button" class="wa-discard-btn" @click.stop="onRecordPointerCancel" aria-label="Buang rekaman">
                      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                      </svg>
                    </button>
                    <button type="button" class="wa-send-btn" @click.stop="stopRecording">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><rect x="4" y="4" width="16" height="16" rx="2"/></svg>
                      Selesai
                    </button>
                  </template>
                </div>

                <!-- RIGHT: Mic trigger — slides with drag -->
                <button
                  v-if="isHoldingRecord && !isRecordingLocked"
                  key="wa-mic-trigger"
                  type="button"
                  class="wa-mic-trigger"
                  :style="{
                    transform: 'translateX(' + recordDragX + 'px) translateY(' + Math.max(-80, recordDragY) + 'px)',
                  }"
                  @pointerdown="onRecordPointerDown"
                  aria-label="Rekam"
                >
                  <div class="wa-mic-trigger__inner"></div>
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z"/>
                    <path d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/>
                  </svg>
                </button>
              </div>
            </template>

            <!-- ── REVIEW: playback bar ── -->
            <div v-else-if="voiceState === 'review'" class="rh-vn-bar">
              <button type="button" class="rh-vn-delete-btn" @click.stop="deleteVoice" aria-label="Hapus rekaman">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
              </button>
              <button type="button" class="rh-vn-play-btn" @click.stop="togglePlayVoice">
                <svg v-if="isPlayingVoice" width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="4" width="4" height="16" rx="1"/><rect x="14" y="4" width="4" height="16" rx="1"/></svg>
                <svg v-else width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
              </button>
              <div class="rh-vn-waveform">
                <div class="rh-vn-progress-track"><div class="rh-vn-progress-fill" :style="{ width: voicePlayProgress + '%' }"></div></div>
                <span class="rh-vn-time">{{ voiceDurationLabel }}</span>
              </div>
              <button type="button" class="rh-vn-download-btn" @click.stop="downloadVoice">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
              </button>
            </div>
          </div>
        </template>
      </template>
    </div>

    <!-- Mushaf-style Navigator Sheet (Pilih Bacaan) -->
    <Transition name="sheet">
      <div v-if="navigatorOpen" class="navigator-overlay" @click="closeNavigator">
        <section class="navigator-sheet"  :class="{ 'navigator-sheet--picker-open': navigatorShowSurahPicker || navigatorShowAyahPicker }" role="dialog" aria-modal="true" aria-labelledby="remote-navigator-title" :style="navigatorSheet.sheetStyle.value" @click.stop>
          <div class="navigator-sheet__handle" v-bind="navigatorSheet.bindHandle"></div>

          <div class="navigator-sheet__content">
            <header class="navigator-sheet__header">
              <div>
                <span>{{ sessionMode === 'listening' ? 'Mendengarkan' : 'Uji Hafalan' }}</span>
                <h2 id="remote-navigator-title">Pilih Bacaan</h2>
                <p>Surat dan ayat yang ingin dibuka</p>
              </div>
              <button type="button" aria-label="Tutup navigasi" @click="closeNavigator">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="m6 6 12 12M18 6 6 18"/>
                </svg>
              </button>
            </header>

            <form class="navigator-dynamic" @submit.prevent="confirmNavigation">
              <div class="navigator-fields-row">
                <div class="navigator-field navigator-field--surah">
                  <label>Surat</label>
                  <button type="button" class="navigator-selector" @click="navigatorShowSurahPicker = true">
                    <span>
                      <strong v-if="navigatorSurahMeta">{{ navigatorSurahMeta.number }}. {{ navigatorSurahMeta.name_latin }}</strong>
                      <strong v-else>Memuat...</strong>
                      <small v-if="navigatorSurahMeta">{{ navigatorSurahMeta.total_ayah }} ayat</small>
                    </span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m8 10 4 4 4-4"/></svg>
                  </button>
                </div>
                <div class="navigator-field navigator-field--ayah">
                  <label>Ayat</label>
                  <button type="button" class="navigator-selector" @click="navigatorShowAyahPicker = true">
                    <span><strong>{{ navigatorSelectedAyah }}</strong></span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m8 10 4 4 4-4"/></svg>
                  </button>
                </div>
              </div>

              <button type="submit" class="navigator-primary" :disabled="!navigatorSurahMeta">
                Buka ayat
              </button>
            </form>
          </div>

          <!-- Surah sub-picker -->
          <Transition name="picker">
            <div v-if="navigatorShowSurahPicker" class="surah-picker">
              <header class="surah-picker__header">
                <button type="button" aria-label="Kembali ke navigasi" @click="navigatorShowSurahPicker = false">
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
                <input v-model="navigatorSurahSearch" type="search" placeholder="Cari nama atau nomor surat">
              </label>

              <div class="surah-picker__list">
                <button
                  v-for="s in filteredNavigatorSurahs"
                  :key="s.id"
                  type="button"
                  class="surah-picker__item"
                  :class="{ 'surah-picker__item--active': s.id === navigatorSurahId }"
                  @click="chooseNavigatorSurah(s)"
                >
                  <span class="surah-picker__number">{{ s.number }}</span>
                  <span class="surah-picker__names">
                    <strong>{{ s.name_latin }}</strong>
                    <small>{{ s.total_ayah }} ayat</small>
                  </span>
                  <span class="surah-picker__arabic">{{ s.name_arabic }}</span>
                  <svg v-if="s.id === navigatorSurahId" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                    <path d="m5 10 3 3 7-7"/>
                  </svg>
                </button>
              </div>
            </div>
          </Transition>

          <!-- Ayah sub-picker -->
          <Transition name="picker">
            <div v-if="navigatorShowAyahPicker" class="surah-picker ayah-picker">
              <header class="surah-picker__header">
                <button type="button" aria-label="Kembali ke navigasi" @click="navigatorShowAyahPicker = false">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m15 18-6-6 6-6"/>
                  </svg>
                </button>
                <div>
                  <span>{{ navigatorSurahMeta?.name_latin || 'Surat' }}</span>
                  <strong>Pilih ayat</strong>
                </div>
              </header>

              <p class="ayah-picker__summary">Tersedia {{ navigatorSurahMeta?.total_ayah || 0 }} ayat</p>

              <div class="ayah-picker__grid">
                <button
                  v-for="n in navigatorAvailableAyahs"
                  :key="n"
                  type="button"
                  :class="{ 'ayah-picker__number--active': n === navigatorSelectedAyah }"
                  :aria-label="'Pilih ayat ' + n"
                  @click="chooseNavigatorAyah(n)"
                >
                  {{ n }}
                </button>
              </div>
            </div>
          </Transition>
        </section>
      </div>
    </Transition>

    <!-- Shared Murottal Range Settings -->
    <Transition name="sheet">
      <div v-if="showAudioSettings" class="picker-overlay" @click="closeAudioSettings">
        <section class="picker-sheet listening-settings-sheet" role="dialog" aria-modal="true" aria-labelledby="listening-settings-title" :style="audioSettingsSheet.sheetStyle.value" @click.stop>
          <div class="picker-sheet__indicator" v-bind="audioSettingsSheet.bindHandle"></div>
          <template v-if="settingsPickerType === 'none'">
            <header class="listening-settings__header"><h3 id="listening-settings-title">Pengaturan Murotal</h3></header>
            <div class="audio-settings-body">
              <div class="audio-settings-row">
                <div class="audio-settings-col"><label>Dari Surat</label><button type="button" class="custom-select-trigger" @click="settingsPickerType = 'startSurah'"><span>{{ settingsStartSurahName }}</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m8 10 4 4 4-4"/></svg></button></div>
                <div class="audio-settings-col"><label>Ayat</label><button type="button" class="custom-select-trigger" @click="settingsPickerType = 'startAyah'"><span>{{ settingsStartAyah }}</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m8 10 4 4 4-4"/></svg></button></div>
              </div>
              <div class="audio-settings-row">
                <div class="audio-settings-col"><label>Hingga Surat</label><button type="button" class="custom-select-trigger" @click="settingsPickerType = 'endSurah'"><span>{{ settingsEndSurahName }}</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m8 10 4 4 4-4"/></svg></button></div>
                <div class="audio-settings-col"><label>Ayat</label><button type="button" class="custom-select-trigger" @click="settingsPickerType = 'endAyah'"><span>{{ settingsEndAyah }}</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m8 10 4 4 4-4"/></svg></button></div>
              </div>
              <div class="audio-settings-field"><label>Pengulangan Ayat</label><button type="button" class="custom-select-trigger" @click="settingsPickerType = 'ayahRepeat'"><span>{{ settingsAyahRepeat }}x</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m8 10 4 4 4-4"/></svg></button></div>
              <div class="audio-settings-field"><label>Pengulangan Keseluruhan</label><button type="button" class="custom-select-trigger" @click="settingsPickerType = 'rangeRepeat'"><span>{{ settingsRangeRepeat === 99999 ? 'Tanpa batas' : settingsRangeRepeat + 'x' }}</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m8 10 4 4 4-4"/></svg></button></div>
              <div class="audio-settings-actions"><button class="settings-action-btn settings-action-btn--cancel" @click="closeAudioSettings">Batal</button><button class="settings-action-btn settings-action-btn--play" @click="startCustomRangePlayback">Putar</button></div>
            </div>
          </template>
          <template v-else>
            <header class="settings-picker-header"><button type="button" class="settings-picker-back" @click="settingsPickerType = 'none'"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m15 18-6-6 6-6"/></svg></button><strong>{{ settingsPickerTitle }}</strong></header>
            <div v-if="settingsPickerType === 'startSurah' || settingsPickerType === 'endSurah'" class="settings-picker-search"><input v-model="settingsSurahSearch" type="search" placeholder="Cari nama atau nomor surat..."></div>
            <div class="settings-picker-options">
              <template v-if="settingsPickerType === 'startSurah' || settingsPickerType === 'endSurah'">
                <button v-for="s in filteredSettingsSurahs" :key="s.id" type="button" class="settings-surah-item" :class="{ 'settings-option--active': settingsPickerType === 'startSurah' ? settingsStartSurah === s.number : settingsEndSurah === s.number }" :disabled="settingsPickerType === 'endSurah' && s.number < settingsStartSurah" @click="selectSettingsSurah(s.number)"><span class="settings-surah-number">{{ s.number }}</span><span class="settings-surah-name"><strong>{{ s.name_latin }}</strong><small>{{ s.total_ayah }} ayat</small></span><span class="settings-surah-arabic">{{ s.name_arabic }}</span></button>
              </template>
              <template v-else-if="settingsPickerType === 'startAyah' || settingsPickerType === 'endAyah'">
                <div class="settings-ayah-grid"><button v-for="n in (settingsPickerType === 'startAyah' ? settingsStartTotalAyah : settingsEndTotalAyah)" :key="n" type="button" :class="{ 'settings-option--active': settingsPickerType === 'startAyah' ? settingsStartAyah === n : settingsEndAyah === n }" :disabled="settingsPickerType === 'endAyah' && settingsStartSurah === settingsEndSurah && n < settingsStartAyah" @click="selectSettingsAyah(n)">{{ n }}</button></div>
              </template>
              <template v-else><button v-for="option in settingsRepeatOptions" :key="option.value" type="button" class="settings-repeat-option" :class="{ 'settings-option--active': settingsPickerType === 'ayahRepeat' ? settingsAyahRepeat === option.value : settingsRangeRepeat === option.value }" @click="selectSettingsRepeat(option.value)">{{ option.label }}</button></template>
            </div>
          </template>
        </section>
      </div>
    </Transition>    <!-- iOS-style Qari Picker Sheet -->
    <Transition name="sheet">
      <div v-if="showQariPicker" class="picker-overlay" @click="closeQariPicker">
        <div class="picker-sheet" :style="qariPickerSheet.sheetStyle.value" @click.stop>
          <div class="picker-sheet__header">
            <div class="picker-sheet__indicator" v-bind="qariPickerSheet.bindHandle"></div>
            <div class="picker-sheet__title-row">
              <h3>Pilih Qori Murottal</h3>
              <button class="picker-sheet__close" @click="closeQariPicker">Selesai</button>
            </div>
          </div>
          <div class="picker-sheet__content">
            <div class="qari-picker-list">
              <button
                v-for="q in qariList"
                :key="q.id"
                class="qari-picker-item"
                :class="{ 'qari-picker-item--active': q.id === selectedQari }"
                @click="selectQari(q.id)"
              >
                <div class="qari-picker-item__left">
                  <span class="qari-picker-icon" aria-hidden="true">
                    <img :src="q.image" :alt="q.name" loading="lazy" />
                  </span>
                  <span class="qari-picker-copy">
                    <strong class="qari-picker-name">{{ q.name }}</strong>
                    <small class="qari-picker-country">{{ q.country }}</small>
                  </span>
                </div>
                <span class="qari-picker-check" v-if="q.id === selectedQari" aria-hidden="true">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                  </svg>
                </span>
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
const { open: openMurojaahDrawer } = useMurojaahDrawer()
const showToast = inject<(msg: string, type: string) => void>('showToast')
let isInitialized = false
let isUnmounted = false

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
  page?: number
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

const sessionMode = computed<'learning' | 'listening'>(() => route.query.mode === 'listening' ? 'listening' : 'learning')
const openModeDrawer = () => openMurojaahDrawer(sessionMode.value, 'quiz')

const legacyRevealMode = useCookie<string>('reveal_mode', {
  default: () => 'hidden',
  maxAge: 60 * 60 * 24 * 365,
  path: '/'
})

const legacyAutoplayAudio = useCookie<boolean>('autoplay_audio', {
  default: () => false,
  maxAge: 60 * 60 * 24 * 365,
  path: '/'
})

const legacyAutoNextAyah = useCookie<boolean>('auto_next_ayah', {
  default: () => false,
  maxAge: 60 * 60 * 24 * 365,
  path: '/'
})

const learningRevealMode = useCookie<string>('learning_reveal_mode', {
  default: () => legacyRevealMode.value || 'hidden',
  maxAge: 60 * 60 * 24 * 365,
  path: '/'
})

const learningAutoplayAudio = useCookie<boolean>('learning_autoplay_audio', {
  default: () => legacyAutoplayAudio.value ?? false,
  maxAge: 60 * 60 * 24 * 365,
  path: '/'
})



const listeningAutoNextAyah = useCookie<boolean>('listening_auto_next_ayah', {
  default: () => legacyAutoNextAyah.value ?? false,
  maxAge: 60 * 60 * 24 * 365,
  path: '/'
})

const currentAyah = ref<AyahData | null>(null)
const displayAyah = ref<AyahData | null>(null)
const lastSurahSnapshot = ref<{ name_latin: string; number: number; total_ayah: number } | null>(null)
const listeningAyahs = ref<AyahData[]>([])
const listeningAyahsLoading = ref(sessionMode.value === 'listening')
const remoteContentRef = ref<HTMLElement | null>(null)
const audioCurrentTime = ref(0)
const audioDuration = ref(0)
const surahList = ref<SurahItem[]>([])
const isRevealed = ref(sessionMode.value === 'listening' ? true : learningRevealMode.value === 'revealed')
const listeningRevealOverride = ref<boolean | null>(null)
const submitting = ref(false)
const flashStatus = ref<string | null>(null)
const showSurahPicker = ref(false)
const showAyahPicker = ref(false)
const isWheelDragging = ref(false)
const pickerSearch = ref('')
const pendingPickerSurahId = ref<number | null>(null)

// Navigator state (Pilih Bacaan)
const navigatorOpen = ref(false)
const navigatorSurahId = ref<number>(0)
const navigatorSelectedAyah = ref(1)
const navigatorShowSurahPicker = ref(false)
const navigatorShowAyahPicker = ref(false)
const navigatorSurahSearch = ref('')
const showAudioSettings = ref(false)
const settingsPickerType = ref<'none' | 'startSurah' | 'startAyah' | 'endSurah' | 'endAyah' | 'ayahRepeat' | 'rangeRepeat'>('none')
const settingsSurahSearch = ref('')

const isCustomRangeActive = useState<boolean>('mushafMurottalRangeActive', () => false)
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

// Qari selection list and audio player state
const showQariPicker = ref(false)
const isPlaying = ref(false)
const autoAdvancing = ref(false)
const selectedQari = useCookie<string>('selected_qari', {
  default: () => 'Maher_AlMuaiqly_64kbps',
  maxAge: 60 * 60 * 24 * 365, // 1 year
  path: '/'
})

// Auto-migrate legacy qari cookie to correct path
if (selectedQari.value === 'MaherAlMuaiqly_64kbps') {
  selectedQari.value = 'Maher_AlMuaiqly_64kbps'
}

const closeAudioSettings = () => { showAudioSettings.value = false; settingsPickerType.value = 'none'; settingsSurahSearch.value = '' }

const qariList = [
  { id: 'Maher_AlMuaiqly_64kbps', shortName: 'Maher Al-Muaiqly', name: 'Syekh Maher bin Hamad Al-Muaiqly', country: 'Arab Saudi', image: '/images/qori/syekh-maher-al-muaiqly.png' },
  { id: 'Alafasy_64kbps', shortName: 'Mishary Alafasy', name: 'Syekh Mishary Rashid Alafasy', country: 'Kuwait', image: '/images/qori/syekh-mishary-al-fasy.png' },
  { id: 'Ghamadi_40kbps', shortName: 'Saad Al-Ghamdi', name: 'Syekh Saad Said Al-Ghamdi', country: 'Arab Saudi', image: '/images/qori/syekh-saad-al-ghamdi.png' },
  { id: 'Husary_64kbps', shortName: 'Mahmoud Al-Husary', name: 'Syekh Mahmoud Khalil Al-Husary', country: 'Mesir', image: '/images/qori/syekh-mahmoud-al-husary.png' }
]

const activeQari = computed(() => qariList.find(q => q.id === selectedQari.value) || qariList[0])
const activeQariName = computed(() => activeQari.value.shortName)
const activeQariImage = computed(() => activeQari.value.image)
const pickerAyahSurah = computed(() => surahList.value.find(s => s.id === pendingPickerSurahId.value) || currentSurahMeta.value)
const pickerAyahTotal = computed(() => pickerAyahSurah.value?.total_ayah || totalAyah.value)
const pickerAyahSurahName = computed(() => pickerAyahSurah.value?.name_latin || surahName.value)

// Navigator computed
const navigatorSurahMeta = computed(() => surahList.value.find(s => s.id === navigatorSurahId.value) || null)
const navigatorAvailableAyahs = computed(() =>
  Array.from({ length: navigatorSurahMeta.value?.total_ayah || 1 }, (_, i) => i + 1)
)
const filteredNavigatorSurahs = computed(() => {
  const q = navigatorSurahSearch.value.trim().toLowerCase()
  if (!q) return surahList.value
  return surahList.value.filter(s =>
    s.name_latin.toLowerCase().includes(q) ||
    s.name_arabic.includes(q) ||
    s.number.toString() === q
  )
})
const settingsStartTotalAyah = computed(() => surahList.value.find(s => s.number === settingsStartSurah.value)?.total_ayah || 1)
const settingsEndTotalAyah = computed(() => surahList.value.find(s => s.number === settingsEndSurah.value)?.total_ayah || 1)
const settingsStartSurahName = computed(() => { const s = surahList.value.find(s => s.number === settingsStartSurah.value); return s ? `${s.number}. ${s.name_latin}` : 'Pilih Surat' })
const settingsEndSurahName = computed(() => { const s = surahList.value.find(s => s.number === settingsEndSurah.value); return s ? `${s.number}. ${s.name_latin}` : 'Pilih Surat' })
const settingsPickerTitle = computed(() => ({ startSurah: 'Dari Surat', startAyah: 'Dari Ayat', endSurah: 'Hingga Surat', endAyah: 'Hingga Ayat', ayahRepeat: 'Pengulangan Ayat', rangeRepeat: 'Pengulangan Keseluruhan' }[settingsPickerType.value] || ''))
const filteredSettingsSurahs = computed(() => { const q = settingsSurahSearch.value.trim().toLowerCase(); if (!q) return surahList.value; return surahList.value.filter(s => s.name_latin.toLowerCase().includes(q) || s.name_arabic.includes(q) || String(s.number) === q) })
const settingsRepeatOptions = computed(() => settingsPickerType.value === 'rangeRepeat' ? [1,2,3,5,10,20,99999].map(value => ({ value, label: value === 99999 ? 'Tanpa batas' : `${value}x` })) : [1,2,3,5,10,20].map(value => ({ value, label: `${value}x` })))

const normalizeMurottalRange = (changed?: 'start' | 'end') => {
  settingsStartAyah.value = Math.min(Math.max(1, settingsStartAyah.value), settingsStartTotalAyah.value)
  if (changed === 'start' && settingsEndSurah.value < settingsStartSurah.value) settingsEndSurah.value = settingsStartSurah.value
  settingsEndAyah.value = Math.min(Math.max(1, settingsEndAyah.value), settingsEndTotalAyah.value)
  if (settingsStartSurah.value === settingsEndSurah.value && settingsEndAyah.value < settingsStartAyah.value) {
    settingsEndAyah.value = settingsStartAyah.value
  }
}

const openAudioSettings = async () => {
  if (!surahList.value.length) await fetchSurahList()
  const currentNumber = currentSurahMeta.value?.number || surahNumber.value
  if (!surahList.value.some(s => s.number === settingsStartSurah.value)) {
    settingsStartSurah.value = currentNumber
    settingsStartAyah.value = currentAyahNumber.value
    settingsEndSurah.value = currentNumber
    settingsEndAyah.value = currentAyahNumber.value
  }
  normalizeMurottalRange()
  settingsPickerType.value = 'none'
  settingsSurahSearch.value = ''
  // Do NOT pause — murotal keeps playing while settings is open
  showAudioSettings.value = true
}

const selectSettingsSurah = (number: number) => {
  if (settingsPickerType.value === 'startSurah') { settingsStartSurah.value = number; settingsStartAyah.value = 1; if (settingsEndSurah.value < number) settingsEndSurah.value = number }
  else { settingsEndSurah.value = number; settingsEndAyah.value = settingsEndTotalAyah.value }
  normalizeMurottalRange()
  settingsPickerType.value = 'none'
  settingsSurahSearch.value = ''
}
const selectSettingsAyah = (number: number) => { if (settingsPickerType.value === 'startAyah') settingsStartAyah.value = number; else settingsEndAyah.value = number; normalizeMurottalRange(); settingsPickerType.value = 'none' }
const selectSettingsRepeat = (value: number) => { if (settingsPickerType.value === 'ayahRepeat') settingsAyahRepeat.value = value; else settingsRangeRepeat.value = value; settingsPickerType.value = 'none' }
const generateMurottalQueue = () => {
  const queue: { surah: number; ayah: number; verse_key: string }[] = []
  normalizeMurottalRange()
  for (let number = settingsStartSurah.value; number <= settingsEndSurah.value; number++) {
    const meta = surahList.value.find(s => s.number === number)
    if (!meta) continue
    const first = number === settingsStartSurah.value ? settingsStartAyah.value : 1
    const last = number === settingsEndSurah.value ? settingsEndAyah.value : meta.total_ayah
    for (let ayah = first; ayah <= last; ayah++) queue.push({ surah: number, ayah, verse_key: `${number}:${ayah}` })
  }
  return queue
}

const navigateToMurottalVerse = async (verse: { surah: number; ayah: number }) => {
  const meta = surahList.value.find(s => s.number === verse.surah)
  if (!meta) return
  if (meta.id === surahId.value) {
    if (!listeningAyahs.value.length) await fetchListeningAyahs()
    await syncListeningAyahState(verse.ayah, { restartAudio: true })
    return
  }
  await router.replace(buildRemoteRoute(meta.id, verse.ayah))
}

const startCustomRangePlayback = async () => {
  const queue = generateMurottalQueue()
  if (!queue.length) return
  activeMurottalQueue.value = queue
  queueIndex.value = 0
  currentAyahRepeatCount.value = 1
  currentRangeRepeatCount.value = 1
  isCustomRangeActive.value = true
  showAudioSettings.value = false
  stopAudio()
  await navigateToMurottalVerse(queue[0])
}

const toggleListeningRepeat = () => {
  if (isCustomRangeActive.value) {
    isCustomRangeActive.value = false
    activeMurottalQueue.value = []
    stopAudio()
    return
  }
  openAudioSettings()
}

const handleCustomRangeEnded = async () => {
  if (currentAyahRepeatCount.value < settingsAyahRepeat.value) {
    currentAyahRepeatCount.value += 1
    startAudioPlayback({ restart: true })
    return
  }
  currentAyahRepeatCount.value = 1
  if (queueIndex.value < activeMurottalQueue.value.length - 1) {
    queueIndex.value += 1
    await navigateToMurottalVerse(activeMurottalQueue.value[queueIndex.value])
    return
  }
  if (currentRangeRepeatCount.value < settingsRangeRepeat.value) {
    currentRangeRepeatCount.value += 1
    queueIndex.value = 0
    await navigateToMurottalVerse(activeMurottalQueue.value[0])
    return
  }
  isCustomRangeActive.value = false
  showToast?.('Rentang murottal selesai', 'fluent')
}

const openQariPicker = () => {
  triggerHaptic(40)
  // Do NOT pause — qari list opens while murotal keeps playing; only stops when new qari is selected
  showQariPicker.value = true
}

const closeQariPicker = () => {
  triggerHaptic(40)
  showQariPicker.value = false
}

const selectQari = (qariId: string) => {
  triggerHaptic(50)
  const wasPlaying = isPlaying.value
  selectedQari.value = qariId
  showQariPicker.value = false
  
  if (wasPlaying) {
    stopAudio()
    playAudio()
  } else {
    stopAudio()
  }
}

// Audio Player
let audioObj: HTMLAudioElement | null = null
let autoAdvanceTimer: ReturnType<typeof setTimeout> | null = null

const clearAutoAdvanceTimer = () => {
  if (autoAdvanceTimer) {
    clearTimeout(autoAdvanceTimer)
    autoAdvanceTimer = null
  }
}

const goToNextListeningTarget = async () => {
  if (currentAyahNumber.value < totalAyah.value) {
    return await syncListeningAyahState(currentAyahNumber.value + 1, { restartAudio: true })
  }

  if (!nextSurah.value) {
    showToast?.('Murottal selesai diputar', 'fluent')
    return false
  }

  await router.replace(buildRemoteRoute(nextSurah.value.id, 1))
  return true
}

const scheduleAutoAdvance = () => {
  clearAutoAdvanceTimer()

  if (!shouldAutoplayAudio.value || !shouldAutoAdvance.value) return

  autoAdvanceTimer = setTimeout(async () => {
    autoAdvanceTimer = null
    if (autoAdvancing.value) return

    autoAdvancing.value = true

    try {
      await goToNextListeningTarget()
    } finally {
      autoAdvancing.value = false
    }
  }, 900)
}

const pauseAudio = () => {
  clearAutoAdvanceTimer()
  if (audioObj && isPlaying.value) {
    audioObj.pause()
  }
  isPlaying.value = false
}

const stopAudio = () => {
  clearAutoAdvanceTimer()
  if (audioObj) {
    audioObj.onplay = null
    audioObj.onended = null
    audioObj.onerror = null
    audioObj.ontimeupdate = null
    audioObj.onloadedmetadata = null
    audioObj.pause()
    audioObj.src = ''
  }
  audioCurrentTime.value = 0
  audioDuration.value = 0
  isPlaying.value = false
}

const startAudioPlayback = (options: { withHaptic?: boolean, restart?: boolean } = {}) => {
  if (isUnmounted) return

  const { withHaptic = false, restart = false } = options

  if (withHaptic) {
    triggerHaptic(50)
  }

  if (isPlaying.value && audioObj && !restart) {
    return
  }

  if (!audioObj) {
    audioObj = new Audio()
  }

  if (audioObj && restart) {
    audioObj.pause()
  }

  const pad = (num: number, size: number) => {
    let s = num + ""
    while (s.length < size) s = "0" + s
    return s
  }

  const audioUrl = `https://everyayah.com/data/${selectedQari.value}/${pad(surahId.value, 3)}${pad(currentAyahNumber.value, 3)}.mp3`
  audioObj.src = audioUrl
  audioObj.load()

  audioObj.onplay = () => {
    isPlaying.value = true
  }
  audioObj.ontimeupdate = () => {
    audioCurrentTime.value = audioObj?.currentTime || 0
  }
  audioObj.onloadedmetadata = () => {
    audioDuration.value = Number.isFinite(audioObj?.duration) ? (audioObj?.duration || 0) : 0
  }
  audioObj.onended = () => {
    isPlaying.value = false
    audioCurrentTime.value = audioDuration.value || audioCurrentTime.value
    if (isCustomRangeActive.value) {
      void handleCustomRangeEnded()
    } else {
      scheduleAutoAdvance()
    }
  }
  audioObj.onerror = () => {
    isPlaying.value = false
    showToast?.('Gagal memuat suara Qori', 'forgot')
  }

  audioObj.play().catch(err => {
    isPlaying.value = false
    if (err.name === 'NotAllowedError') {
      console.warn('Autoplay blocked by browser. User interaction required to play audio.')
    } else {
      console.error('Playback error:', err)
    }
  })
}

const formatPlayerTime = (seconds: number) => {
  if (!Number.isFinite(seconds) || seconds < 0) return '0:00'
  const mins = Math.floor(seconds / 60)
  const secs = Math.floor(seconds % 60)
  return `${mins}:${secs.toString().padStart(2, '0')}`
}

const playerCurrentTimeLabel = computed(() => formatPlayerTime(audioCurrentTime.value))
const playerDurationLabel = computed(() => formatPlayerTime(audioDuration.value))

const scrollListeningAyahIntoView = async (behavior: ScrollBehavior = 'smooth') => {
  if (!isListeningMode.value) return
  await nextTick()
  const container = remoteContentRef.value
  const active = container?.querySelector(`[data-ayah="${currentAyahNumber.value}"]`) as HTMLElement | null
  if (!container || !active) return

  const blockPosition: ScrollLogicalPosition = active.offsetHeight > container.clientHeight * 0.82 ? 'start' : 'center'
  active.scrollIntoView({ block: blockPosition, inline: 'nearest', behavior })
}

const fetchListeningAyahs = async () => {
  if (!isListeningMode.value) return [] as AyahData[]
  listeningAyahsLoading.value = true
  try {
    const res = await apiFetch<{ data: AyahData[] }>(`/surahs/${surahId.value}/ayahs`)
    listeningAyahs.value = res.data
    await scrollListeningAyahIntoView('auto')
    return res.data
  } catch (e) {
    console.error('Failed to load listening ayahs:', e)
    return [] as AyahData[]
  } finally {
    listeningAyahsLoading.value = false
  }
}

const initializeListeningAyahs = async (targetAyahNum: number) => {
  await fetchListeningAyahs()
  await syncListeningAyahState(targetAyahNum)
  await fetchAyah(targetAyahNum, { skipAutoplay: true, skipScroll: true })

  if (shouldAutoplayAudio.value && listeningAyahs.value.length) {
    startAudioPlayback({ restart: true })
  }
}

const playAudio = () => {
  triggerHaptic(50)

  if (isPlaying.value && audioObj) {
    audioObj.pause()
    isPlaying.value = false
    return
  }

  startAudioPlayback()
}
onUnmounted(() => {
  isUnmounted = true
  stopAudio()
  cleanupVoice()
})

const currentSurahMeta = computed(() => surahList.value.find(s => s.id === surahId.value) || null)
const surahName = computed(() => currentAyah.value?.surah_name || lastSurahSnapshot.value?.name_latin || currentSurahMeta.value?.name_latin || '...')
const surahNumber = computed(() => currentAyah.value?.surah_number || lastSurahSnapshot.value?.number || currentSurahMeta.value?.number || surahId.value)
const totalAyah = computed(() => currentAyah.value?.total_ayah || lastSurahSnapshot.value?.total_ayah || currentSurahMeta.value?.total_ayah || 0)
const isListeningMode = computed(() => sessionMode.value === 'listening')
const isHiddenState = computed(() => !isListeningMode.value && !isRevealed.value)
const shouldAutoplayAudio = computed(() => isListeningMode.value)
const shouldAutoAdvance = computed(() => isListeningMode.value)
const remoteRouteQuery = computed(() => ({ mode: sessionMode.value }))

const showFirstWordHint = ref(false)
const firstArabicWord = computed(() => {
  const text = currentAyah.value?.text_arabic || ''
  if (!text) return ''
  // Same stripping logic as formatArabicText:
  // Tajweed markup pattern is [rulename[char] (single closing bracket)
  // e.g. [i:61[صُ] → صُ  |  [h[مٌّ] → مٌّ
  const stripped = text
    .replace(/^\uFEFF/, '')                                                  // BOM
    .replace(/\u0672/g, '\u0670')                                            // normalize alef
    .replace(/[\u0610-\u061A\u06D6-\u06E4\u06E7-\u06ED\u08A0-\u08FF]/g, '') // annotation marks
    .replace(/\[([a-z0-9:]+)\[([^\]]*)\]/g, '$2')                           // [rule[char] → char
    .replace(/[\u0600-\u0605\u06DD]/g, '')                                   // Quran control chars
    .trim()
  return stripped.split(/\s+/)[0] || ''
})

// Voice recording variables for self-murojaah
const voiceState = ref<'idle' | 'recording' | 'review'>('idle')
const mediaRecorder = ref<MediaRecorder | null>(null)
const voiceBlobUrl = ref<string | null>(null)
const voiceAudio = ref<HTMLAudioElement | null>(null)
const isPlayingVoice = ref(false)
const voiceDuration = ref(0)
const voicePlayProgress = ref(0)
const voiceMimeType = ref('audio/webm') // Actual recorded mime type (varies by browser)
const MAX_RECORDING_SECONDS = 60

const isHoldingRecord = ref(false)
const isRecordingLocked = ref(false)
const isRecordingCancelled = ref(false)
const recordDragX = ref(0)
const recordDragY = ref(0)

const micPermission = ref<'prompt' | 'granted' | 'denied'>('prompt')

const checkMicPermission = async () => {
  if (typeof navigator === 'undefined' || !navigator.permissions || !navigator.permissions.query) {
    return
  }
  try {
    const result = await navigator.permissions.query({ name: 'microphone' as PermissionName })
    micPermission.value = result.state
    result.onchange = () => {
      micPermission.value = result.state
    }
  } catch (e) {
    console.warn('Permissions API not fully supported:', e)
  }
}

let initialPointerX = 0
let initialPointerY = 0
let pointerDownTime = 0


const voiceDurationLabel = computed(() => {
  const s = voiceDuration.value
  const mins = Math.floor(s / 60)
  const secs = s % 60
  return `${mins}:${String(secs).padStart(2, '0')}`
})

const currentVoiceKey = computed(() => `recording-${surahId.value}-${currentAyahNumber.value}`)

// ── IndexedDB helpers for persisting voice recordings ──
const IDB_NAME = 'murojaah-recordings'
const IDB_VERSION = 1
const IDB_STORE = 'recordings'
const RECORDING_TTL_MS = 7 * 24 * 60 * 60 * 1000 // 7 days
let idb: IDBDatabase | null = null

const openRecordingDB = (): Promise<IDBDatabase> => {
  return new Promise((resolve, reject) => {
    if (idb) { resolve(idb); return }
    const req = indexedDB.open(IDB_NAME, IDB_VERSION)
    req.onerror = () => reject(req.error)
    req.onsuccess = () => { idb = req.result; resolve(idb) }
    req.onupgradeneeded = (e) => {
      const db = (e.target as IDBOpenDBRequest).result
      if (!db.objectStoreNames.contains(IDB_STORE)) {
        db.createObjectStore(IDB_STORE, { keyPath: 'key' })
      }
    }
  })
}

const saveVoiceRecording = async (key: string, blob: Blob, duration: number) => {
  try {
    const db = await openRecordingDB()
    const tx = db.transaction(IDB_STORE, 'readwrite')
    tx.objectStore(IDB_STORE).put({ key, blob, duration, savedAt: Date.now() })
  } catch (e) { console.error('Failed to save recording:', e) }
}

const loadVoiceRecording = async (key: string): Promise<{ blob: Blob; duration: number } | null> => {
  try {
    const db = await openRecordingDB()
    return new Promise((resolve) => {
      const req = db.transaction(IDB_STORE, 'readonly').objectStore(IDB_STORE).get(key)
      req.onsuccess = () => {
        const r = req.result
        if (!r || Date.now() - r.savedAt > RECORDING_TTL_MS) { resolve(null); return }
        resolve({ blob: r.blob, duration: r.duration })
      }
      req.onerror = () => resolve(null)
    })
  } catch { return null }
}

const deleteVoiceRecording = async (key: string) => {
  try {
    const db = await openRecordingDB()
    db.transaction(IDB_STORE, 'readwrite').objectStore(IDB_STORE).delete(key)
  } catch (e) { console.error('Failed to delete recording:', e) }
}

let audioChunks: Blob[] = []
let recordingInterval: any = null
let permissionRequestInProgress = false

const onRecordPointerDown = (e: PointerEvent) => {
  e.preventDefault()
  cleanupRecordGesture()
  triggerHaptic(50)
  
  isHoldingRecord.value = true
  isRecordingLocked.value = false
  isRecordingCancelled.value = false
  recordDragX.value = 0
  recordDragY.value = 0
  
  initialPointerX = e.clientX
  initialPointerY = e.clientY
  pointerDownTime = Date.now()
  
  // Attach pointer listeners immediately so pointerup/pointercancel
  // are always handled, even during the mic permission request.
  window.addEventListener('pointermove', onRecordPointerMove)
  window.addEventListener('pointerup', onRecordPointerUp)
  window.addEventListener('pointercancel', onRecordPointerCancel)
  
  startRecording()
}

const onRecordPointerMove = (e: PointerEvent) => {
  if (!isHoldingRecord.value || isRecordingLocked.value || isRecordingCancelled.value) return
  
  const deltaX = e.clientX - initialPointerX
  const deltaY = e.clientY - initialPointerY
  
  // Cap the horizontal drag to -150px so it stays visible
  recordDragX.value = Math.max(-150, Math.min(0, deltaX))
  recordDragY.value = Math.min(0, deltaY)
  
  // Slide Up to Lock (threshold 80px)
  if (recordDragY.value < -80) {
    isRecordingLocked.value = true
    triggerHaptic(45)
    cleanupRecordGesture()
  }
}

const onRecordPointerUp = (e: PointerEvent) => {
  if (!isHoldingRecord.value) return
  
  const elapsed = Date.now() - pointerDownTime
  
  // Quick tap check
  if (elapsed < 300) {
    isRecordingCancelled.value = true
    stopRecording()
    cleanupRecordGesture()
    showToast?.('Tahan tombol untuk merekam', 'fluent')
    return
  }
  
  // Check if finger was released in the Cancel Zone (dragged left past -110px)
  if (recordDragX.value < -110) {
    isRecordingCancelled.value = true
    triggerHaptic(60)
    stopRecording()
    showToast?.('Rekaman dibatalkan', 'doubtful')
  } else if (!isRecordingLocked.value && !isRecordingCancelled.value) {
    stopRecording()
  }
  
  cleanupRecordGesture()
}

const onRecordPointerCancel = () => {
  isRecordingCancelled.value = true
  stopRecording()
  cleanupRecordGesture()
}

const cleanupRecordGesture = () => {
  isHoldingRecord.value = false
  window.removeEventListener('pointermove', onRecordPointerMove)
  window.removeEventListener('pointerup', onRecordPointerUp)
  window.removeEventListener('pointercancel', onRecordPointerCancel)
}

const startRecording = async () => {
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true })
    checkMicPermission()
    audioChunks = []
    
    // Stop any existing main audio playback
    pauseAudio()
    if (isPlayingVoice.value && voiceAudio.value) {
      voiceAudio.value.pause()
      isPlayingVoice.value = false
    }

    const options = { mimeType: 'audio/webm' }
    let recorder: MediaRecorder
    try {
      recorder = new MediaRecorder(stream, options)
    } catch (err) {
      recorder = new MediaRecorder(stream) // Fallback for browsers with strict codecs (like Safari)
    }
    
    mediaRecorder.value = recorder
    
    recorder.ondataavailable = (e) => {
      if (e.data.size > 0) {
        audioChunks.push(e.data)
      }
    }
    
    recorder.onstop = async () => {
      // Stop media tracks to turn off mic indicator
      stream.getTracks().forEach(track => track.stop())

      if (isRecordingCancelled.value) {
        audioChunks = []
        voiceState.value = 'idle'
        return
      }

      const mimeType = recorder.mimeType || 'audio/webm'
      voiceMimeType.value = mimeType
      const blob = new Blob(audioChunks, { type: mimeType })
      if (voiceBlobUrl.value) URL.revokeObjectURL(voiceBlobUrl.value)
      voiceBlobUrl.value = URL.createObjectURL(blob)
      voiceAudio.value = null // Reset so a fresh element is created on play (fixes volume inconsistency)
      voiceState.value = 'review'
      voicePlayProgress.value = 0

      // Persist to IndexedDB so it survives page refresh
      await saveVoiceRecording(currentVoiceKey.value, blob, voiceDuration.value)
    }

    voiceState.value = 'recording'
    voiceDuration.value = 0
    const startTime = Date.now()
    recordingInterval = setInterval(() => {
      voiceDuration.value = Math.round((Date.now() - startTime) / 1000)
      // Auto-stop after MAX_RECORDING_SECONDS
      if (voiceDuration.value >= MAX_RECORDING_SECONDS) {
        stopRecording()
        showToast?.(`Rekaman otomatis berhenti (maks. ${MAX_RECORDING_SECONDS} detik)`, 'doubtful')
      }
    }, 1000)

    recorder.start()
  } catch (err) {
    console.error('Microphone access denied:', err)
    showToast?.('Gagal mengakses mikrofon. Pastikan izin mikrofon diberikan.', 'forgot')
    cleanupRecordGesture()
  }
}

const stopRecording = () => {
  if (recordingInterval) {
    clearInterval(recordingInterval)
  }
  if (mediaRecorder.value && mediaRecorder.value.state !== 'inactive') {
    mediaRecorder.value.stop()
  }
}

const togglePlayVoice = () => {
  if (!voiceBlobUrl.value) return

  if (isPlayingVoice.value && voiceAudio.value) {
    voiceAudio.value.pause()
    isPlayingVoice.value = false
    return
  }

  // Always create a fresh Audio element to prevent volume inconsistency bug
  if (voiceAudio.value) {
    voiceAudio.value.pause()
    voiceAudio.value.ontimeupdate = null
    voiceAudio.value.onended = null
  }
  voiceAudio.value = new Audio(voiceBlobUrl.value)
  voiceAudio.value.ontimeupdate = () => {
    const audio = voiceAudio.value
    if (audio && audio.duration && !isNaN(audio.duration)) {
      voicePlayProgress.value = (audio.currentTime / audio.duration) * 100
    }
  }
  voiceAudio.value.onended = () => {
    isPlayingVoice.value = false
    voicePlayProgress.value = 0
  }

  // Stop main qari player before playing own voice
  pauseAudio()

  voiceAudio.value.play().catch((err) => {
    console.error('Voice playback error:', err)
    isPlayingVoice.value = false
  })
  isPlayingVoice.value = true
}

const deleteVoice = async () => {
  await deleteVoiceRecording(currentVoiceKey.value)
  cleanupVoice()
}

const downloadVoice = () => {
  if (!voiceBlobUrl.value) return
  triggerHaptic(40)
  // Determine correct file extension based on actual recorded mime type
  const mime = voiceMimeType.value
  let ext = 'webm'
  if (mime.includes('mp4') || mime.includes('m4a')) ext = 'm4a'
  else if (mime.includes('ogg')) ext = 'ogg'
  else if (mime.includes('wav')) ext = 'wav'
  const a = document.createElement('a')
  a.href = voiceBlobUrl.value
  a.download = `murojaah-${surahName.value}-ayat-${currentAyahNumber.value}.${ext}`
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  showToast?.('Rekaman berhasil diunduh!', 'fluent')
}

// cleanupVoice: only clears in-memory/UI state — does NOT delete from IndexedDB
const cleanupVoice = () => {
  if (recordingInterval) clearInterval(recordingInterval)
  if (mediaRecorder.value && mediaRecorder.value.state !== 'inactive') {
    mediaRecorder.value.stop()
  }
  if (voiceAudio.value) {
    voiceAudio.value.pause()
    voiceAudio.value.ontimeupdate = null
    voiceAudio.value.onended = null
    voiceAudio.value = null
  }
  if (voiceBlobUrl.value) {
    URL.revokeObjectURL(voiceBlobUrl.value)
    voiceBlobUrl.value = null
  }
  voiceState.value = 'idle'
  isPlayingVoice.value = false
  voiceDuration.value = 0
  voicePlayProgress.value = 0
}

const buildRemoteRoute = (targetSurahId: number, targetAyahNumber: number) => ({
  path: `/remote/${targetSurahId}/${targetAyahNumber}`,
  query: remoteRouteQuery.value,
})

const replaceRemoteUrlLocally = (targetSurahId: number, targetAyahNumber: number) => {
  if (typeof window === 'undefined') return
  const resolved = router.resolve(buildRemoteRoute(targetSurahId, targetAyahNumber))
  window.history.replaceState(window.history.state, '', resolved.fullPath)
}

const syncListeningAyahState = async (targetAyahNumber: number, options: { restartAudio?: boolean } = {}) => {
  const targetAyah = listeningAyahs.value.find(ayah => ayah.ayah_number === targetAyahNumber)
  if (!targetAyah) return false

  currentAyahNumber.value = targetAyahNumber
  currentAyah.value = targetAyah
  displayAyah.value = targetAyah
  lastSurahSnapshot.value = {
    name_latin: targetAyah.surah_name,
    number: targetAyah.surah_number,
    total_ayah: targetAyah.total_ayah,
  }
  syncRevealState()
  replaceRemoteUrlLocally(surahId.value, targetAyahNumber)
  await scrollListeningAyahIntoView()

  if (options.restartAudio && shouldAutoplayAudio.value) {
    startAudioPlayback({ restart: true })
  }

  return true
}

const getDesiredRevealState = () => {
  if (sessionMode.value === 'listening') {
    return true
  }

  return learningRevealMode.value === 'revealed'
}

const syncRevealState = () => {
  isRevealed.value = getDesiredRevealState()
}

// Dynamic font size depending on length of Arabic text to prevent wrapping issues
const dynamicFontSizeClass = computed(() => {
  if (!currentAyah.value) return 'text-arabic-xl'
  const text = currentAyah.value.text_arabic || ''
  const len = text.length
  if (len > 120) return 'text-arabic-md'
  if (len > 60) return 'text-arabic-lg'
  return 'text-arabic-xl'
})

// Helper for synthesized click sound (Web Audio API)
let audioCtx: AudioContext | null = null
const playTick = (type: 'fluent' | 'doubtful' | 'forgot' | 'normal' = 'normal') => {
  if (typeof window === 'undefined') return
  try {
    const AudioContextClass = window.AudioContext || (window as any).webkitAudioContext
    if (!AudioContextClass) return

    // Initialize lazily to comply with autoplay policy
    if (!audioCtx) {
      audioCtx = new AudioContextClass()
    }

    // Resume context if suspended (iOS Safari requires this)
    if (audioCtx.state === 'suspended') {
      audioCtx.resume()
    }

    const now = audioCtx.currentTime
    const osc = audioCtx.createOscillator()
    const gain = audioCtx.createGain()

    osc.connect(gain)
    gain.connect(audioCtx.destination)

    if (type === 'fluent') {
      // Gentle chime/pop for Lancar
      osc.type = 'sine'
      osc.frequency.setValueAtTime(700, now)
      osc.frequency.exponentialRampToValueAtTime(1000, now + 0.05)

      gain.gain.setValueAtTime(0, now)
      gain.gain.linearRampToValueAtTime(0.06, now + 0.015)
      gain.gain.exponentialRampToValueAtTime(0.001, now + 0.05)

      osc.start(now)
      osc.stop(now + 0.05)
    } else if (type === 'doubtful') {
      // Soft mid tick
      osc.type = 'triangle'
      osc.frequency.setValueAtTime(350, now)
      osc.frequency.exponentialRampToValueAtTime(150, now + 0.04)

      gain.gain.setValueAtTime(0, now)
      gain.gain.linearRampToValueAtTime(0.08, now + 0.01)
      gain.gain.exponentialRampToValueAtTime(0.001, now + 0.04)

      osc.start(now)
      osc.stop(now + 0.04)
    } else if (type === 'forgot') {
      // Lower soft thud
      osc.type = 'sine'
      osc.frequency.setValueAtTime(180, now)
      osc.frequency.exponentialRampToValueAtTime(80, now + 0.06)

      gain.gain.setValueAtTime(0, now)
      gain.gain.linearRampToValueAtTime(0.12, now + 0.015)
      gain.gain.exponentialRampToValueAtTime(0.001, now + 0.06)

      osc.start(now)
      osc.stop(now + 0.06)
    } else {
      // Standard mechanical tick
      osc.type = 'sine'
      osc.frequency.setValueAtTime(1400, now)
      osc.frequency.exponentialRampToValueAtTime(200, now + 0.015)

      gain.gain.setValueAtTime(0, now)
      gain.gain.linearRampToValueAtTime(0.04, now + 0.003)
      gain.gain.exponentialRampToValueAtTime(0.001, now + 0.015)

      osc.start(now)
      osc.stop(now + 0.015)
    }
  } catch (err) {
    console.warn('Web Audio tap sound failed:', err)
  }
}

// Helper for haptic vibration and sound
const triggerHaptic = (ms = 40, soundType: 'fluent' | 'doubtful' | 'forgot' | 'normal' = 'normal') => {
  if (typeof navigator !== 'undefined' && navigator.vibrate) {
    const hasActivation = typeof navigator.userActivation !== 'undefined' 
      ? navigator.userActivation.hasBeenActive 
      : true
    if (hasActivation) {
      try {
        navigator.vibrate(ms)
      } catch (e) {}
    }
  }
  playTick(soundType)
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
let isScrolling = false
const onTouchStart = (e: TouchEvent) => {
  // Jika pop-up picker sedang terbuka, jangan aktifkan swipe gesture halaman utama
  if (showSurahPicker.value || showAyahPicker.value || navigatorOpen.value) return
  touchStartX = e.touches[0].clientX
  touchStartY = e.touches[0].clientY
  isScrolling = false
}

const onTouchMove = (e: TouchEvent) => {
  if (showSurahPicker.value || showAyahPicker.value || navigatorOpen.value) return
  const diffX = Math.abs(e.touches[0].clientX - touchStartX)
  const diffY = Math.abs(e.touches[0].clientY - touchStartY)
  // Jika jari bergeser lebih dari 10px, anggap sedang melakukan scroll/drag
  if (diffX > 10 || diffY > 10) {
    isScrolling = true
  }
}
const onTouchEnd = (e: TouchEvent) => {
  // Jika pop-up picker sedang terbuka, jangan aktifkan swipe gesture halaman utama
  if (showSurahPicker.value || showAyahPicker.value || navigatorOpen.value) return

  const diffX = e.changedTouches[0].clientX - touchStartX
  const diffY = e.changedTouches[0].clientY - touchStartY

  // Hanya aktifkan swipe horizontal (kiri/kanan) untuk ganti ayat.
  // Swipe vertikal (atas/bawah) kita matikan agar tidak memicu ganti surat secara tidak sengaja saat men-scroll atau mengetuk layar.
  if (Math.abs(diffX) > Math.abs(diffY)) {
    if (Math.abs(diffX) > 75) {
      if (diffX > 0 && currentAyahNumber.value > 1) prevAyah()
      else if (diffX < 0 && (currentAyahNumber.value < totalAyah.value || (isListeningMode.value && nextSurah.value))) skipAyah()
    }
  }
}

const fetchAyah = async (ayahNum: number, options: { skipAutoplay?: boolean, skipScroll?: boolean } = {}) => {
  const { skipAutoplay = false, skipScroll = false } = options

  try {
    syncRevealState()

    if (!skipAutoplay && shouldAutoplayAudio.value) {
      setTimeout(() => {
        if (currentAyahNumber.value === ayahNum && shouldAutoplayAudio.value) {
          startAudioPlayback({ restart: true })
        }
      }, 10)
    }

    const res = await apiFetch<{ data: AyahData }>(`/surahs/${surahId.value}/ayahs/${ayahNum}`)
    currentAyah.value = res.data
    displayAyah.value = res.data
    if (listeningAyahs.value.length) {
      listeningAyahs.value = listeningAyahs.value.map(ayah =>
        ayah.ayah_number === res.data.ayah_number ? { ...ayah, ...res.data } : ayah
      )
    }
    lastSurahSnapshot.value = {
      name_latin: res.data.surah_name,
      number: res.data.surah_number,
      total_ayah: res.data.total_ayah,
    }

    syncRevealState()
    if (isListeningMode.value && !skipScroll) {
      await scrollListeningAyahIntoView()
    }

    // Load saved recording from IndexedDB (only in Uji Hafalan mode)
    if (!isListeningMode.value) {
      const saved = await loadVoiceRecording(`recording-${surahId.value}-${ayahNum}`)
      if (saved) {
        if (voiceBlobUrl.value) URL.revokeObjectURL(voiceBlobUrl.value)
        voiceBlobUrl.value = URL.createObjectURL(saved.blob)
        voiceDuration.value = saved.duration
        voiceAudio.value = null
        voiceState.value = 'review'
        voicePlayProgress.value = 0
      }
    }
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
  // Jika user sedang men-scroll teks yang panjang, jangan sembunyikan ayat
  if (isScrolling) return
  triggerHaptic(35)

  const nextRevealState = !isRevealed.value
  isRevealed.value = nextRevealState

  if (isListeningMode.value) {
    listeningRevealOverride.value = nextRevealState
  }

  // Dalam mode mendengarkan, tampil/sembunyi ayat tidak menghentikan audio yang sedang berjalan.
  if (nextRevealState && shouldAutoplayAudio.value) {
    startAudioPlayback()
  } else if (!nextRevealState && !isListeningMode.value) {
    stopAudio()
  }
}
const hideAyah = () => {
  triggerHaptic(30)
  isRevealed.value = false

  if (isListeningMode.value) {
    listeningRevealOverride.value = false
  }

  if (!isListeningMode.value) {
    stopAudio()
  }
}

const openSurahPicker = () => {
  triggerHaptic(40)
  // Do NOT pause — murotal keeps playing while surah picker is open
  pickerSearch.value = ''
  pendingPickerSurahId.value = null
  showSurahPicker.value = true
}

const openNavigator = async () => {
  triggerHaptic(40)
  // Do NOT pause — murotal keeps playing while navigator is open
  if (!surahList.value.length) await fetchSurahList()
  // Pre-select current surah + ayah
  navigatorSurahId.value = surahId.value
  navigatorSelectedAyah.value = currentAyahNumber.value
  navigatorSurahSearch.value = ''
  navigatorShowSurahPicker.value = false
  navigatorShowAyahPicker.value = false
  navigatorOpen.value = true
}

const closeNavigator = () => {
  navigatorOpen.value = false
  navigatorShowSurahPicker.value = false
  navigatorShowAyahPicker.value = false
}

const chooseNavigatorSurah = (s: SurahItem) => {
  triggerHaptic(40)
  navigatorSurahId.value = s.id
  navigatorSelectedAyah.value = 1
  navigatorShowSurahPicker.value = false
}

const chooseNavigatorAyah = (n: number) => {
  triggerHaptic(40)
  navigatorSelectedAyah.value = n
  navigatorShowAyahPicker.value = false
}

const confirmNavigation = async () => {
  const targetSurahId = navigatorSurahId.value
  const targetAyah = navigatorSelectedAyah.value
  closeNavigator()
  stopAudio()

  pendingPickerSurahId.value = targetSurahId !== surahId.value ? targetSurahId : null
  await changeAyah(targetAyah)
}

const closeSurahPicker = () => {
  triggerHaptic(40)
  showSurahPicker.value = false
}

const openAyahPicker = () => {
  triggerHaptic(40)
  // Do NOT pause — murotal keeps playing while ayah picker is open
  showAyahPicker.value = true
}

const closeAyahPicker = () => {
  triggerHaptic(40)
  showAyahPicker.value = false
  pendingPickerSurahId.value = null
}

const changeSurah = (targetSurahId: number) => {
  triggerHaptic(50)
  stopAudio()
  showSurahPicker.value = false
  router.push(buildRemoteRoute(targetSurahId, 1))
}

const handleSurahSelect = (id: number) => {
  if (isWheelDragging.value) return
  triggerHaptic(45)
  pendingPickerSurahId.value = id
  showSurahPicker.value = false
  showAyahPicker.value = true
}

const changeAyah = async (targetAyahNum: number) => {
  triggerHaptic(50)
  stopAudio()
  const targetSurahId = pendingPickerSurahId.value || surahId.value
  pendingPickerSurahId.value = null
  showAyahPicker.value = false

  if (targetSurahId !== surahId.value) {
    await router.push(buildRemoteRoute(targetSurahId, targetAyahNum))
    return
  }

  if (isListeningMode.value) {
    const changed = await syncListeningAyahState(targetAyahNum)
    if (changed) return
  }

  currentAyahNumber.value = targetAyahNum
  router.replace(buildRemoteRoute(surahId.value, targetAyahNum))
}

const jumpToHeaderAyah = (event: Event) => {
  const value = Number((event.target as HTMLSelectElement).value)
  if (value >= 1 && value <= totalAyah.value) void changeAyah(value)
}
const submitReview = async (status: 'forgot' | 'doubtful' | 'fluent') => {
  if (!currentAyah.value || submitting.value) return
  stopAudio()
  submitting.value = true

  // Flash feedback
  flashStatus.value = status
  setTimeout(() => { flashStatus.value = null }, 350)

  // Haptic & sound feedback
  triggerHaptic(60, status)

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

    showToast?.(`Ayat ${currentAyahNumber.value} - ${statusLabels[status]}`, status)

    // Auto advance to next ayah
    if (currentAyahNumber.value < totalAyah.value) {
      currentAyahNumber.value++
      await router.replace(buildRemoteRoute(surahId.value, currentAyahNumber.value))
    } else {
      showToast?.('Surat selesai!', 'fluent')
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
  stopAudio()

  if (isListeningMode.value) {
    const changed = await syncListeningAyahState(currentAyahNumber.value - 1, { restartAudio: true })
    if (changed) return
  }

  currentAyahNumber.value--
  await router.replace(buildRemoteRoute(surahId.value, currentAyahNumber.value))
}

const skipAyah = async () => {
  if (isListeningMode.value) {
    await goToNextListeningTarget()
    return
  }

  // Clicking "Next" (Selanjutnya) or swiping left defaults to logging a "Lancar" (fluent) review
  await submitReview('fluent')
}

const goBack = () => {
  triggerHaptic(40)
  stopAudio()
  router.push({ path: `/surahs/${surahId.value}`, query: { mode: sessionMode.value } })
}

// Parse Quran Tajweed API markup into color-coded HTML spans
const formatArabicText = (text: string) => {
  if (!text) return ''

  // Clean BOM character if any
  let cleanText = text.replace(/^\uFEFF/, '')

  // Replace U+0672 (Arabic letter Alef with wavy hamza above) which renders as a dotted circle
  // with the standard U+0670 (Arabic superscript alif / dagger alif) supported by Uthmanic Hafs font
  cleanText = cleanText.replace(/\u0672/g, '\u0670')

  // Strip all known Quranic annotation/waqf marks that render as black dots/blocks
  // in browsers with Uthmanic Hafs font. Covers Arabic Supplement + Extended-A.
  // Exclude \u06E5 (small waw) and \u06E6 (small ya) as they are critical pronunciation marks.
  //   U+0610ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã¢â‚¬Å“U+061A ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â Arabic Annotation Signs
  //   U+06D6ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã¢â‚¬Å“U+06E4, U+06E7ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã¢â‚¬Å“U+06ED ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â Small High/Low signs (excluding small waw & small ya)
  //   U+08A0ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã¢â‚¬Å“U+08FF ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â Arabic Extended-A small high marks for Quran
  cleanText = cleanText.replace(/[\u0610-\u061A\u06D6-\u06E4\u06E7-\u06ED\u08A0-\u08FF]/g, '')

  // Regex to match: [rule[text] or [rule:meta[text] (allowing empty text using *)
  const regex = /\[([a-z0-9:]+)\[([^\]]*)\]/g

  // WebKit (Safari dan semua browser di iOS seperti Chrome/Firefox) merusak sambungan huruf Arab
  // jika dibungkus dalam tag span. Di iOS/Safari, kita hapus tag tajwid sepenuhnya (fallback ke teks polos)
  // agar huruf Arab tampil tersambung secara rapi dan sempurna demi kelancaran membaca Al-Qur'an.
  const isWebKit = typeof navigator !== 'undefined' && (
    /iPad|iPhone|iPod/.test(navigator.userAgent) ||
    (/^((?!chrome|android).)*safari/i.test(navigator.userAgent)) ||
    (navigator.maxTouchPoints && navigator.maxTouchPoints > 2 && /Macintosh/.test(navigator.userAgent))
  )

  if (isWebKit) {
    cleanText = cleanText.replace(regex, '$2')
  } else {
    cleanText = cleanText.replace(regex, (match, ruleInfo, char) => {
      if (!char) return '' // Remove empty tags entirely instead of rendering broken brackets
      const rule = ruleInfo.split(':')[0]
      return `<span class="tajweed-${rule}">${char}</span>`
    })
  }

  return cleanText
}

// Combines Arabic text + end-of-ayah ornament into a single HTML string
// so they share one bidi text run ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â preventing the ornament from wrapping
// to its own line due to the browser's RTL/LTR bidi boundary algorithm.
const ORNAMENT_SVG = `<svg viewBox="0 0 100 100" class="ayah-ornament__svg"><rect x="22" y="22" width="56" height="56" rx="8" fill="none" stroke="currentColor" stroke-width="5" transform="rotate(45 50 50)"/><rect x="22" y="22" width="56" height="56" rx="8" fill="none" stroke="currentColor" stroke-width="5"/><circle cx="50" cy="50" r="23" fill="#FAF8F2" stroke="currentColor" stroke-width="2.5"/><circle cx="50" cy="50" r="19" fill="none" stroke="var(--color-primary-dark)" stroke-width="1.5" stroke-dasharray="3,2.5"/><circle cx="50" cy="11" r="3.5" fill="var(--color-primary-dark)"/><circle cx="50" cy="89" r="3.5" fill="var(--color-primary-dark)"/><circle cx="11" cy="50" r="3.5" fill="var(--color-primary-dark)"/><circle cx="89" cy="50" r="3.5" fill="var(--color-primary-dark)"/><circle cx="22.5" cy="22.5" r="3.5" fill="var(--color-primary-dark)"/><circle cx="77.5" cy="22.5" r="3.5" fill="var(--color-primary-dark)"/><circle cx="22.5" cy="77.5" r="3.5" fill="var(--color-primary-dark)"/><circle cx="77.5" cy="77.5" r="3.5" fill="var(--color-primary-dark)"/></svg>`

const formatArabicWithOrnament = (text: string, ayahNum: number) => {
  const arabicHtml = formatArabicText(text)
  const ornament = `<span class="ayah-ornament">${ORNAMENT_SVG}<span class="ayah-ornament__num">${ayahNum}</span></span>`
  return arabicHtml + ornament
}

const formatListeningAyahBadge = (ayahNum: number) => {
  return `<span class="ayah-ornament ayah-ornament--mini">${ORNAMENT_SVG}<span class="ayah-ornament__num">${ayahNum}</span></span>`
}

watch(() => [route.params.surahId, route.params.ayahNumber], async (newVals, oldVals) => {
  if (!isInitialized) return
  const [newSurahId, newAyahNum] = newVals
  const [oldSurahId, oldAyahNum] = oldVals || []

  if (newSurahId && newAyahNum) {
    const targetAyahNum = Number(newAyahNum)
    if (newSurahId === oldSurahId && targetAyahNum === Number(oldAyahNum)) {
      return
    }

    currentAyahNumber.value = targetAyahNum
    syncRevealState()

    if (isListeningMode.value && newSurahId !== oldSurahId) {
      await initializeListeningAyahs(targetAyahNum)
      return
    }

    await fetchAyah(targetAyahNum, { skipAutoplay: isListeningMode.value })
  }
})

watch(currentAyahNumber, async () => {
  cleanupVoice()
  showFirstWordHint.value = false
  if (isListeningMode.value) {
    await scrollListeningAyahIntoView()
  }
})

watch(isListeningMode, async (active) => {
  // Always stop audio when switching modes to prevent audio leaking
  stopAudio()
  if (active) {
    await initializeListeningAyahs(currentAyahNumber.value)
  } else {
    listeningRevealOverride.value = null
  }
  syncRevealState()
})

watch(learningRevealMode, () => {
  syncRevealState()
})

// Initial fetches
// Bottom sheet instances
const navigatorSheet = useBottomSheet({
  mode: 'dismiss',
  closeThreshold: 80,
  onClose: closeNavigator
})

const audioSettingsSheet = useBottomSheet({
  mode: 'dismiss',
  closeThreshold: 80,
  onClose: closeAudioSettings
})

const qariPickerSheet = useBottomSheet({
  mode: 'dismiss',
  closeThreshold: 80,
  onClose: closeQariPicker
})

watch(navigatorOpen, (val) => { if (val) navigatorSheet.reset() })
watch(showAudioSettings, (val) => { if (val) audioSettingsSheet.reset() })
watch(showQariPicker, (val) => { if (val) qariPickerSheet.reset() })

onMounted(async () => {
  isInitialized = true
  checkMicPermission()
  fetchSurahList()

  if (isListeningMode.value) {
    await initializeListeningAyahs(currentAyahNumber.value)
    return
  }

  fetchAyah(currentAyahNumber.value)
})

useHead({
  title: computed(() => currentAyah.value
    ? `${currentAyah.value.surah_name} ${currentAyahNumber.value} - Murojaah`
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
  width: 100%;
  max-width: 100%;
  position: relative;
  touch-action: pan-x;
  transition: background 0.35s ease;
}

/* ─── Full-page immersive dark mode when ayat is hidden ─── */
.remote-page--hidden {
  background: #032d1d;
}

.remote-page--hidden .remote-header {
  background: linear-gradient(135deg, #022318 0%, #043526 100%);
  box-shadow: none;
  border-bottom: 1px solid rgba(255,255,255,0.06);
}

.remote-page--hidden .remote-content {
  padding: 0;
  background: transparent;
  cursor: pointer;
}

.remote-page--hidden .remote-action-bar {
  background: #032d1d;
  border-top: 1px solid rgba(255,255,255,0.07);
  box-shadow: 0 -8px 24px rgba(0,0,0,0.3);
}

.remote-page--hidden .action-btn--back {
  color: rgba(255,255,255,0.5);
  background: rgba(255,255,255,0.06);
  border-color: rgba(255,255,255,0.08);
}

.remote-page--hidden .action-btn--forgot {
  color: #fca5a5;
  background: rgba(248,113,113,0.1);
  border-color: rgba(248,113,113,0.15);
}

.remote-page--hidden .action-btn--doubtful {
  color: #fcd34d;
  background: rgba(252,211,77,0.1);
  border-color: rgba(252,211,77,0.15);
}

.remote-page--hidden .action-btn--fluent {
  background: linear-gradient(135deg, #065f46, #0a9e72);
  color: white;
  border-color: transparent;
  box-shadow: 0 4px 16px rgba(10,158,114,0.4);
}


/* Header ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â 12% */
.remote-header {
  flex: 0 0 12%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: calc(var(--safe-top) + 8px) 16px 8px;
  background: linear-gradient(135deg, var(--color-primary-900) 0%, var(--color-primary-dark) 100%);
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

/* Content ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â 52% */
.remote-content {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 12px 10px;
  /* Extra bottom padding so action bar doesn't cover content */
  padding-bottom: calc(96px + var(--safe-bottom, 0px));
  cursor: pointer;
  -webkit-user-select: none;
  user-select: none;
  overflow: hidden;
  /* Smooth background transition when toggling hidden/revealed state */
  transition: background 0.4s ease;
}

/* ───── HIDDEN STATE — Clean Redesign ───── */
.remote-hidden {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 20px;
  /* Account for the fixed action bar (~80px) + safe area */
  padding: 0 32px calc(96px + env(safe-area-inset-bottom, 0px));
  touch-action: none;
  user-select: none;
}

.rh-label {
  font-size: 0.68rem;
  font-weight: 700;
  color: rgba(255, 255, 255, 0.4);
  text-transform: uppercase;
  letter-spacing: 0.12em;
}

/* Two real div circles — always symmetrical */
.rh-circle-outer {
  position: relative;
  width: 176px;
  height: 176px;
  border-radius: 50%;
  border: 1.5px solid rgba(212, 175, 55, 0.45);
  display: grid;
  place-items: center;
  flex-shrink: 0;
  animation: rhBreath 3.5s ease-in-out infinite;
}

.rh-circle-outer--recording {
  border-color: rgba(239, 68, 68, 0.4);
  animation: rhBreathRecording 2.4s ease-in-out infinite;
}

.rh-circle-inner {
  position: relative;
  z-index: 2;
  width: 132px;
  height: 132px;
  border-radius: 50%;
  border: 1px solid rgba(255, 255, 255, 0.1);
  background: rgba(255, 255, 255, 0.04);
  display: grid;
  place-items: center;
}

@keyframes rhBreath {
  0%, 100% { transform: scale(1);    border-color: rgba(212, 175, 55, 0.3); }
  50%       { transform: scale(1.05); border-color: rgba(212, 175, 55, 0.65); }
}

@keyframes rhBreathRecording {
  0%, 100% { transform: scale(1);    border-color: rgba(239, 68, 68, 0.3); }
  50%       { transform: scale(1.04); border-color: rgba(239, 68, 68, 0.55); }
}

/* Concentric audio pulse waves scaling outwards behind circle */
.rh-pulse-ring {
  position: absolute;
  inset: 0;
  border-radius: 50%;
  pointer-events: none;
  border: 2px solid rgba(239, 68, 68, 0.35);
  background: rgba(239, 68, 68, 0.04);
  z-index: 1;
}

.rh-pulse-ring--1 {
  animation: rhVoicePulseRing 2.4s cubic-bezier(0.16, 1, 0.3, 1) infinite;
}

.rh-pulse-ring--2 {
  animation: rhVoicePulseRing 2.4s cubic-bezier(0.16, 1, 0.3, 1) infinite;
  animation-delay: 0.8s;
}

@keyframes rhVoicePulseRing {
  0% {
    transform: scale(0.96);
    opacity: 0.8;
  }
  100% {
    transform: scale(1.55);
    opacity: 0;
  }
}

/* Landscape orientation optimization for small heights */
@media (max-height: 500px) {
  .remote-hidden {
    gap: 8px;
    padding: 8px 16px calc(76px + env(safe-area-inset-bottom, 0px));
  }
  .rh-circle-outer {
    width: 110px;
    height: 110px;
  }
  .rh-circle-inner {
    width: 82px;
    height: 82px;
  }
  .rh-num {
    font-size: 2.6rem;
  }
  .rh-hint-word__arabic {
    font-size: 2.2rem;
  }
  .rh-hint-zone {
    min-height: 48px;
  }
}

.rh-num {
  font-size: 3.8rem;
  font-weight: 900;
  color: #fff;
  letter-spacing: -0.05em;
  line-height: 1;
  text-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
}

.rh-progress-text {
  font-size: 0.75rem;
  color: rgba(255, 255, 255, 0.35);
  font-weight: 500;
}

.rh-progress-text strong {
  color: rgba(255, 255, 255, 0.65);
  font-weight: 700;
}

/* Hint zone — fixed min-height keeps layout stable when toggling */
.rh-hint-zone {
  min-height: 80px;
  display: grid;
  place-items: center;
  width: 100%;
  padding: 8px 24px;
}

/* Floating hint card that replaces the old inline layout */
.rh-hint-card {
  position: relative;
  background: rgba(255, 255, 255, 0.07);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 16px;
  padding: 14px 40px 14px 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  width: 100%;
  max-width: 260px;
  animation: fadeSlideUp 0.25s ease;
}

.rh-hint-card__label {
  font-size: 0.6rem;
  color: rgba(255, 255, 255, 0.35);
  text-transform: uppercase;
  letter-spacing: 0.1em;
  font-weight: 700;
}

.rh-hint-card__arabic {
  font-size: 1.6rem;
  color: rgba(255, 255, 255, 0.88);
  line-height: 1.4;
  margin: 0;
}

.rh-hint-card__close {
  position: absolute;
  top: 8px;
  right: 8px;
  width: 26px;
  height: 26px;
  border-radius: 50%;
  border: none;
  background: rgba(255, 255, 255, 0.08);
  color: rgba(255, 255, 255, 0.4);
  display: grid;
  place-items: center;
  cursor: pointer;
  -webkit-tap-highlight-color: transparent;
  transition: background 0.15s;
}

.rh-hint-card__close:active {
  background: rgba(255, 255, 255, 0.15);
}

.rh-hint-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 18px;
  border: 1px solid rgba(255, 255, 255, 0.14);
  border-radius: 99px;
  background: rgba(255, 255, 255, 0.06);
  color: rgba(255, 255, 255, 0.55);
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.18s, transform 0.15s;
  -webkit-tap-highlight-color: transparent;
}

.rh-hint-btn:active {
  transform: scale(0.93);
  background: rgba(255, 255, 255, 0.1);
}

/* Arabic hint word — separate display below button */
.rh-hint-word {
  text-align: center;
  animation: fadeSlideUp 0.25s ease;
}

@keyframes fadeSlideUp {
  from { opacity: 0; transform: translateY(8px); }
  to   { opacity: 1; transform: translateY(0); }
}

.rh-hint-word__label {
  font-size: 0.62rem;
  color: rgba(255, 255, 255, 0.3);
  text-transform: uppercase;
  letter-spacing: 0.1em;
  margin-bottom: 6px;
}

.rh-hint-word__arabic {
  font-size: 2.8rem;
  /* Soft warm white — readable but not jarring on dark green */
  color: rgba(255, 255, 255, 0.88);
  text-shadow: 0 2px 12px rgba(0, 0, 0, 0.4);
  line-height: 1.6;
}

.rh-reveal-instruction {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  padding: 12px 24px;
  transition: opacity 0.2s, transform 0.2s;
  -webkit-tap-highlight-color: transparent;
  border-radius: 99px;
  outline: none;
}

.rh-reveal-instruction:focus-visible {
  box-shadow: 0 0 0 3px rgba(52, 211, 153, 0.4);
}

.rh-reveal-instruction:active {
  transform: scale(0.96);
}

.rh-reveal-ring {
  position: absolute;
  inset: 0;
  border-radius: 99px;
  border: 1px solid rgba(52, 211, 153, 0.3);
  animation: rhRevealRing 2.4s ease-in-out infinite;
  pointer-events: none;
}

@keyframes rhRevealRing {
  0%, 100% { transform: scale(1); opacity: 0.5; }
  50% { transform: scale(1.06); opacity: 0; }
}

.rh-reveal-inner {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  position: relative;
  z-index: 1;
}

.rh-reveal-eye-icon {
  color: rgba(52, 211, 153, 0.6);
  flex-shrink: 0;
}

.rh-reveal-text {
  font-size: 0.72rem;
  color: #34d399;
  font-weight: 750;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

/* ─── Side-by-Side Action Buttons ─── */
.rh-actions-row {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  width: 100%;
  margin: 4px 0;
  z-index: 10;
}

.rh-action-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 9px 18px;
  border-radius: 99px;
  font-size: 0.74rem;
  font-weight: 750;
  cursor: pointer;
  transition: all 0.18s ease;
  border: 1px solid rgba(255, 255, 255, 0.12);
  background: rgba(255, 255, 255, 0.05);
  color: rgba(255, 255, 255, 0.7);
  -webkit-tap-highlight-color: transparent;
}

.rh-action-btn:active {
  transform: scale(0.95);
}

.rh-action-btn--record {
  border-color: rgba(239, 68, 68, 0.28);
  background: rgba(239, 68, 68, 0.08);
  color: #fca5a5;
}

.rh-action-btn--record:active {
  background: rgba(239, 68, 68, 0.16);
}

.rh-action-btn--recording {
  border-color: rgba(239, 68, 68, 0.4);
  background: rgba(239, 68, 68, 0.12);
  color: #fca5a5;
}

.rh-action-btn--hint {
  border-color: rgba(255, 255, 255, 0.12);
  background: rgba(255, 255, 255, 0.05);
}

.rh-action-btn--hint:active {
  background: rgba(255, 255, 255, 0.1);
}

.rh-voice-indicator {
  width: 7px;
  height: 7px;
  background: #ef4444;
  border-radius: 50%;
  animation: rhRecordPulse 1.2s ease-in-out infinite;
}

.rh-voice-indicator--lg {
  width: 10px;
  height: 10px;
}

@keyframes rhRecordPulse {
  0%, 100% { transform: scale(1); opacity: 1; }
  50% { transform: scale(1.5); opacity: 0.5; }
}

/* ─── PREMIUM VOICE NOTE UI (WhatsApp-inspired, elevated) ─── */

/* Dock container — full width, centered */
.wa-dock {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 4px 16px;
  position: relative;
  touch-action: none;
}

/* ── IDLE button — premium glassmorphism ── */
.wa-idle-btn {
  position: relative;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 24px;
  background: rgba(239, 68, 68, 0.08);
  border: 1px solid rgba(239, 68, 68, 0.2);
  border-radius: 99px;
  width: 100%;
  justify-content: center;
  cursor: pointer;
  -webkit-tap-highlight-color: transparent;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden;
  backdrop-filter: blur(4px);
}

.wa-idle-btn__glow {
  position: absolute;
  inset: 0;
  border-radius: 99px;
  background: linear-gradient(
    135deg,
    rgba(239, 68, 68, 0.35) 0%,
    rgba(220, 38, 38, 0.25) 40%,
    transparent 60%
  );
  opacity: 0;
  transition: opacity 0.3s ease;
  pointer-events: none;
}

.wa-idle-btn:hover .wa-idle-btn__glow {
  opacity: 1;
}

.wa-idle-btn:active {
  transform: scale(0.96);
  background: rgba(239, 68, 68, 0.15);
}

.wa-idle-btn__icon {
  position: relative;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: linear-gradient(145deg, #ef4444, #dc2626);
  display: grid;
  place-items: center;
  color: white;
  box-shadow: 0 4px 14px rgba(239, 68, 68, 0.5), inset 0 1px 0 rgba(255, 255, 255, 0.2);
  flex-shrink: 0;
  transition: transform 0.2s ease;
}

.wa-idle-btn:active .wa-idle-btn__icon {
  transform: scale(0.92);
}

.wa-idle-btn__icon::after {
  content: '';
  position: absolute;
  inset: -3px;
  border-radius: 50%;
  border: 1px solid rgba(239, 68, 68, 0.25);
  animation: waIdlePing 2s ease-in-out infinite;
}

@keyframes waIdlePing {
  0%, 100% { transform: scale(1); opacity: 1; }
  50% { transform: scale(1.15); opacity: 0; }
}

.wa-idle-btn__label {
  font-size: 0.78rem;
  font-weight: 700;
  color: rgba(255, 255, 255, 0.7);
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

/* ── RECORDING ROW ── */
.wa-rec-row {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 100%;
  animation: fadeSlideUp 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

/* Pill bar — left side, takes remaining space — glassmorphism */
.wa-rec-bar {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 10px;
  background: rgba(18, 18, 20, 0.92);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 99px;
  padding: 8px 14px;
  min-height: 46px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.04);
}

.wa-rec-left {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

.wa-rec-pulse {
  position: relative;
  width: 10px;
  height: 10px;
  flex-shrink: 0;
}

.wa-rec-pulse::before {
  content: '';
  position: absolute;
  inset: 0;
  background: #ef4444;
  border-radius: 50%;
  animation: rhRecordPulse 1.2s ease-in-out infinite;
}

.wa-rec-pulse::after {
  content: '';
  position: absolute;
  inset: -4px;
  border-radius: 50%;
  background: rgba(239, 68, 68, 0.15);
  animation: waRecPulseRing 1.2s ease-in-out infinite;
}

@keyframes waRecPulseRing {
  0%, 100% { transform: scale(1); opacity: 0.5; }
  50% { transform: scale(1.6); opacity: 0; }
}

.wa-rec-timer {
  font-size: 0.8rem;
  font-weight: 800;
  color: #ef4444;
  font-variant-numeric: tabular-nums;
  flex-shrink: 0;
}

/* Cancel hint — centered in remaining space */
.wa-cancel-hint {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: rgba(255, 255, 255, 0.35);
  user-select: none;
  transition: color 0.2s ease, text-shadow 0.2s ease;
}

.wa-cancel-hint--active {
  color: #ef4444;
  text-shadow: 0 0 12px rgba(239, 68, 68, 0.5);
}

.wa-cancel-arrow-icon {
  flex-shrink: 0;
  opacity: 0.6;
  transition: opacity 0.2s ease;
}

.wa-cancel-hint--active .wa-cancel-arrow-icon {
  opacity: 1;
}

/* Discard (trash) button — locked state */
.wa-discard-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background: rgba(239, 68, 68, 0.12);
  color: rgba(252, 165, 165, 0.8);
  border: 1px solid rgba(239, 68, 68, 0.2);
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  -webkit-tap-highlight-color: transparent;
  flex-shrink: 0;
}

.wa-discard-btn:hover {
  background: rgba(239, 68, 68, 0.2);
  color: #fca5a5;
}

.wa-discard-btn:active {
  background: rgba(239, 68, 68, 0.3);
  transform: scale(0.88);
  color: #fff;
}

/* Send (stop) button — locked state */
.wa-send-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  background: rgba(239, 68, 68, 0.15);
  color: rgba(252, 165, 165, 0.9);
  border: 1px solid rgba(239, 68, 68, 0.22);
  border-radius: 99px;
  padding: 6px 16px;
  font-size: 0.72rem;
  font-weight: 800;
  cursor: pointer;
  -webkit-tap-highlight-color: transparent;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  margin-left: auto;
  flex-shrink: 0;
}

.wa-send-btn:hover {
  background: rgba(239, 68, 68, 0.22);
  color: #fca5a5;
}

.wa-send-btn:active {
  background: rgba(239, 68, 68, 0.35);
  transform: scale(0.96);
  color: #fff;
}

/* ── MIC TRIGGER BUTTON (draggable) ── */
.wa-mic-trigger {
  position: relative;
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: rgba(239, 68, 68, 0.15);
  border: 1.5px solid rgba(239, 68, 68, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #ef4444;
  cursor: grab;
  flex-shrink: 0;
  touch-action: none;
  transition: box-shadow 0.2s ease, background 0.2s ease;
  box-shadow: 0 4px 16px rgba(239, 68, 68, 0.15), inset 0 1px 0 rgba(239, 68, 68, 0.1);
}

.wa-mic-trigger__inner {
  position: absolute;
  inset: 3px;
  border-radius: 50%;
  border: 1px solid rgba(239, 68, 68, 0.15);
  pointer-events: none;
}

.wa-mic-trigger:active {
  cursor: grabbing;
  background: rgba(239, 68, 68, 0.25);
  box-shadow: 0 6px 20px rgba(239, 68, 68, 0.25);
}

/* ── LOCK PANEL (left side, floats above) — glassmorphism ── */
.wa-lock-panel {
  position: absolute;
  bottom: 74px;
  left: 18px;
  width: 44px;
  padding: 14px 0 16px;
  background: rgba(18, 18, 20, 0.9);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 99px;
  box-shadow: 0 16px 48px rgba(0, 0, 0, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.04);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  z-index: 999;
  animation: waLockFadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes waLockFadeIn {
  from { transform: translateY(15px) scale(0.9); opacity: 0; }
  to { transform: translateY(0) scale(1); opacity: 1; }
}

.wa-lock-icon {
  color: rgba(255, 255, 255, 0.3);
  transition: color 0.25s ease, transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
  display: flex;
  align-items: center;
  justify-content: center;
}

.wa-lock-icon--ready {
  color: #ef4444;
  transform: scale(1.2);
  filter: drop-shadow(0 0 8px rgba(239, 68, 68, 0.5));
}

.wa-lock-chevron {
  font-size: 0.65rem;
  line-height: 0.7;
  color: rgba(255, 255, 255, 0.2);
  animation: waLockChevronSlide 1.2s infinite ease-in-out;
}

.wa-lock-chevron--1 {
  animation-delay: 0s;
}

.wa-lock-chevron--2 {
  animation-delay: 0.2s;
  margin-top: -4px;
}

@keyframes waLockChevronSlide {
  0% { transform: translateY(4px); opacity: 0; }
  50% { opacity: 0.8; }
  100% { transform: translateY(-4px); opacity: 0; }
}

/* Voice note review bar */
.rh-vn-bar {
  display: flex;
  align-items: center;
  gap: 10px;
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.13);
  border-radius: 99px;
  padding: 10px 14px;
  width: 100%;
  animation: fadeSlideUp 0.22s ease;
}

.rh-vn-play-btn {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: none;
  background: white;
  color: #032d1d;
  display: grid;
  place-items: center;
  cursor: pointer;
  flex-shrink: 0;
  box-shadow: 0 2px 8px rgba(0,0,0,0.25);
  transition: transform 0.15s;
  -webkit-tap-highlight-color: transparent;
}

.rh-vn-play-btn:active { transform: scale(0.88); }

.rh-vn-waveform {
  flex: 1;
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 8px;
  min-width: 0;
}

.rh-vn-progress-track {
  flex: 1;
  height: 4px;
  background: rgba(255,255,255,0.2);
  border-radius: 99px;
  overflow: hidden;
}

.rh-vn-progress-fill {
  height: 100%;
  background: #34d399;
  border-radius: 99px;
  transition: width 0.1s linear;
}

.rh-vn-time {
  font-size: 0.72rem;
  font-weight: 700;
  color: rgba(255,255,255,0.5);
  font-variant-numeric: tabular-nums;
  flex-shrink: 0;
  min-width: 30px;
  text-align: right;
}

.rh-vn-delete-btn,
.rh-vn-download-btn {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  border: none;
  display: grid;
  place-items: center;
  cursor: pointer;
  flex-shrink: 0;
  -webkit-tap-highlight-color: transparent;
  transition: background 0.15s;
}

.rh-vn-delete-btn {
  background: rgba(239, 68, 68, 0.15);
  color: #fca5a5;
}

.rh-vn-delete-btn:active { background: rgba(239, 68, 68, 0.3); }

.rh-vn-download-btn {
  background: rgba(255,255,255,0.12);
  color: rgba(255,255,255,0.75);
}

.rh-vn-download-btn:active { background: rgba(255,255,255,0.22); }

/* Bottom bar hint when ayah is hidden */
.action-bar-reveal-hint {
  flex: 1;
  text-align: center;
  font-size: 0.68rem;
  font-weight: 700;
  color: rgba(255,255,255,0.3);
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.remote-revealed {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-start; /* Solusi agar ayat panjang tidak terpotong di atas dan bisa di-scroll */
  width: 100%;
  max-height: 100%;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
  padding: 8px 0;
}

.remote-ayah-card {
  /* Slightly warm cream — easier transition from dark hidden state than pure white */
  background: #F5F1E8;
  border: 1.5px solid #DDD5C0;
  border-radius: var(--radius-lg);
  padding: 14px 14px 18px;
  margin-bottom: 16px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.06), inset 0 1px 3px rgba(0,0,0,0.02);
  display: flow-root; /* enables float containment like listening card */
  width: 100%;
  text-align: left;
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
  line-height: 1.7;
  text-align: right;
  width: 100%;
}

.text-arabic-md {
  font-size: clamp(1.6rem, 5vw, 2.6rem) !important;
}
.text-arabic-lg {
  font-size: clamp(1.9rem, 6vw, 3.0rem) !important;
}
.text-arabic-xl {
  font-size: clamp(2.3rem, 7.5vw, 3.8rem) !important;
}

:deep(.ayah-ornament) {
  position: relative;
  display: inline-block;
  width: 44px;
  height: 44px;
  margin: 0 10px;
  user-select: none;
  flex-shrink: 0;
  vertical-align: middle;
}

/* Ornament in revealed card — float left, matching listening-ayah-card__badge */
.remote-ayah-card {
  position: relative;
  width: 100%;
}

.remote-ayah-card__ornament {
  float: left;
  width: 36px;
  height: 36px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin: 0 0 2px 0;
  z-index: 1;
}

.remote-ayah-card__ornament :deep(.ayah-ornament),
.remote-ayah-card__ornament ::v-deep(.ayah-ornament) {
  margin: 0;
}

:deep(.ayah-ornament__svg) {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  color: #C29B38; /* Gold color */
  transition: transform var(--transition-normal);
}

:deep(.ayah-ornament:hover .ayah-ornament__svg) {
  transform: rotate(45deg);
}

:deep(.ayah-ornament__num) {
  position: absolute;
  inset: 0;
  z-index: 10;
  font-size: 0.8125rem;
  font-weight: 900;
  color: var(--color-primary-dark);
  font-family: var(--font-ui);
  display: flex;
  align-items: center;
  justify-content: center;
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

/* Review Buttons ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â 16% */
/* ===== Unified Bottom Action Bar (fixed to bottom for thumb reach) ===== */
.remote-action-bar {
  position: fixed;
  bottom: 0;
  left: 0;
  width: 100%;
  max-width: 100%;
  display: flex;
  gap: 10px;
  align-items: stretch;
  padding: 10px 14px;
  padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 12px);
  background: var(--color-bg);
  border-top: 1px solid rgba(0, 0, 0, 0.06);
  box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.06);
  z-index: 100;
}

/* Base for all action buttons */
.action-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4px;
  border-radius: var(--radius-lg);
  font-weight: 700;
  transition: all var(--transition-fast);
  cursor: pointer;
  flex-shrink: 0;
}

.action-btn:disabled {
  opacity: 0.3;
  pointer-events: none;
}

.action-btn:active {
  transform: scale(0.93);
}

.action-btn__label {
  font-size: 0.7rem;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  font-weight: 800;
}

.action-btn__icon {
  font-size: 1.1rem;
  font-weight: 900;
  line-height: 1;
}

/* Back (icon-only, small) */
.action-btn--back {
  width: 48px;
  min-height: 72px;
  background: var(--color-bg-subtle);
  color: var(--color-text-secondary);
  border: 1.5px solid rgba(0, 0, 0, 0.07);
  flex-shrink: 0;
}

.action-btn--back:active {
  background: var(--color-bg-card);
}

/* Forgot ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â compact secondary */
.action-btn--forgot {
  width: 72px;
  min-height: 72px;
  background: var(--color-forgot-bg);
  color: var(--color-forgot);
  border: 1.5px solid var(--color-forgot);
}

.action-btn--forgot:active {
  background: var(--color-forgot);
  color: white;
  box-shadow: 0 0 12px rgba(244, 63, 94, 0.35);
}

/* Doubtful ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â compact secondary */
.action-btn--doubtful {
  width: 72px;
  min-height: 72px;
  background: var(--color-doubtful-bg);
  color: #B45309;
  border: 1.5px solid var(--color-doubtful);
}

.action-btn--doubtful:active {
  background: var(--color-doubtful);
  color: white;
  box-shadow: 0 0 12px rgba(245, 158, 11, 0.35);
}

/* Fluent / Primary CTA ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â takes remaining space */
.action-btn--fluent {
  flex: 1;
  min-height: 72px;
  background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
  color: white;
  border: none;
  border-radius: var(--radius-lg);
  box-shadow: 0 4px 14px rgba(0,0,0,0.15);
  gap: 6px;
}

.action-btn--fluent .action-btn__label {
  font-size: 0.875rem;
  letter-spacing: 0.04em;
}

.action-btn--fluent:active {
  background: var(--color-primary-dark);
  box-shadow: 0 0 16px rgba(0, 0, 0, 0.12);
}

.remote-action-bar--listening {
  padding: 7px 10px calc(7px + env(safe-area-inset-bottom));
  background: linear-gradient(180deg, rgba(255, 253, 248, 0.72), rgba(250, 246, 236, 0.97));
  border-top-color: rgba(180, 146, 77, 0.14);
  backdrop-filter: blur(14px);
}

.listening-player {
  width: 100%;
  min-height: 68px;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 9px 8px 12px;
  border-radius: 19px;
  background: linear-gradient(140deg, #0B5A46 0%, #06493B 62%, #053B32 100%);
  border: 1px solid rgba(222, 185, 101, 0.45);
  box-shadow: 0 9px 22px rgba(5, 58, 48, 0.18), inset 0 1px 0 rgba(255, 247, 220, 0.13);
  color: #FAF5E9;
}

.listening-player__qari {
  min-width: 0;
  flex: 1;
  display: flex;
  align-items: center;
  gap: 9px;
  padding: 5px 2px;
  color: #F8E9C3;
  text-align: left;
}

.listening-player__qari-icon {
  width: 30px;
  height: 30px;
  flex: 0 0 30px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  color: #F3D994;
  background: rgba(255, 247, 226, 0.08);
  border: 1px solid rgba(240, 207, 132, 0.16);
  overflow: hidden;
}

.listening-player__qari-icon img {
  width: 100%;
  height: 100%;
  display: block;
  object-fit: cover;
}

.listening-player__qari-copy {
  min-width: 0;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.listening-player__qari-copy strong {
  overflow: hidden;
  color: #FFF5DC;
  font-size: 0.86rem;
  font-weight: 750;
  line-height: 1.2;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.listening-player__qari-copy small {
  color: rgba(248, 233, 195, 0.7);
  font-size: 0.68rem;
  font-variant-numeric: tabular-nums;
  line-height: 1.2;
}

.listening-player__qari-chevron {
  flex: 0 0 auto;
  color: rgba(248, 233, 195, 0.72);
}

.listening-player__controls {
  flex: 0 0 auto;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 6px;
}

.listening-player__control {
  width: 34px;
  height: 34px;
  border-radius: 11px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #F9F2DF;
  background: rgba(255, 249, 232, 0.07);
  border: 1px solid rgba(246, 220, 159, 0.13);
}

.listening-player__control:disabled {
  opacity: 0.3;
}

.listening-player__play {
  width: 45px;
  height: 45px;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: radial-gradient(circle at 32% 26%, #FFF9EB 0%, #F5E4B9 70%, #DDBB69 100%);
  color: #084B3C;
  border: 1.5px solid rgba(224, 183, 88, 0.95);
  box-shadow: 0 5px 13px rgba(0, 0, 0, 0.18), inset 0 1px 0 rgba(255,255,255,0.72);
}

.listening-player__play--playing {
  color: #0A5844;
}

.listening-player__control:active,
.listening-player__play:active,
.listening-player__qari:active {
  transform: scale(0.96);
}
.remote-content--listening {
  display: block;
  padding: 18px 16px 112px;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
  scroll-padding-block: 104px 112px;
}

.listening-splash {
  min-height: min(52vh, 380px);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  color: #164E40;
  animation: listening-splash-in 180ms ease-out both;
}

.listening-splash__mark {
  position: relative;
  width: 54px;
  height: 54px;
  display: grid;
  place-items: center;
  margin-bottom: 15px;
  border-radius: 50%;
  background: linear-gradient(145deg, #0C6A50, #084A3B);
  border: 1px solid rgba(205, 164, 74, 0.62);
  box-shadow: 0 10px 28px rgba(8, 74, 59, 0.16), inset 0 1px 0 rgba(255, 248, 220, 0.2);
}

.listening-splash__mark::before,
.listening-splash__mark::after,
.listening-splash__mark span {
  content: '';
  position: absolute;
  border-radius: 999px;
}

.listening-splash__mark::before {
  inset: 8px;
  border: 1px solid rgba(247, 224, 169, 0.34);
}

.listening-splash__mark::after {
  width: 7px;
  height: 7px;
  background: #F5D98D;
  box-shadow: 0 -15px 0 -1px #F5D98D, 0 15px 0 -1px #F5D98D, 15px 0 0 -1px #F5D98D, -15px 0 0 -1px #F5D98D;
  animation: listening-splash-spin 850ms linear infinite;
}

.listening-splash__mark span {
  width: 10px;
  height: 10px;
  background: #FFF8E8;
}

.listening-splash__title {
  color: #164E40;
  font-size: 1rem;
  font-weight: 750;
  letter-spacing: 0.01em;
}

.listening-splash__hint {
  margin-top: 5px;
  color: var(--color-text-secondary);
  font-size: 0.8rem;
}

@keyframes listening-splash-spin {
  to { transform: rotate(360deg); }
}

@keyframes listening-splash-in {
  from { opacity: 0; transform: translateY(4px); }
  to { opacity: 1; transform: translateY(0); }
}
.listening-ayah-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.listening-ayah-card {
  position: relative;
  display: flow-root;
  width: 100%;
  text-align: left;
  padding: 16px 14px 18px;
  border-radius: 22px;
  border: 1px solid rgba(207, 181, 124, 0.28);
  background: #FFFDF8;
  box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05);
}

.listening-ayah-card__badge {
  position: relative;
  z-index: 1;
  float: left;
  width: 30px;
  height: 30px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin: 0 0 2px 0;
}

.listening-ayah-card--active {
  background: linear-gradient(180deg, rgba(16, 185, 129, 0.08), rgba(16, 185, 129, 0.03));
  border-color: rgba(16, 185, 129, 0.35);
  box-shadow: 0 10px 24px rgba(16, 185, 129, 0.10);
  scroll-margin-block: 104px 112px;
}

.listening-ayah-card__body {
  display: block;
}

.listening-ayah-card__arabic {
  margin: 0;
  font-family: var(--font-arabic);
  font-size: clamp(1.85rem, 5.6vw, 2.5rem);
  line-height: 1.85;
  text-align: right;
  color: var(--color-text-primary);
}

.listening-ayah-card__translation {
  clear: both;
  margin: 14px 0 0;
  padding: 0 3px 1px;
  font-size: 0.975rem;
  line-height: 1.65;
  color: var(--color-text-secondary);
}

.listening-ayah-card__badge :deep(.ayah-ornament),
.listening-ayah-card__badge ::v-deep(.ayah-ornament) {
  margin: 0;
}

:deep(.ayah-ornament--mini) {
  width: 38px;
  height: 38px;
  margin: 0;
}

:deep(.ayah-ornament--mini .ayah-ornament__num) {
  font-size: 0.65rem; /* slightly smaller to fit 3-digit numbers */
  font-weight: 900;
  letter-spacing: -0.03em; /* tighter kerning for 3 digits */
}

.listening-ayah-card--active .listening-ayah-card__badge {
  transform: scale(1.03);
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
  user-select: none;
  -webkit-user-select: none;
}

.picker-sheet__header {
  padding: 16px 20px 12px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.08);
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.picker-sheet__indicator {
  width: 100%;
  padding: 8px 0;
  display: flex;
  justify-content: center;
  cursor: grab;
  touch-action: none;
  user-select: none;
  margin-bottom: 4px;
}

.picker-sheet__indicator::before {
  content: '';
  width: 40px;
  height: 5px;
  background: var(--color-text-muted);
  border-radius: var(--radius-full);
  opacity: 0.5;
}

.picker-sheet__indicator:active {
  cursor: grabbing;
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
.sheet-leave-active .picker-sheet,
.sheet-enter-active .navigator-sheet,
.sheet-leave-active .navigator-sheet {
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.sheet-enter-from .picker-sheet,
.sheet-leave-to .picker-sheet,
.sheet-enter-from .navigator-sheet,
.sheet-leave-to .navigator-sheet {
  transform: translateY(100%);
}

/* Desktop: center as card */
@media (min-width: 768px) {
  .remote-page {
    border-left: 1px solid rgba(0, 0, 0, 0.08);
    border-right: 1px solid rgba(0, 0, 0, 0.08);
    box-shadow: var(--shadow-lg);
  }
  .remote-content {
    padding: 16px 20px;
  }
  .remote-ayah-card {
    padding: 24px 20px;
  }
  .remote-ayah-text {
    /* Font sizes will be handled by the dynamic classes */
  }
  .text-arabic-md {
    font-size: clamp(1.5rem, 5vw, 2.5rem) !important;
  }
  .text-arabic-lg {
    font-size: clamp(1.8rem, 6vw, 3rem) !important;
  }
  .text-arabic-xl {
    font-size: clamp(2rem, 7vw, 3.5rem) !important;
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

/* ================================================
   QARI AUDIO PLAYER & SELECTOR
   ================================================ */
.audio-panel {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 8px 16px;
  margin-top: 4px;
  margin-bottom: 8px;
}

.audio-qari-selector {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 6px;
  background: rgba(0, 0, 0, 0.04);
  border: 1.5px solid rgba(0, 0, 0, 0.02);
  border-radius: var(--radius-lg);
  padding: 8px 14px;
  color: var(--color-text-secondary);
  font-weight: 700;
  font-size: 0.8rem;
  cursor: pointer;
  transition: all 0.2s ease;
  flex: 1;
  min-width: 0;
}

.audio-qari-selector span {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  flex: 1;
  text-align: left;
}

.audio-qari-selector:hover {
  background: rgba(0, 0, 0, 0.08);
}

.audio-qari-selector:active {
  transform: scale(0.95);
}

.audio-play-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-primary-light);
  border: none;
  border-radius: 50%;
  color: white;
  cursor: pointer;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 4px 10px rgba(16, 185, 129, 0.2);
  width: 44px;
  height: 44px;
  flex-shrink: 0;
}

.audio-play-btn:hover {
  background: var(--color-primary);
  box-shadow: 0 6px 14px rgba(16, 185, 129, 0.3);
}

.audio-play-btn:active {
  transform: scale(0.95);
}

.audio-play-btn--playing {
  background: #EF4444 !important;
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3) !important;
  animation: pulseAudio 1.5s infinite;
}

@keyframes pulseAudio {
  0% { transform: scale(1); }
  50% { transform: scale(1.03); }
  100% { transform: scale(1); }
}

/* Qari Picker Sheet Content */
.qari-picker-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 10px 4px;
}

.qari-picker-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #F9FAF7;
  border: 1.5px solid rgba(0, 0, 0, 0.03);
  border-radius: var(--radius-md);
  padding: 14px 16px;
  cursor: pointer;
  transition: all 0.2s ease;
  width: 100%;
}

.qari-picker-item:hover {
  background: #FFFDF8;
  border-color: var(--color-primary-light);
}

.qari-picker-item--active {
  background: #ECFDF5;
  border-color: var(--color-primary-light);
  box-shadow: 0 2px 8px rgba(16, 185, 129, 0.05);
}

.qari-picker-item__left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.qari-picker-icon {
  width: 46px;
  height: 46px;
  flex: 0 0 46px;
  overflow: hidden;
  border-radius: 999px;
  border: 1px solid rgba(180, 146, 77, 0.22);
  background: #F3EFE5;
  box-shadow: 0 3px 9px rgba(31, 41, 55, 0.08);
}

.qari-picker-icon img {
  width: 100%;
  height: 100%;
  display: block;
  object-fit: cover;
}

.qari-picker-copy {
  min-width: 0;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 3px;
  text-align: left;
}

.qari-picker-name {
  color: #1F2937;
  font-size: 0.88rem;
  font-weight: 750;
  line-height: 1.3;
}

.qari-picker-country {
  color: var(--color-text-muted);
  font-size: 0.75rem;
  font-weight: 550;
  line-height: 1.2;
}

.qari-picker-item--active .qari-picker-name {
  color: var(--color-primary);
}

.qari-picker-check {
  color: var(--color-primary);
  font-weight: bold;
  font-size: 1rem;
}

.remote-header__surah-info {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  min-width: 0;
  flex: 1;
}

.header-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 0.6rem;
  font-weight: 850;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  margin-bottom: 6px;
  text-align: left;
  cursor: pointer;
  border: none;
  padding: 4.5px 10px;
  border-radius: 99px;
  transition: transform 0.2s, background 0.22s, color 0.22s;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.header-eyebrow:active {
  transform: scale(0.95);
}

.header-eyebrow--learning {
  background: rgba(52, 211, 153, 0.15); /* Emerald Tint */
  color: #34D399;
  border: 1px solid rgba(52, 211, 153, 0.25);
}

.header-eyebrow--learning:active {
  background: rgba(52, 211, 153, 0.25);
}

.header-eyebrow--listening {
  background: rgba(99, 102, 241, 0.18); /* Indigo Tint to match Dengar mode card */
  color: #a5b4fc; /* Light Indigo */
  border: 1px solid rgba(99, 102, 241, 0.3);
}

.header-eyebrow--listening:active {
  background: rgba(99, 102, 241, 0.3);
}
/* Unified reader chrome */
.remote-header {
  flex: 0 0 auto;
  min-height: 76px;
  gap: 10px;
  padding: calc(var(--safe-top) + 8px) 12px 8px;
}
.remote-header__left { min-width: 0; flex: 1; gap: 8px; }
.remote-header__surah-trigger { min-width: 0; flex: 1; }
.remote-header__back { width: 40px; height: 40px; }
.remote-header__title {
  max-width: 100%;
  overflow: hidden;
  gap: 4px;
  font-size: 1rem;
  line-height: 1.2;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.remote-header__subtitle { font-size: .68rem; }
.remote-header__browse {
  width: 42px;
  height: 42px;
  flex: 0 0 42px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  color: #fff;
  background: rgba(255,255,255,.12);
  box-shadow: inset 0 0 0 1px rgba(255,255,255,.08);
}
.remote-header__browse svg { width: 21px; height: 21px; }
.remote-action-bar--listening {
  padding: 8px 12px calc(8px + env(safe-area-inset-bottom));
  background: rgba(255,255,253,.97);
  border-top-color: rgba(31,54,45,.1);
  box-shadow: 0 -8px 26px rgba(28,46,39,.08);
}
.listening-player {
  min-height: 54px;
  gap: 10px;
  padding: 0;
  border: 0;
  border-radius: 0;
  color: #26362f;
  background: transparent;
  box-shadow: none;
}
.listening-player__controls {
  order: 0;
  gap: 2px;
  padding: 3px;
  border: 1px solid #e2ece7;
  border-radius: 999px;
  background: #f1f7f4;
}
.listening-player__control,
.listening-player__play {
  width: 40px;
  height: 40px;
  flex: 0 0 40px;
  border: 0;
  border-radius: 50%;
}
.listening-player__control { color: #527067; background: transparent; }
.listening-player__control:active { color: #087d59; background: #e2f0e9; }
.listening-player__play {
  color: #fff;
  background: #12866a;
  box-shadow: 0 4px 10px rgba(18,134,106,.2);
}
.listening-player__control svg,
.listening-player__play svg { width: 20px; height: 20px; }
.listening-player__qari {
  order: 1;
  gap: 8px;
  padding: 0;
  color: #26362f;
}
.listening-player__qari-icon {
  width: 34px;
  height: 34px;
  flex-basis: 34px;
  border-color: rgba(180,146,77,.2);
  background: #f3efe5;
}
.listening-player__qari-copy strong { color: #26362f; font-size: .78rem; }
.listening-player__qari-copy small { color: #7b8680; font-size: .62rem; }
.listening-player__qari-chevron { width: 16px; height: 16px; color: #168d70; }
/* Header optical alignment and shared Murottal settings */
.remote-header { padding-right: 12px; padding-left: 12px; }
.remote-header__left { gap: 12px; }
.listening-player__control { position: relative; }
.listening-player__control--active { color: #087d59; background: #e2f0e9; }
.listening-player__badge {
  position: absolute;
  top: -4px;
  right: -5px;
  padding: 2px 4px;
  border-radius: 999px;
  color: #fff;
  background: #12866a;
  font-size: .48rem;
  font-weight: 800;
  line-height: 1;
}
.listening-settings-sheet { padding: 10px 20px calc(22px + env(safe-area-inset-bottom)); }
.listening-settings__header h3 { margin: 6px 0 18px; color: #1f2e28; font-size: 1.2rem; }
.listening-settings__body { display: flex; flex-direction: column; gap: 14px; }
.listening-settings__row { display: grid; grid-template-columns: minmax(0, 1fr) minmax(92px, .72fr); gap: 12px; }
.listening-settings__body label { display: flex; flex-direction: column; gap: 7px; color: #43534c; font-size: .72rem; font-weight: 750; }
.listening-settings__body select {
  width: 100%;
  min-height: 46px;
  border: 1px solid #d8e3de;
  border-radius: 13px;
  padding: 0 12px;
  color: #26362f;
  background: #fbfcfa;
  font-size: .78rem;
  font-weight: 700;
}
.listening-settings__actions { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 22px; }
.listening-settings__actions button { min-height: 48px; border-radius: 13px; font-size: .82rem; font-weight: 800; }
.listening-settings__cancel { border: 1px solid #d8e3de; color: #75827c; background: #fff; }
.listening-settings__play { color: #fff; background: #12866a; }
/* Remote Navigator Sheet (mushaf-style Pilih Bacaan) */
.navigator-overlay {
  position: fixed;
  inset: 0;
  z-index: 1100;
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

.navigator-sheet__content {
  padding: 16px 20px calc(24px + env(safe-area-inset-bottom));
  display: flex;
  flex-direction: column;
  gap: 20px;
  flex: 1;
  overflow-y: auto;
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

.navigator-dynamic {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.navigator-fields-row {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 12px;
  align-items: start;
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

/* Ayat number must always be fully visible — no ellipsis */
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

/* Surah / Ayah sub-picker — identik dengan mushaf */
.surah-picker {
  position: fixed;
  inset: 0;
  z-index: 1200;
  display: flex;
  flex-direction: column;
  min-height: 0;
  padding: 18px 18px calc(20px + var(--safe-bottom, 0px));
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

/* Ayah grid sub-picker */
.ayah-picker {
  /* inherits .surah-picker layout */
}

.ayah-picker__summary {
  margin: 0 0 13px;
  color: #7e8983;
  font-size: .68rem;
  flex-shrink: 0;
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

/* Picker sub-panel transitions — identik mushaf */
.picker-enter-active,
.picker-leave-active {
  transition: transform .24s ease, opacity .2s ease;
}
.picker-enter-from,
.picker-leave-to {
  transform: translateY(100%);
  opacity: 0;
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
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
  border-bottom: 1px solid rgba(0,0,0,0.05);
  flex-shrink: 0;
}

.surah-picker__search svg {
  width: 18px;
  height: 18px;
  flex-shrink: 0;
  color: #9CA3AF;
}

.surah-picker__search input {
  flex: 1;
  border: none;
  background: transparent;
  font-size: 0.88rem;
  color: #1F2937;
  outline: none;
}

.surah-picker__search input::placeholder {
  color: #9CA3AF;
}

.surah-picker__list {
  flex: 1;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 2px;
  padding: 8px 12px calc(24px + env(safe-area-inset-bottom));
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
  border-radius: 50%;
  background: #F4F3ED;
  display: grid;
  place-items: center;
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
  font-size: 0.82rem;
  font-weight: 700;
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

/* Ayah grid sub-picker */
.ayah-picker {
  padding-bottom: env(safe-area-inset-bottom);
}

.ayah-picker__summary {
  padding: 10px 16px 0;
  font-size: 0.75rem;
  color: #6B7280;
  flex-shrink: 0;
}

.ayah-picker__grid {
  flex: 1;
  overflow-y: auto;
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 8px;
  padding: 10px 14px calc(24px + env(safe-area-inset-bottom));
}

.ayah-picker__grid button {
  aspect-ratio: 1;
  border-radius: 12px;
  border: 1.5px solid rgba(0,0,0,0.06);
  background: #fff;
  color: #374151;
  font-size: 0.88rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.15s;
}

.ayah-picker__grid button:active {
  transform: scale(0.93);
}

.ayah-picker__grid .ayah-picker__number--active {
  background: #087d59 !important;
  color: #fff !important;
  border-color: #087d59 !important;
  box-shadow: 0 2px 8px rgba(8, 125, 89, 0.3);
}

/* Picker sub-panel transitions */
.picker-enter-active, .picker-leave-active {
  transition: transform 0.32s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.22s ease;
}
.picker-enter-from {
  transform: translateY(100%);
  opacity: 0;
}
.picker-leave-to {
  transform: translateY(100%);
  opacity: 0;
}
.listening-settings-sheet { padding: 10px 20px calc(24px + env(safe-area-inset-bottom)); }
.listening-settings__header h3 { margin: 6px 0 18px; color: #1f2e28; font-size: 1.2rem; }
.audio-settings-body { display: flex; flex-direction: column; gap: 16px; padding: 8px 0; }
.audio-settings-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.audio-settings-col, .audio-settings-field { display: flex; flex-direction: column; gap: 6px; }
.audio-settings-col label, .audio-settings-field label { color: #314039; font-size: .75rem; font-weight: 700; }
.custom-select-trigger { width: 100%; height: 48px; display: flex; align-items: center; justify-content: space-between; border: 1px solid #dce3df; border-radius: 13px; padding: 0 16px; color: #314039; background: #fbfcfa; font-size: .8rem; font-weight: 750; text-align: left; }
.custom-select-trigger span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.custom-select-trigger svg { width: 16px; height: 16px; flex: 0 0 16px; color: #159174; }
.audio-settings-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 10px; }
.settings-action-btn { height: 48px; border: 1px solid #dce3df; border-radius: 12px; font-size: .85rem; font-weight: 750; }
.settings-action-btn--cancel { color: #7b8680; background: #fff; }
.settings-action-btn--play { color: #fff; border-color: #159174; background: #159174; }
.settings-picker-header { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; border-bottom: 1px solid #dce3df; padding-bottom: 12px; color: #26362f; }
.settings-picker-back { width: 36px; height: 36px; display: grid; place-items: center; border-radius: 50%; color: #314039; background: #f4f6f5; }
.settings-picker-back svg { width: 20px; height: 20px; }
.settings-picker-search { margin-bottom: 12px; }
.settings-picker-search input { width: 100%; height: 40px; border: 1px solid #dce3df; border-radius: 10px; padding: 0 12px; background: #fbfcfa; font-size: .8rem; }
.settings-picker-options { max-height: 50vh; overflow-y: auto; display: flex; flex-direction: column; gap: 4px; }
.settings-surah-item { width: 100%; min-height: 58px; display: flex; align-items: center; gap: 11px; border: 1px solid #eef1ef; border-radius: 12px; padding: 8px 10px; color: #314039; background: #fff; text-align: left; }
.settings-surah-item:disabled { opacity: .35; }
.settings-surah-number { width: 31px; height: 31px; flex: 0 0 31px; display: grid; place-items: center; border-radius: 50%; color: #087d59; background: #e9f6f0; font-size: .7rem; font-weight: 800; }
.settings-surah-name { min-width: 0; flex: 1; display: flex; flex-direction: column; gap: 2px; }
.settings-surah-name strong { font-size: .78rem; }
.settings-surah-name small { color: #7b8580; font-size: .64rem; }
.settings-surah-arabic { color: #53625b; font-family: 'Uthmanic Hafs', serif; font-size: .9rem; }
.settings-ayah-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 9px; }
.settings-ayah-grid button { aspect-ratio: 1; border: 1px solid #dce3df; border-radius: 11px; color: #43534c; background: #fbfcfa; font-weight: 750; }
.settings-ayah-grid button:disabled { opacity: .28; }
.settings-repeat-option { width: 100%; min-height: 48px; border: 1px solid #eef1ef; border-radius: 10px; padding: 0 16px; color: #314039; background: #fff; text-align: left; font-size: .82rem; font-weight: 650; }
.settings-option--active { color: #087d59 !important; border-color: #ccece2 !important; background: #e9f6f0 !important; }

/* Landscape orientation optimization for modal pickers and sheets */
@media (max-height: 520px) {
  .navigator-sheet,
  .picker-sheet {
    min-height: 0 !important;
    max-height: 96dvh !important;
  }
  .navigator-sheet__content,
  .picker-sheet__content,
  .audio-settings-body {
    flex: 1 !important;
    max-height: none !important;
    overflow-y: auto !important;
  }
  .settings-picker-options,
  .ayah-picker-grid,
  .settings-ayah-grid {
    max-height: 50vh !important;
    overflow-y: auto !important;
  }
  /* ─── 2-Column Grid Layout for Landscape Uji Ayat ─── */
  .remote-page:not(.remote-page--listening) {
    display: grid !important;
    grid-template-rows: auto 1fr !important;
    grid-template-columns: 60% 40% !important;
    height: 100dvh !important;
    overflow: hidden !important;
  }
  .remote-page:not(.remote-page--listening) .remote-header {
    grid-row: 1 !important;
    grid-column: 1 / span 2 !important;
    height: auto !important;
    min-height: 48px !important;
    padding: 6px 16px !important;
    flex: none !important;
  }
  .remote-page:not(.remote-page--listening) .remote-content {
    grid-row: 2 !important;
    grid-column: 1 !important;
    height: 100% !important;
    padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 8px) !important;
    overflow-y: auto !important;
  }
  .remote-page:not(.remote-page--listening) .remote-action-bar {
    grid-row: 2 !important;
    grid-column: 2 !important;
    height: 100% !important;
    position: static !important;
    transform: none !important;
    display: flex !important;
    flex-direction: column !important;
    justify-content: space-evenly !important; /* Space items evenly to never overflow */
    padding: 10px 16px !important;
    padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 12px) !important;
    border-top: none !important;
    border-left: 1px solid rgba(0, 0, 0, 0.06) !important;
    width: 100% !important;
    max-width: 100% !important;
  }
  .remote-page--hidden:not(.remote-page--listening) .remote-action-bar {
    border-left: 1px solid rgba(255, 255, 255, 0.08) !important;
    background: #022318 !important;
  }
  .action-btn {
    width: 100% !important;
    flex-direction: row !important;
    justify-content: center !important;
    align-items: center !important;
    gap: 8px !important;
    height: auto !important;
    flex: 1 !important;
    max-height: 44px !important; /* Cap the max height of each button so they fit nicely */
    padding: 0 16px !important;
  }
  .action-btn--back {
    max-height: 36px !important;
  }

}
.navigator-sheet--picker-open {
  height: 96dvh;
  max-height: 96dvh;
}


/* ─── REMOVED: old placeholder styles replaced by wa-* classes ─── */
</style>
