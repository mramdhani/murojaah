import { ref } from 'vue'

const CACHE_NAME = 'murojaah-audio-v1'

export interface DownloadProgress {
  chapter: number
  qariKey: string
  percentage: number
  loadedBytes: number
  totalBytes: number
  status: 'idle' | 'downloading' | 'completed' | 'error'
  errorMsg?: string
}

export const useAudioCache = () => {
  const isDownloading = ref(false)
  const downloadProgress = ref<DownloadProgress>({
    chapter: 0,
    qariKey: '',
    percentage: 0,
    loadedBytes: 0,
    totalBytes: 0,
    status: 'idle'
  })

  // Check if audio for surah & qari is already cached offline
  const isAudioCached = async (audioUrl: string): Promise<boolean> => {
    if (!process.client || !('caches' in window) || !audioUrl) return false
    try {
      const cache = await caches.open(CACHE_NAME)
      const match = await cache.match(audioUrl)
      return !!match
    } catch (e) {
      return false
    }
  }

  // Get cached audio Blob URL (0ms latency, offline)
  const getCachedAudioUrl = async (audioUrl: string): Promise<string | null> => {
    if (!process.client || !('caches' in window) || !audioUrl) return null
    try {
      const cache = await caches.open(CACHE_NAME)
      const response = await cache.match(audioUrl)
      if (response) {
        const blob = await response.blob()
        return URL.createObjectURL(blob)
      }
    } catch (e) {
      console.warn('[AudioCache] Failed to load from cache:', e)
    }
    return null
  }

  // Download audio with real-time percentage progress (0% -> 100%)
  const downloadAudioWithProgress = async (
    audioUrl: string,
    chapter: number,
    qariKey: string,
    onProgress?: (progress: DownloadProgress) => void
  ): Promise<boolean> => {
    if (!process.client || !('caches' in window) || !audioUrl) return false

    isDownloading.value = true
    downloadProgress.value = {
      chapter,
      qariKey,
      percentage: 0,
      loadedBytes: 0,
      totalBytes: 0,
      status: 'downloading'
    }
    onProgress?.(downloadProgress.value)

    try {
      const response = await fetch(audioUrl)
      if (!response.ok) throw new Error(`HTTP error ${response.status}`)

      const contentLength = response.headers.get('content-length')
      const total = contentLength ? parseInt(contentLength, 10) : 0
      downloadProgress.value.totalBytes = total

      if (!response.body) {
        // Fallback for browsers without ReadableStream body
        const cache = await caches.open(CACHE_NAME)
        await cache.put(audioUrl, response)
        downloadProgress.value.percentage = 100
        downloadProgress.value.status = 'completed'
        isDownloading.value = false
        onProgress?.(downloadProgress.value)
        return true
      }

      const reader = response.body.getReader()
      const chunks: Uint8Array[] = []
      let loaded = 0

      while (true) {
        const { done, value } = await reader.read()
        if (done) break
        if (value) {
          chunks.push(value)
          loaded += value.length
          downloadProgress.value.loadedBytes = loaded
          if (total > 0) {
            downloadProgress.value.percentage = Math.min(99, Math.round((loaded / total) * 100))
          } else {
            downloadProgress.value.percentage = 50 // Indeterminate progress if no content-length
          }
          onProgress?.(downloadProgress.value)
        }
      }

      // Combine chunks into Response and save in CacheStorage
      const blob = new Blob(chunks, { type: response.headers.get('content-type') || 'audio/mpeg' })
      const cachedResponse = new Response(blob, {
        headers: response.headers
      })

      const cache = await caches.open(CACHE_NAME)
      await cache.put(audioUrl, cachedResponse)

      downloadProgress.value.percentage = 100
      downloadProgress.value.status = 'completed'
      isDownloading.value = false
      onProgress?.(downloadProgress.value)
      return true
    } catch (e: any) {
      console.error('[AudioCache] Download failed:', e)
      downloadProgress.value.status = 'error'
      downloadProgress.value.errorMsg = e.message || 'Gagal mengunduh audio'
      isDownloading.value = false
      onProgress?.(downloadProgress.value)
      return false
    }
  }

  // Delete cached surah audio
  const deleteCachedAudio = async (audioUrl: string): Promise<boolean> => {
    if (!process.client || !('caches' in window) || !audioUrl) return false
    try {
      const cache = await caches.open(CACHE_NAME)
      return await cache.delete(audioUrl)
    } catch (e) {
      return false
    }
  }

  return {
    isDownloading,
    downloadProgress,
    isAudioCached,
    getCachedAudioUrl,
    downloadAudioWithProgress,
    deleteCachedAudio
  }
}
