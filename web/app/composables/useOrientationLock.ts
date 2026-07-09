/**
 * useOrientationLock
 *
 * Forces portrait orientation on iOS PWA/Safari by applying a CSS-transform
 * rotation trick when the device is in landscape AND we are NOT on a page
 * that explicitly allows landscape (e.g. /mushaf/...).
 *
 * Android already respects the manifest `orientation: 'portrait'` so this
 * composable is a no-op there.
 *
 * Usage: call once in app.vue → onMounted.
 */
export const useOrientationLock = () => {
  if (!import.meta.client) return

  const route = useRoute()

  /**
   * Returns true when the current device is an iOS device running in a
   * standalone (PWA) context OR in Safari (we lock orientation in both cases
   * because iOS Safari also ignores the manifest orientation field).
   */
  const isIOS = (): boolean => {
    const ua = navigator.userAgent
    return /iPad|iPhone|iPod/.test(ua) && !(window as any).MSStream
  }

  /**
   * Returns true when the current route allows landscape orientation.
   * Add more patterns here if needed in the future.
   */
  const isLandscapeAllowedRoute = (): boolean => {
    return route.path.startsWith('/mushaf/')
  }

  const HTML_CLASS = 'ios-portrait-lock'

  const applyLock = () => {
    if (!isIOS()) return

    const isLandscape = window.matchMedia('(orientation: landscape)').matches
    const html = document.documentElement

    if (isLandscape && !isLandscapeAllowedRoute()) {
      html.classList.add(HTML_CLASS)
    } else {
      html.classList.remove(HTML_CLASS)
    }
  }

  // Re-evaluate whenever the route changes (e.g., navigating to/from mushaf)
  watch(() => route.path, () => {
    applyLock()
  })

  // Re-evaluate on orientation change
  const mq = window.matchMedia('(orientation: landscape)')
  mq.addEventListener('change', applyLock)

  // Initial check
  applyLock()

  // Cleanup on unmount (called from app.vue which lives forever, so optional)
  onUnmounted(() => {
    mq.removeEventListener('change', applyLock)
    document.documentElement.classList.remove(HTML_CLASS)
  })
}
