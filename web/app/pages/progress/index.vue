<template>
  <div class="page">
    <header class="progress-header">
      <!-- Background arabesque overlay -->
      <div class="progress-header__arabesque"></div>

      <!-- Floating sparkles -->
      <span class="hdr-sparkle hdr-sparkle--1">✦</span>
      <span class="hdr-sparkle hdr-sparkle--2">◈</span>
      <span class="hdr-sparkle hdr-sparkle--3">✧</span>
      <span class="hdr-dot hdr-dot--1"></span>
      <span class="hdr-dot hdr-dot--2"></span>

      <div class="container" style="position: relative; z-index: 2; padding-bottom: 24px;">
        <div class="progress-header__text">
          <h1 class="progress-title">Progress Hafalan</h1>
          <p class="progress-subtitle">
            <span class="hdr-diamond">◆</span>
            Lihat perkembangan hafalan per surat
          </p>
        </div>

        <div class="progress-header__badge-card" v-if="userBadge">
          <div class="ph-badge-left">
            <span class="ph-badge-label">LEVEL HAFALAN</span>
            <div class="ph-badge-name-row">
              <span class="ph-badge-name">{{ userBadge.name }}</span>
              <span class="ph-badge-arabic">· {{ userBadge.arabic }}</span>
            </div>
            <div class="ph-badge-progress-wrap" v-if="nextBadge">
              <div class="ph-badge-meta">
                <span class="ph-badge-pct">{{ badgeProgress }}%</span>
                <span class="ph-badge-next">{{ ayahsToNext }} ayat menuju {{ nextBadge.name }}</span>
              </div>
              <div class="ph-badge-bar">
                <div class="ph-badge-fill" :style="{ width: badgeProgress + '%' }"></div>
              </div>
            </div>
          </div>
          <div class="ph-badge-right">
            <div class="ph-badge-ring"></div>
            <img :src="userBadge.image" :alt="userBadge.name" class="ph-badge-img" />
          </div>
        </div>
      </div>

      <!-- Elegant curved divider -->
      <div class="progress-header__divider">
        <div class="progress-header__divider-fill"></div>
        <svg class="progress-header__divider-svg" viewBox="0 0 390 28" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <linearGradient id="divGold" x1="0" y1="0" x2="390" y2="0" gradientUnits="userSpaceOnUse">
              <stop offset="0%" stop-color="rgba(170,124,17,0)"/>
              <stop offset="20%" stop-color="#D4AF37"/>
              <stop offset="50%" stop-color="#FFF8D6"/>
              <stop offset="80%" stop-color="#D4AF37"/>
              <stop offset="100%" stop-color="rgba(170,124,17,0)"/>
            </linearGradient>
          </defs>
          <path d="M0,28 Q195,0 390,28 L390,28 L0,28 Z" fill="#FAFAF5"/>
          <path d="M0,28 Q195,0 390,28" fill="none" stroke="url(#divGold)" stroke-width="1.5"/>
        </svg>
        <div class="hdr-diamond-ornament">◆</div>
      </div>
    </header>

    <div class="page-content container">
      <div class="progress-list" v-if="surahs.length > 0">
        <NuxtLink
          v-for="surah in reviewedSurahs"
          :key="surah.id"
          :to="`/progress/${surah.id}`"
          class="progress-card animate-fade-in"
        >
          <div class="progress-card__header">
            <div class="progress-card__title-section">
              <div class="progress-card__number-wrap">
                <span class="progress-card__number">{{ surah.number }}</span>
              </div>
              <div class="progress-card__name-col">
                <span class="progress-card__name">{{ surah.name_latin }}</span>
                <span class="progress-card__arabic">{{ surah.name_arabic }}</span>
              </div>
            </div>
            <button class="progress-card__delete-btn" @click.stop.prevent="confirmDelete(surah.id, surah.name_latin)" title="Hapus Progress">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="3 6 5 6 21 6"></polyline>
                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                <line x1="10" y1="11" x2="10" y2="17"></line>
                <line x1="14" y1="11" x2="14" y2="17"></line>
              </svg>
            </button>
          </div>

          <div class="progress-bar-wrap">
            <div class="progress-bar">
              <div class="progress-bar__segment progress-bar__segment--fluent" :style="{ width: pct(surah, 'fluent') }"></div>
              <div class="progress-bar__segment progress-bar__segment--doubtful" :style="{ width: pct(surah, 'doubtful') }"></div>
              <div class="progress-bar__segment progress-bar__segment--forgot" :style="{ width: pct(surah, 'forgot') }"></div>
            </div>
          </div>

          <div class="progress-card__stats">
            <div class="progress-stat progress-stat--fluent">
              <span class="stat-dot"></span>
              <strong>{{ surah.progress.fluent }}</strong> Lancar
            </div>
            <div class="progress-stat progress-stat--doubtful">
              <span class="stat-dot"></span>
              <strong>{{ surah.progress.doubtful }}</strong> Ragu
            </div>
            <div class="progress-stat progress-stat--forgot">
              <span class="stat-dot"></span>
              <strong>{{ surah.progress.forgot }}</strong> Lupa
            </div>
          </div>
        </NuxtLink>
      </div>

      <!-- Empty State -->
      <div v-if="!loading && reviewedSurahs.length === 0" class="empty-state">
        <div class="empty-state__icon">📖</div>
        <h3 class="empty-state__title">Belum ada progress</h3>
        <p class="empty-state__desc">Mulai hafalan untuk melihat progress di sini</p>
        <NuxtLink to="/surahs" class="btn btn-primary empty-state__btn">Mulai Hafalan</NuxtLink>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="progress-list">
        <div v-for="i in 4" :key="i" class="progress-card" style="padding: 20px">
          <div class="skeleton" style="width: 150px; height: 20px; margin-bottom: 16px"></div>
          <div class="skeleton" style="width: 100%; height: 8px; margin-bottom: 16px"></div>
          <div class="skeleton" style="width: 200px; height: 14px"></div>
        </div>
      </div>
    </div>

    <!-- Custom Delete Confirmation Modal -->
    <div v-if="isDeleteModalOpen" class="modal-overlay animate-fade-in" @click="closeDeleteModal">
      <div class="modal-dialog animate-scale-in" @click.stop>
        <div class="modal-header">
          <div class="modal-icon-warning">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
              <line x1="12" y1="9" x2="12" y2="13"></line>
              <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
          </div>
          <h3 class="modal-title">Hapus Progress</h3>
        </div>
        <div class="modal-body">
          <p>Apakah Kakak yakin ingin menghapus <strong>seluruh progress murojaah</strong> dan <strong>riwayat</strong> untuk surat <strong>{{ surahToDelete?.name }}</strong>?</p>

          <!-- Badge Impact Warning -->
          <div class="modal-badge-impact" v-if="userBadge">
            <div class="modal-badge-impact__header">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
              </svg>
              <span>Dampak pada Badge Level</span>
            </div>
            <div class="modal-badge-impact__content">
              <img :src="userBadge.image" :alt="userBadge.name" class="modal-badge-img" />
              <div class="modal-badge-impact__text">
                <span class="modal-badge-level" :style="{ color: userBadge.color }">{{ userBadge.name }} · {{ userBadge.arabic }}</span>
                <span class="modal-badge-desc">Menghapus progress akan mengurangi total ayat yang dihafal. Jika jumlah ayat turun di bawah batas level saat ini, <strong>badge Kakak bisa turun level</strong>.</span>
              </div>
            </div>
          </div>

          <p class="modal-text-danger">Tindakan ini tidak dapat dibatalkan.</p>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="closeDeleteModal">Batal</button>
          <button class="btn btn-danger" @click="executeDelete" :disabled="isDeleting">
            <span v-if="isDeleting">Menghapus...</span>
            <span v-else>Ya, Hapus</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const { apiFetch } = useApi()
