<template>
  <div class="page">
    <header class="history-header">
      <div class="history-header__arabesque"></div>
      <span class="hdr-sparkle hdr-sparkle--1">✦</span>
      <span class="hdr-sparkle hdr-sparkle--2">◈</span>
      <span class="hdr-sparkle hdr-sparkle--3">✧</span>
      <span class="hdr-dot hdr-dot--1"></span>
      <span class="hdr-dot hdr-dot--2"></span>

      <div class="container" style="position: relative; z-index: 2; padding-bottom: 24px; padding-left: calc(16px + env(safe-area-inset-left)); padding-right: calc(16px + env(safe-area-inset-right));">
        <NuxtLink to="/" class="history-header__back" aria-label="Kembali">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
            <path d="m15 18-6-6 6-6"/>
          </svg>
        </NuxtLink>
        <div class="history-header__text">
          <h1 class="history-title">Bookmark</h1>
          <p class="history-subtitle">
            <span class="hdr-diamond">◆</span>
            Ayat yang kamu simpan untuk dibaca ulang
          </p>
        </div>
      </div>

      <div class="history-header__divider" aria-hidden="true">
        <svg class="history-header__divider-svg" viewBox="0 0 390 96" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <linearGradient id="bm-waveCream" x1="0" y1="0" x2="0" y2="96">
              <stop offset="0%" stop-color="#FFFDF6" />
              <stop offset="100%" stop-color="#FAFAF2" />
            </linearGradient>
            <linearGradient id="bm-waveGold" x1="0" y1="0" x2="390" y2="0">
              <stop offset="0%" stop-color="#A87518" />
              <stop offset="32%" stop-color="#F7D979" />
              <stop offset="62%" stop-color="#FFF3B8" />
              <stop offset="100%" stop-color="#C6952B" />
            </linearGradient>
            <filter id="bm-softWaveShadow" x="-20%" y="-50%" width="140%" height="180%">
              <feDropShadow dx="0" dy="5" stdDeviation="5" flood-color="#04251A" flood-opacity="0.14" />
            </filter>
          </defs>
          <path d="M0,55 C58,39 118,37 174,46 C242,57 304,60 390,37 L390,96 L0,96 Z" fill="url(#bm-waveCream)" filter="url(#bm-softWaveShadow)"></path>
          <path d="M0,55 C58,39 118,37 174,46 C242,57 304,60 390,37" fill="none" stroke="#E7C765" stroke-width="6" stroke-linecap="round" opacity="0.2"></path>
          <path d="M0,55 C58,39 118,37 174,46 C242,57 304,60 390,37" fill="none" stroke="url(#bm-waveGold)" stroke-width="2.2" stroke-linecap="round"></path>
          <path d="M0,51 C58,35 118,33 174,42 C242,53 304,56 390,33" fill="none" stroke="rgba(255,255,255,0.75)" stroke-width="1" stroke-linecap="round"></path>
        </svg>
      </div>
    </header>

    <main class="page-content container" style="margin-top: -18px; padding-bottom: calc(var(--bottom-nav-height) + var(--safe-bottom) + 28px);">
      <div class="bookmarks-summary" v-if="bookmarks.length">
        <div>
          <strong>{{ bookmarks.length }}</strong>
          <span>ayat tersimpan</span>
        </div>
        <label class="bookmarks-search">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/>
            <path d="m21 21-4.35-4.35"/>
          </svg>
          <input v-model="searchQuery" type="search" placeholder="Cari surat atau ayat" />
        </label>
      </div>

      <section v-if="filteredBookmarks.length" class="bookmark-list">
        <div
          v-for="bookmark in filteredBookmarks"
          :key="bookmark.verse_key"
          class="bookmark-item"
        >
          <NuxtLink
            class="bookmark-item__link"
            :to="{ path: `/mushaf/${bookmark.page}`, query: { surah: bookmark.surah, ayah: bookmark.ayah } }"
          >
            <span class="bookmark-item__num">{{ bookmark.ayah }}</span>
            <span class="bookmark-item__name">{{ bookmark.surah_name || `Surat ${bookmark.surah}` }}</span>
            <span class="bookmark-item__meta">Ayat {{ bookmark.ayah }}</span>
          </NuxtLink>
          <button type="button" class="bookmark-item__remove" aria-label="Hapus penanda" @click="confirmRemove(bookmark)">
            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M2.5 5h15"/>
              <path d="M6.5 5V3.5a1 1 0 0 1 1-1h5a1 1 0 0 1 1 1V5"/>
              <path d="M16.5 5l-1 11.5a1.5 1.5 0 0 1-1.5 1.4H6a1.5 1.5 0 0 1-1.5-1.4L3.5 5"/>
              <path d="M8 8.5v5"/>
              <path d="M12 8.5v5"/>
            </svg>
          </button>
        </div>
      </section>

      <section v-else class="bookmark-empty">
        <div class="bookmark-empty__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/>
          </svg>
        </div>
        <h2>{{ bookmarks.length ? 'Bookmark tidak ditemukan' : 'Belum ada penanda' }}</h2>
        <p>{{ bookmarks.length ? 'Coba kata kunci lain.' : 'Tahan ayat di mushaf, lalu pilih Simpan Bookmark.' }}</p>
        <NuxtLink to="/mushaf/1?surah=1" class="bookmark-empty__btn">Buka Mushaf</NuxtLink>
      </section>
    </main>

    <!-- Delete Confirmation Modal -->
    <Transition name="fade">
      <div v-if="deleteConfirmTarget" class="confirm-overlay" @click="deleteConfirmTarget = null">
        <div class="confirm-modal" @click.stop>
          <div class="confirm-modal__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M3 6h18"/>
              <path d="M8 6V4h8v2"/>
              <path d="M19 6l-1 14H6L5 6"/>
            </svg>
          </div>
          <h3 class="confirm-modal__title">Hapus Penanda?</h3>
          <p class="confirm-modal__desc">Penanda <strong>{{ deleteConfirmTarget?.surah_name || 'Surat ' + deleteConfirmTarget?.surah }}</strong> Ayat {{ deleteConfirmTarget?.ayah }} akan dihapus.</p>
          <div class="confirm-modal__actions">
            <button type="button" class="confirm-btn confirm-btn--cancel" @click="deleteConfirmTarget = null">Batal</button>
            <button type="button" class="confirm-btn confirm-btn--confirm" @click="executeDelete">Hapus</button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
