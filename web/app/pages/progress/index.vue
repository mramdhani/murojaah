<template>
  <div class="page">
    <header class="page-header">
      <div class="container">
        <h1>Progress Hafalan</h1>
        <p>Lihat perkembangan hafalan per surat</p>
      </div>
    </header>

    <div class="page-content container">
      <div class="progress-list" v-if="surahs.length > 0">
        <NuxtLink
          v-for="surah in reviewedSurahs"
          :key="surah.id"
          :to="`/progress/${surah.id}`"
          class="progress-card card card-interactive animate-fade-in"
        >
          <div class="progress-card__header">
            <div class="progress-card__title-section">
              <div>
                <span class="progress-card__number">{{ surah.number }}.</span>
                <span class="progress-card__name">{{ surah.name_latin }}</span>
              </div>
              <span class="progress-card__arabic">{{ surah.name_arabic }}</span>
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

          <div class="progress-bar" style="height: 8px; margin: 12px 0">
            <div class="progress-bar__segment progress-bar__segment--fluent" :style="{ width: pct(surah, 'fluent') }"></div>
            <div class="progress-bar__segment progress-bar__segment--doubtful" :style="{ width: pct(surah, 'doubtful') }"></div>
            <div class="progress-bar__segment progress-bar__segment--forgot" :style="{ width: pct(surah, 'forgot') }"></div>
          </div>

          <div class="progress-card__stats">
            <span class="progress-stat progress-stat--fluent">{{ surah.progress.fluent }} Lancar</span>
            <span class="progress-stat progress-stat--doubtful">{{ surah.progress.doubtful }} Ragu</span>
            <span class="progress-stat progress-stat--forgot">{{ surah.progress.forgot }} Lupa</span>
          </div>
        </NuxtLink>
      </div>

      <!-- Empty State -->
      <div v-if="!loading && reviewedSurahs.length === 0" class="empty-state">
        <div class="empty-state__icon">📖</div>
        <h3>Belum ada progress</h3>
        <p>Mulai hafalan untuk melihat progress di sini</p>
        <NuxtLink to="/surahs" class="btn btn-primary" style="margin-top: 16px">Mulai Hafalan</NuxtLink>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="progress-list">
        <div v-for="i in 4" :key="i" class="card" style="padding: 20px">
          <div class="skeleton" style="width: 150px; height: 20px; margin-bottom: 12px"></div>
          <div class="skeleton" style="width: 100%; height: 8px; margin-bottom: 12px"></div>
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
.progress-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.progress-card {
  padding: 16px;
  text-decoration: none;
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
  border-radius: 16px;
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
  width: 48px;
  height: 48px;
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
  font-weight: 700;
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
  font-weight: 600;
  font-size: 0.875rem;
  margin-top: 12px;
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
  border: 1px solid var(--border-color);
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

.progress-card__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
}

.progress-card__title-section {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  flex: 1;
}

.progress-card__delete-btn {
  color: var(--color-text-muted);
  padding: 6px;
  border-radius: var(--radius-sm);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all var(--transition-fast);
  cursor: pointer;
  -webkit-tap-highlight-color: transparent;
}

.progress-card__delete-btn:hover {
  color: var(--color-forgot);
  background: var(--color-forgot-bg);
}

.progress-card__delete-btn:active {
  transform: scale(0.9);
}

.progress-card__number {
  font-weight: 600;
  color: var(--color-text-muted);
  margin-right: 4px;
}

.progress-card__name {
  font-weight: 700;
  font-size: 1rem;
}

.progress-card__arabic {
  font-family: var(--font-arabic);
  font-size: 1.125rem;
  color: var(--color-primary-dark);
}

.progress-card__stats {
  display: flex;
  gap: 16px;
}

.progress-stat {
  font-size: 0.75rem;
  font-weight: 600;
}

.progress-stat--fluent { color: var(--color-fluent); }
.progress-stat--doubtful { color: #B45309; }
.progress-stat--forgot { color: var(--color-forgot); }

.empty-state {
  text-align: center;
  padding: 64px 20px;
}

.empty-state__icon {
  font-size: 3rem;
  margin-bottom: 12px;
}

.empty-state h3 {
  font-size: 1.125rem;
  font-weight: 700;
  margin-bottom: 4px;
}

.empty-state p {
  color: var(--color-text-muted);
  font-size: 0.875rem;
}
</style>