const { getBadge, getNextBadge, getAyahsToNext, getProgressToNext } = useBadge()

interface SurahProgress {
  id: number
  number: number
  name_latin: string
  name_arabic: string
  total_ayah: number
  progress: { fluent: number; doubtful: number; forgot: number; unreviewed: number }
}

const surahs = ref<SurahProgress[]>([])
const loading = ref(true)

const reviewedSurahs = computed(() =>
  surahs.value.filter(s => s.progress.fluent + s.progress.doubtful + s.progress.forgot > 0)
)

const totalAyah = computed(() => {
  return surahs.value.reduce((sum, s) => sum + s.progress.fluent + s.progress.doubtful + s.progress.forgot, 0)
})

const userBadge = computed(() => getBadge(totalAyah.value))
const nextBadge = computed(() => getNextBadge(totalAyah.value))
const ayahsToNext = computed(() => getAyahsToNext(totalAyah.value))
const badgeProgress = computed(() => getProgressToNext(totalAyah.value))

const pct = (surah: SurahProgress, status: string) => {
  const count = surah.progress[status as keyof typeof surah.progress] || 0
  return `${(count / surah.total_ayah) * 100}%`
}

const fetchSurahProgress = async () => {
  try {
    const res = await apiFetch<{ data: SurahProgress[] }>('/surahs')
    surahs.value = res.data
  } catch (e) {
    console.error('Failed to load progress:', e)
  } finally {
    loading.value = false
  }
}

const isDeleteModalOpen = ref(false)
const isDeleting = ref(false)
const surahToDelete = ref<{id: number, name: string} | null>(null)