useScrollRestore('bookmarks')
import type { AyahBookmark } from '~/composables/useAyahBookmarks'

const { bookmarks, loadBookmarks, removeBookmark } = useAyahBookmarks()
const showToast = inject<(msg: string, type?: string) => void>('showToast')
const searchQuery = ref('')

const filteredBookmarks = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()
  const sorted = [...bookmarks.value].sort((a, b) => Date.parse(b.created_at) - Date.parse(a.created_at))
  if (!query) return sorted

  return sorted.filter(bookmark => {
    const haystack = [
      bookmark.surah_name,
      `surat ${bookmark.surah}`,
      `ayat ${bookmark.ayah}`,
      bookmark.verse_key,
      bookmark.text,
      bookmark.translation
    ].filter(Boolean).join(' ').toLowerCase()
    return haystack.includes(query)
  })
})

const deleteConfirmTarget = ref<AyahBookmark | null>(null)

const confirmRemove = (bookmark: AyahBookmark) => {
  deleteConfirmTarget.value = bookmark
}

const executeDelete = () => {
  if (deleteConfirmTarget.value) {
    removeBookmark(deleteConfirmTarget.value.verse_key)
    showToast?.('Penanda ayat dihapus', 'info')
    deleteConfirmTarget.value = null
  }
}

onMounted(loadBookmarks)

useHead({
  title: 'Bookmark - Murojaah'
})
</script>

