/**
 * useScrollRestore
 *
 * Saves and restores the scroll position of the `.page` element
 * (the global scrollable container defined in main.css) when navigating
 * between pages.
 *
 * This fixes the iOS landscape bug where the scroll position resets to
 * the top when navigating back to a previously scrolled page.
 *
 * Usage: call in any page component that uses the `.page` scroll container.
 *
 * @param key   - Unique key for this page (e.g. route name or path).
 */
export const useScrollRestore = (key: string) => {
  if (!import.meta.client) return

  const STORAGE_PREFIX = 'murojaah_scroll_'
  const storageKey = STORAGE_PREFIX + key

  const getScrollContainer = (): HTMLElement | null => {
    // The global .page class in main.css is the overflow-y:auto container.
    return document.querySelector('.page') as HTMLElement | null
  }

  /** Save current scroll position to sessionStorage before leaving the page. */
  const saveScrollPosition = () => {
    const el = getScrollContainer()
    if (!el) return
    try {
      sessionStorage.setItem(storageKey, String(el.scrollTop))
    } catch {
      // sessionStorage not available (e.g. private mode some browsers)
    }
  }

  /** Restore saved scroll position after the page mounts / is shown again. */
  const restoreScrollPosition = () => {
    const el = getScrollContainer()
    if (!el) return
    try {
      const saved = sessionStorage.getItem(storageKey)
      if (saved !== null) {
        // Use requestAnimationFrame to ensure DOM is fully painted first
        requestAnimationFrame(() => {
          el.scrollTop = Number(saved)
        })
      }
    } catch {
      // ignore
    }
  }

  /** Clear the saved position for this page (call after intentional resets). */
  const clearScrollPosition = () => {
    try {
      sessionStorage.removeItem(storageKey)
    } catch {
      // ignore
    }
  }

  // Auto-save when leaving the page
  onBeforeRouteLeave(() => {
    saveScrollPosition()
  })

  // Auto-restore when the page mounts
  onMounted(() => {
    restoreScrollPosition()
  })

  return { saveScrollPosition, restoreScrollPosition, clearScrollPosition }
}