const confirmDelete = (surahId: number, surahName: string) => {
  if (typeof navigator !== 'undefined' && navigator.vibrate) {
    navigator.vibrate(60)
  }
  surahToDelete.value = { id: surahId, name: surahName }
  isDeleteModalOpen.value = true
}

const closeDeleteModal = () => {
  isDeleteModalOpen.value = false
  surahToDelete.value = null
}

const executeDelete = async () => {
  if (!surahToDelete.value) return
  
  isDeleting.value = true
  try {
    await apiFetch(`/progress/surahs/${surahToDelete.value.id}`, {
      method: 'DELETE',
    })
    // Refresh list
    await fetchSurahProgress()
    closeDeleteModal()
  } catch (e) {
    console.error('Failed to delete progress:', e)
  } finally {
    isDeleting.value = false
  }
}

onMounted(() => {
  fetchSurahProgress()
})

useHead({ title: 'Progress Hafalan — Murojaah' })
</script>

<style scoped>
/* ================================================
   HEADER (Mirrors homepage styling)
   ================================================ */
.progress-header {
  padding: calc(var(--safe-top) + 36px) 0 0 !important;
  background: linear-gradient(160deg, #052e1c 0%, #064E3B 45%, #0a6349 100%) !important;
  color: white;
  position: relative;
  overflow: hidden;
}

/* Subtle arabesque/geometric overlay */
.progress-header__arabesque {
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

.progress-header .container {
  padding: 0 20px;
}

.progress-title {
  font-size: 1.8rem;
  font-weight: 800;
  letter-spacing: -0.03em;
  line-height: 1.1;
  margin-bottom: 8px;
  color: #fff;
}

.progress-subtitle {
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

/* Header Badge Card */
.progress-header__badge-card {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(12px);
  border-radius: 16px;
  padding: 16px 20px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}

.ph-badge-left {
  flex: 1;
}

.ph-badge-label {
  display: block;
  font-size: 0.65rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  color: rgba(255, 255, 255, 0.6);
  margin-bottom: 4px;
}

.ph-badge-name-row {
  display: flex;
  align-items: baseline;
  gap: 8px;
  margin-bottom: 12px;
}

.ph-badge-name {
  font-size: 1.4rem;
  font-weight: 800;
  color: #fff;
  line-height: 1;
}

.ph-badge-arabic {
  font-size: 0.85rem;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.7);
}

.ph-badge-progress-wrap {
  display: flex;
  flex-direction: column;
  gap: 6px;
  max-width: 220px;
}

.ph-badge-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.ph-badge-pct {
  font-size: 0.85rem;
  font-weight: 800;
  color: #fff;
}

.ph-badge-next {
  font-size: 0.7rem;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.6);
}

.ph-badge-bar {
  height: 6px;
  background: rgba(0, 0, 0, 0.2);
  border-radius: 99px;
  overflow: hidden;
}

.ph-badge-fill {
  height: 100%;
  background: #D4AF37;
  border-radius: 99px;
  position: relative;
  transition: width 0.8s ease;
}
.ph-badge-fill::after {
  content: '';
  position: absolute;
  top: 0; right: 0;
  width: 6px; height: 100%;
  background: rgba(255, 255, 255, 0.6);
  border-radius: 99px;
  filter: blur(2px);
}

.ph-badge-right {
  position: relative;
  flex-shrink: 0;
  margin-left: 16px;
}

.ph-badge-ring {
  position: absolute;
  inset: -6px;
  border-radius: 50%;
  border: 1.5px solid rgba(212, 175, 55, 0.4);
  animation: ringPulse 2.5s ease-in-out infinite;
}

.ph-badge-img {
  width: 60px;
  height: 60px;
  object-fit: contain;
  position: relative;
  z-index: 1;
  filter: drop-shadow(0 4px 10px rgba(0,0,0,0.25));
  animation: heroFloat 3.5s ease-in-out infinite;
}

@keyframes heroFloat {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-4px); }
}

@keyframes ringPulse {
  0%, 100% { transform: scale(1); opacity: 0.5; }
  50% { transform: scale(1.08); opacity: 1; }
}

/* Elegant curved divider */
.progress-header__divider {
  position: relative;
  z-index: 1;
  line-height: 0;
  margin-top: 4px;
}

.progress-header__divider-fill {
  height: 0;
}

.progress-header__divider-svg {
  width: 100%;
  height: 28px;
  display: block;
}

.hdr-diamond-ornament {
  position: absolute;
  top: -7px;
  left: 50%;
  transform: translateX(-50%);
  font-size: 0.85rem;
  color: #D4AF37;
  text-shadow: 0 0 10px rgba(212, 175, 55, 0.6), 0 0 20px rgba(212, 175, 55, 0.3);
  z-index: 3;
  line-height: 1;
}

/* ================================================
   CONTENT
   ================================================ */
