/**
 * useOrientationLock
 *
 * Older versions of the app used a CSS rotation trick to fake portrait mode on
 * iOS Safari/PWA. In practice it breaks normal landscape rendering on pages like
 * the home dashboard, so the composable now only removes the legacy class.
 */
export const useOrientationLock = () => {
  if (!import.meta.client) return

  const HTML_CLASS = 'ios-portrait-lock'

  const clearLegacyLock = () => {
    document.documentElement.classList.remove(HTML_CLASS)
  }

  clearLegacyLock()

  const route = useRoute()
  watch(() => route.path, clearLegacyLock)

  const mq = window.matchMedia('(orientation: landscape)')
  mq.addEventListener('change', clearLegacyLock)

  onUnmounted(() => {
    mq.removeEventListener('change', clearLegacyLock)
    clearLegacyLock()
  })
}
