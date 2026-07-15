/**
 * useBottomSheet
 *
 * Reusable composable for bottom-sheet / drawer elements.
 *
 * Two modes:
 *
 * 1. 'resize' (default) — user can drag up/down to resize the sheet height.
 *    Use for: Translation drawer where users want to see more/less content.
 *
 * 2. 'dismiss' — sheet stays at its natural content height.
 *    Only dragging DOWN triggers close (iOS-style swipe to dismiss).
 *    Use for: Qari picker, Navigator, Audio Settings, MurojaahDrawer.
 */

interface UseBottomSheetOptions {
  /** 'dismiss' = iOS-style swipe down to close only. 'resize' = free drag up/down. Default: 'dismiss' */
  mode?: 'dismiss' | 'resize'
  /** [resize only] Initial height in px when sheet opens. Default: 360 */
  initialHeight?: number
  /** [resize only] Minimum height (px). Default: 120 */
  minHeight?: number
  /** [resize only] Maximum height. Default: 88% of window height */
  maxHeight?: number | (() => number)
  /** [dismiss] How many px downward drag triggers close. Default: 80 */
  closeThreshold?: number
  /** Called when user drags the sheet down past closeThreshold */
  onClose: () => void
}

export function useBottomSheet(options: UseBottomSheetOptions) {
  const {
    mode = 'dismiss',
    initialHeight = 360,
    minHeight = 120,
    closeThreshold = 80,
    onClose,
  } = options

  const getMaxHeight = () => {
    if (typeof options.maxHeight === 'function') return options.maxHeight()
    if (typeof options.maxHeight === 'number') return options.maxHeight
    return Math.round(window.innerHeight * 0.88)
  }

  // ── Shared state ────────────────────────────────────────────────────────────
  const isDragging = ref(false)
  let dragStartY = 0

  // ── DISMISS mode state (translateY offset) ──────────────────────────────────
  const translateY = ref(0)

  // ── RESIZE mode state (height) ──────────────────────────────────────────────
  const height = ref(initialHeight)
  let dragStartHeight = 0
  let willClose = false

  // ── Style for the sheet element ─────────────────────────────────────────────
  const sheetStyle = computed(() => {
    if (mode === 'dismiss') {
      return {
        transform: `translateY(${translateY.value}px)`,
        transition: isDragging.value ? 'none' : 'transform 0.32s cubic-bezier(0.32, 0.72, 0, 1)',
        // iOS spring on return
      }
    } else {
      return {
        height: `${height.value}px`,
        transition: isDragging.value ? 'none' : 'height 0.28s cubic-bezier(0.34, 1.2, 0.64, 1)',
      }
    }
  })

  // ── Drag handler ─────────────────────────────────────────────────────────────
  function onDragStart(e: TouchEvent | MouseEvent) {
    isDragging.value = true
    willClose = false
    dragStartY = 'touches' in e ? e.touches[0].clientY : (e as MouseEvent).clientY
    dragStartHeight = height.value

    function onMove(ev: TouchEvent | MouseEvent) {
      if (!isDragging.value) return
      const currentY = 'touches' in ev ? ev.touches[0].clientY : (ev as MouseEvent).clientY
      const deltaDown = currentY - dragStartY  // positive = dragging down

      if (mode === 'dismiss') {
        // Only allow dragging down (clamp to 0 on upward drag)
        translateY.value = Math.max(0, deltaDown)
      } else {
        // Resize: drag up = taller, drag down can trigger close
        const delta = dragStartY - currentY  // positive = up = taller
        const rawHeight = dragStartHeight + delta
        const maxH = getMaxHeight()

        if (rawHeight < minHeight - closeThreshold) {
          willClose = true
          // Rubber-band feel while dragging toward close
          height.value = Math.max(40, rawHeight + closeThreshold * 0.5)
        } else {
          willClose = false
          height.value = Math.min(maxH, Math.max(minHeight, rawHeight))
        }
      }
    }

    function onEnd() {
      isDragging.value = false

      if (mode === 'dismiss') {
        if (translateY.value > closeThreshold) {
          // Animate off-screen then close
          translateY.value = 600
          setTimeout(() => {
            onClose()
            translateY.value = 0  // reset for next open
          }, 280)
        } else {
          // Spring back
          translateY.value = 0
        }
      } else {
        if (willClose) {
          height.value = 0
          setTimeout(() => onClose(), 260)
        } else {
          const maxH = getMaxHeight()
          height.value = Math.min(maxH, Math.max(minHeight, height.value))
        }
      }

      document.removeEventListener('mousemove', onMove as EventListener)
      document.removeEventListener('mouseup', onEnd)
      document.removeEventListener('touchmove', onMove as EventListener)
      document.removeEventListener('touchend', onEnd)
    }

    document.addEventListener('mousemove', onMove as EventListener, { passive: false })
    document.addEventListener('mouseup', onEnd)
    document.addEventListener('touchmove', onMove as EventListener, { passive: false })
    document.addEventListener('touchend', onEnd)
  }

  // ── Bind to handle element ───────────────────────────────────────────────────
  const bindHandle = {
    onTouchstart: (e: TouchEvent) => { if (e.cancelable) e.preventDefault(); onDragStart(e) },
    onMousedown: (e: MouseEvent) => { e.preventDefault(); onDragStart(e) },
  }

  // ── Reset (call when sheet opens) ────────────────────────────────────────────
  function reset(h?: number) {
    translateY.value = 0
    if (mode === 'resize') height.value = h ?? initialHeight
  }

  return {
    height,
    translateY,
    isDragging,
    sheetStyle,
    bindHandle,
    reset,
    onDragStart,
  }
}