.progress-list {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.progress-card {
  display: flex;
  flex-direction: column;
  padding: 18px 20px;
  background: var(--color-bg-card);
  border-radius: 16px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.03);
  border: 1px solid rgba(0,0,0,0.04);
  text-decoration: none;
  transition: transform var(--transition-fast), box-shadow var(--transition-fast);
}

.progress-card:hover {
  box-shadow: 0 6px 16px rgba(0,0,0,0.06);
}

.progress-card:active {
  transform: scale(0.98);
}

.progress-card__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.progress-card__title-section {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
}

.progress-card__number-wrap {
  width: 32px;
  height: 32px;
  border-radius: 10px;
  background: var(--color-bg-subtle);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.progress-card__number {
  font-size: 0.8rem;
  font-weight: 800;
  color: var(--color-text-muted);
}

.progress-card__name-col {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.progress-card__name {
  font-weight: 800;
  font-size: 1.05rem;
  color: var(--color-text);
  letter-spacing: -0.01em;
}

.progress-card__arabic {
  font-family: var(--font-arabic);
  font-size: 0.95rem;
  color: var(--color-primary-dark);
}

.progress-card__delete-btn {
  color: var(--color-text-muted);
  padding: 8px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all var(--transition-fast);
  background: rgba(0,0,0,0.02);
}

.progress-card__delete-btn:hover {
  color: #DC2626;
  background: #FEF2F2;
}

.progress-card__delete-btn:active {
  transform: scale(0.9);
}

.progress-bar-wrap {
  margin-bottom: 14px;
}

.progress-bar {
  height: 8px;
  background: var(--color-bg-subtle);
  border-radius: 99px;
  overflow: hidden;
  display: flex;
}

.progress-bar__segment {
  height: 100%;
  transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.progress-bar__segment--fluent { background: var(--color-fluent); }
.progress-bar__segment--doubtful { background: var(--color-doubtful); }
.progress-bar__segment--forgot { background: var(--color-forgot); }

.progress-card__stats {
  display: flex;
  gap: 16px;
  border-top: 1px solid rgba(0,0,0,0.04);
  padding-top: 14px;
}

.progress-stat {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.78rem;
  font-weight: 600;
  color: var(--color-text-secondary);
}

.stat-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.progress-stat--fluent .stat-dot { background: var(--color-fluent); }
.progress-stat--doubtful .stat-dot { background: var(--color-doubtful); }
.progress-stat--forgot .stat-dot { background: var(--color-forgot); }

.progress-stat--fluent strong { color: var(--color-fluent); }
.progress-stat--doubtful strong { color: #B45309; }
.progress-stat--forgot strong { color: var(--color-forgot); }

/* ================================================
   EMPTY STATE & MODAL
   ================================================ */
.empty-state {
  text-align: center;
  padding: 80px 20px;
  background: var(--color-bg-card);
  border-radius: 20px;
  border: 1.5px dashed rgba(5,150,105,0.15);
  margin-top: 20px;
}

.empty-state__icon {
  font-size: 3rem;
  margin-bottom: 16px;
  animation: heroFloat 4s ease-in-out infinite;
}

.empty-state__title {
  font-size: 1.25rem;
  font-weight: 800;
  color: var(--color-text);
  margin-bottom: 6px;
}

.empty-state__desc {
  color: var(--color-text-muted);
  font-size: 0.9rem;
  margin-bottom: 24px;
}

.empty-state__btn {
  padding: 12px 28px;
  border-radius: 99px;
}

/* Modal Styles */
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

.modal-icon-warning {
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

.modal-text-danger {
  color: #DC2626;
  font-weight: 700;
  font-size: 0.85rem;
  margin-top: 16px;
}

/* Badge Impact in Modal */
.modal-badge-impact {
  margin: 16px 0 0;
  background: #FFFBEB;
  border: 1.5px solid #FDE68A;
  border-radius: 14px;
  overflow: hidden;
}

.modal-badge-impact__header {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  background: #FEF3C7;
  font-size: 0.75rem;
  font-weight: 800;
  color: #B45309;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border-bottom: 1px solid #FDE68A;
}

.modal-badge-impact__content {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px;
}

.modal-badge-img {
  width: 48px;
  height: 48px;
  object-fit: contain;
  flex-shrink: 0;
}

.modal-badge-impact__text {
  display: flex;
  flex-direction: column;
  gap: 4px;
  text-align: left;
}

.modal-badge-level {
  font-size: 0.85rem;
  font-weight: 800;
  letter-spacing: 0.01em;
}

.modal-badge-desc {
  font-size: 0.78rem;
  color: #78350F;
  line-height: 1.4;
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
}

.btn-danger {
  background: #DC2626;
  color: white;
  border: none;
}

.btn-danger:hover {
  background: #B91C1C;
}

.btn-danger:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}
</style>