<style scoped>
.history-header {
  position: relative;
  overflow: hidden;
  padding: calc(var(--safe-top) + 28px) 0 0;
  color: #fff;
  background: linear-gradient(165deg, #042e1b 0%, #07543f 52%, #0a6b4f 100%);
}

.history-header__arabesque {
  position: absolute;
  inset: 0;
  opacity: 0.10;
  background-image:
    radial-gradient(circle at 15% 22%, #f7d979, transparent 28%),
    radial-gradient(circle at 88% 30%, #d4af37, transparent 32%),
    repeating-linear-gradient(45deg, transparent, transparent 16px, rgba(247, 217, 121, 0.06) 16px, rgba(247, 217, 121, 0.06) 17px);
  pointer-events: none;
}

.hdr-sparkle {
  position: absolute;
  z-index: 1;
  pointer-events: none;
  opacity: 0.22;
  color: #f7d979;
}
.hdr-sparkle--1 { top: 18%; right: 10%; font-size: 1.2rem; animation: floatSparkle 5s ease-in-out infinite; }
.hdr-sparkle--2 { top: 32%; left: 8%; font-size: 1.0rem; animation: floatSparkle 7s ease-in-out infinite 1s; }
.hdr-sparkle--3 { bottom: 26%; right: 16%; font-size: 0.8rem; animation: floatSparkle 6s ease-in-out infinite 2s; }

.hdr-dot {
  position: absolute;
  z-index: 1;
  border-radius: 50%;
  pointer-events: none;
  background: rgba(247, 217, 121, 0.18);
}
.hdr-dot--1 { width: 8px; height: 8px; top: 14%; left: 22%; animation: pulseDot 4s ease-in-out infinite; }
.hdr-dot--2 { width: 5px; height: 5px; bottom: 22%; right: 22%; animation: pulseDot 5s ease-in-out infinite 1.5s; }

@keyframes floatSparkle {
  0%, 100% { transform: translateY(0) rotate(0deg); }
  50% { transform: translateY(-10px) rotate(18deg); }
}
@keyframes pulseDot {
  0%, 100% { opacity: 0.18; transform: scale(1); }
  50% { opacity: 0.5; transform: scale(1.5); }
}

.history-header__back {
  width: 40px;
  height: 40px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
  color: #fff;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.12);
  margin-bottom: 14px;
  text-decoration: none;
}

.history-header__back svg {
  width: 20px;
  height: 20px;
}

.history-header__text {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.history-title {
  margin: 0;
  font-size: 1.7rem;
  font-weight: 800;
  letter-spacing: -0.01em;
  line-height: 1.1;
}

.history-subtitle {
  margin: 6px 0 0;
  color: rgba(255, 255, 255, 0.75);
  font-size: 0.84rem;
  font-weight: 500;
}

.hdr-diamond {
  display: inline-block;
  color: #f7d979;
  font-size: 0.5rem;
  margin-right: 4px;
  transform: translateY(-1px);
}

.history-header__divider {
  position: relative;
  width: 100%;
  height: 40px;
  margin-top: -6px;
  overflow: hidden;
}

.history-header__divider-svg {
  width: 100%;
  height: 60px;
  display: block;
}

/* Page content */
.page-content {
  position: relative;
  z-index: 1;
}

.bookmarks-summary {
  position: relative;
  display: grid;
  gap: 12px;
  padding: 14px;
  margin-bottom: 14px;
  border-radius: 18px;
  background: #fff;
  border: 1px solid rgba(210, 178, 101, 0.2);
  box-shadow: 0 10px 24px rgba(31, 41, 55, 0.07);
}

.bookmarks-summary strong {
  display: block;
  color: #064e3b;
  font-size: 1.4rem;
  line-height: 1;
}

.bookmarks-summary span {
  color: #66756f;
  font-size: 0.8rem;
  font-weight: 700;
}

.bookmarks-search {
  height: 44px;
  display: flex;
  align-items: center;
  gap: 9px;
  padding: 0 13px;
  border-radius: 14px;
  background: #f6f8f5;
  border: 1px solid #e7ece8;
}

.bookmarks-search svg {
  width: 17px;
  height: 17px;
  color: #0a6b4f;
}

.bookmarks-search input {
  width: 100%;
  border: 0;
  outline: 0;
  background: transparent;
  color: #1f2937;
  font: inherit;
  font-size: 0.86rem;
}

.bookmark-list {
  display: grid;
  gap: 12px;
}

.bookmark-item {
  display: flex;
  align-items: stretch;
  overflow: hidden;
  border-radius: 14px;
  background: #fff;
  border: 1px solid #edf0ea;
  box-shadow: 0 4px 14px rgba(31, 41, 55, 0.05);
}

.bookmark-item__link {
  flex: 1;
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px 16px;
  color: inherit;
  text-decoration: none;
  transition: background 0.12s ease;
}

.bookmark-item__link:active {
  background: rgba(0,0,0,0.02);
}

.bookmark-item__num {
  width: 36px;
  height: 36px;
  flex: 0 0 auto;
  display: grid;
  place-items: center;
  color: #0a6b4f;
  background: linear-gradient(180deg, #f8f3df, #eef7f2);
  border: 1px solid rgba(10, 107, 79, 0.12);
  border-radius: 10px;
  font-weight: 800;
  font-size: 0.85rem;
}

.bookmark-item__name {
  flex: 1;
  min-width: 0;
  color: #17233a;
  font-size: 0.92rem;
  font-weight: 700;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.bookmark-item__meta {
  flex: 0 0 auto;
  color: #7b8882;
  font-size: 0.72rem;
  font-weight: 700;
  white-space: nowrap;
}

.bookmark-item__remove {
  width: 44px;
  flex: 0 0 auto;
  border: 0;
  border-left: 1px solid #edf0ea;
  color: #b4534b;
  background: #fffafa;
  display: grid;
  place-items: center;
  cursor: pointer;
}

.bookmark-item__remove svg {
  width: 18px;
  height: 18px;
}

.bookmark-empty {
  min-height: 360px;
  display: grid;
  place-items: center;
  align-content: center;
  text-align: center;
  padding: 32px 18px;
  border-radius: 22px;
  background: #fff;
  border: 1px solid #edf0ea;
}

.bookmark-empty__icon {
  width: 74px;
  height: 74px;
  display: grid;
  place-items: center;
  margin-bottom: 16px;
  color: #0a6b4f;
  border-radius: 22px;
  background: #eef7f2;
}

.bookmark-empty__icon svg {
  width: 32px;
  height: 32px;
}

.bookmark-empty h2 {
  margin: 0 0 8px;
  color: #17233a;
  font-size: 1.12rem;
}

.bookmark-empty p {
  margin: 0 0 18px;
  color: #6d7a74;
  font-size: 0.88rem;
}

.bookmark-empty__btn {
  min-height: 44px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0 18px;
  border-radius: 14px;
  color: #fff;
  background: #0a6b4f;
  font-size: 0.86rem;
  font-weight: 800;
  text-decoration: none;
}

.confirm-overlay {
  position: fixed;
  inset: 0;
  z-index: 1400;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.45);
  backdrop-filter: blur(4px);
  padding: 24px;
}

.confirm-modal {
  width: 100%;
  max-width: 320px;
  border-radius: 20px;
  background: #fff;
  padding: 28px 24px 20px;
  text-align: center;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
}

.confirm-modal__icon {
  width: 48px;
  height: 48px;
  margin: 0 auto 16px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: #fef2f2;
  color: #dc2626;
}

.confirm-modal__icon svg {
  width: 22px;
  height: 22px;
}

.confirm-modal__title {
  margin: 0 0 8px;
  font-size: 1.1rem;
  font-weight: 800;
  color: #1f2937;
}

.confirm-modal__desc {
  margin: 0 0 24px;
  font-size: 0.85rem;
  color: #6b7280;
  line-height: 1.5;
}

.confirm-modal__desc strong {
  color: #1f2937;
}

.confirm-modal__actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

.confirm-btn {
  height: 46px;
  border: 0;
  border-radius: 12px;
  font-size: 0.88rem;
  font-weight: 700;
  cursor: pointer;
  transition: transform 0.12s ease;
}

.confirm-btn:active {
  transform: scale(0.97);
}

.confirm-btn--cancel {
  background: #f3f4f6;
  color: #4b5563;
}

.confirm-btn--confirm {
  background: #dc2626;
  color: #fff;
}

@media (min-width: 640px) {
  .bookmarks-summary {
    grid-template-columns: auto minmax(220px, 1fr);
    align-items: center;
  }
}

/* Fade transition for modal */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>