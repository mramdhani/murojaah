<template>
  <Transition name="pwa-slide">
    <div v-if="showPrompt && !dismissed" class="pwa-prompt">
      <div class="pwa-prompt__card">
        <button class="pwa-prompt__close" aria-label="Tutup" @click="dismiss">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
        
        <div class="pwa-prompt__content">
          <div class="pwa-prompt__icon">
            <img src="/logo.png" alt="Murojaah" />
          </div>
          <div class="pwa-prompt__text">
            <strong>Install Aplikasi Murojaah</strong>
            <p>Akses Murojaah lebih cepat dan nyaman langsung dari layar utama HP Anda.</p>
          </div>
        </div>

        <div v-if="isIos" class="pwa-prompt__ios-guide">
          <p>Di Safari, ketuk ikon <strong>Bagikan</strong> <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="share-icon"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8M16 6l-4-4-4 4M12 2v13"/></svg> di bawah, lalu pilih <strong>"Tambah ke Layar Utama" (Add to Home Screen)</strong>.</p>
        </div>

        <button v-else class="pwa-prompt__btn" @click="installPwa">
          Install Sekarang
        </button>
      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'

const showPrompt = ref(false)
const dismissed = ref(false)
const isIos = ref(false)
let deferredPrompt: any = null

const dismiss = () => {
  dismissed.value = true
  localStorage.setItem('murojaah_pwa_dismissed', Date.now().toString())
}

const checkDismissed = () => {
  const lastDismissed = localStorage.getItem('murojaah_pwa_dismissed')
  if (lastDismissed) {
    // If dismissed within the last 7 days, don't show again
    if (Date.now() - parseInt(lastDismissed) < 7 * 24 * 60 * 60 * 1000) {
      return true
    }
  }
  return false
}

const installPwa = async () => {
  if (!deferredPrompt) return
  
  deferredPrompt.prompt()
  const { outcome } = await deferredPrompt.userChoice
  if (outcome === 'accepted') {
    showPrompt.value = false
  }
  deferredPrompt = null
}

const handleBeforeInstallPrompt = (e: any) => {
  e.preventDefault()
  deferredPrompt = e
  if (!checkDismissed()) {
    showPrompt.value = true
  }
}

onMounted(() => {
  if (process.server) return

  // Check if already installed
  const isStandalone = window.matchMedia('(display-mode: standalone)').matches || (window.navigator as any).standalone
  if (isStandalone) return

  // Listen for Android install prompt
  window.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt)

  // Check if iOS (iPhone/iPad) and Safari
  const ua = window.navigator.userAgent.toLowerCase()
  const isIosDevice = /iphone|ipad|ipod/.test(ua)
  
  if (isIosDevice) {
    isIos.value = true
    if (!checkDismissed()) {
      // Small delay so it doesn't immediately pop up on first load
      setTimeout(() => {
        showPrompt.value = true
      }, 3000)
    }
  }
})

onUnmounted(() => {
  if (process.client) {
    window.removeEventListener('beforeinstallprompt', handleBeforeInstallPrompt)
  }
})
</script>

<style scoped>
.pwa-prompt {
  position: fixed;
  bottom: calc(var(--safe-bottom) + var(--bottom-nav-height, 0px) + 20px);
  left: 20px;
  right: 20px;
  z-index: 9999;
  display: flex;
  justify-content: center;
  pointer-events: none;
}

.pwa-prompt__card {
  position: relative;
  width: 100%;
  max-width: 400px;
  background: rgba(255, 255, 255, 0.98);
  backdrop-filter: blur(20px);
  border-radius: 20px;
  padding: 20px;
  box-shadow: 0 12px 40px rgba(13, 56, 43, 0.16), 0 2px 10px rgba(13, 56, 43, 0.08);
  border: 1px solid rgba(22, 141, 112, 0.15);
  pointer-events: auto;
}

.pwa-prompt__close {
  position: absolute;
  top: 12px;
  right: 12px;
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  background: rgba(0, 0, 0, 0.04);
  border-radius: 50%;
  color: #666;
  cursor: pointer;
  transition: background 0.2s;
}

.pwa-prompt__close:active {
  background: rgba(0, 0, 0, 0.08);
}

.pwa-prompt__close svg {
  width: 16px;
  height: 16px;
}

.pwa-prompt__content {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  margin-bottom: 16px;
  padding-right: 24px;
}

.pwa-prompt__icon {
  width: 48px;
  height: 48px;
  flex: 0 0 48px;
  border-radius: 12px;
  background: #f1f7f4;
  padding: 6px;
  box-shadow: inset 0 0 0 1px rgba(22, 141, 112, 0.1);
}

.pwa-prompt__icon img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.pwa-prompt__text strong {
  display: block;
  font-size: 1.05rem;
  color: #1a2721;
  margin-bottom: 4px;
}

.pwa-prompt__text p {
  font-size: 0.85rem;
  line-height: 1.4;
  color: #536b5f;
  margin: 0;
}

.pwa-prompt__btn {
  width: 100%;
  padding: 12px;
  background: #168d70;
  color: white;
  border: none;
  border-radius: 12px;
  font-weight: 600;
  font-size: 0.95rem;
  cursor: pointer;
  transition: transform 0.15s, background 0.2s;
  box-shadow: 0 4px 12px rgba(22, 141, 112, 0.25);
}

.pwa-prompt__btn:active {
  transform: scale(0.98);
  background: #127960;
}

.pwa-prompt__ios-guide {
  background: #f1f7f4;
  padding: 12px 14px;
  border-radius: 12px;
  border: 1px solid rgba(22, 141, 112, 0.15);
}

.pwa-prompt__ios-guide p {
  margin: 0;
  font-size: 0.85rem;
  line-height: 1.45;
  color: #2b4438;
}

.pwa-prompt__ios-guide strong {
  color: #168d70;
}

.share-icon {
  width: 16px;
  height: 16px;
  vertical-align: -3px;
  margin: 0 2px;
  color: #168d70;
}

.pwa-slide-enter-active,
.pwa-slide-leave-active {
  transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.pwa-slide-enter-from,
.pwa-slide-leave-to {
  opacity: 0;
  transform: translateY(30px) scale(0.95);
}
</style>
